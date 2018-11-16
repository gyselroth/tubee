<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Dreamscapes\Ldap\Core\Ldap as LdapServer;
use Generator;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Endpoint\Ldap\Exception as LdapEndpointException;
use Tubee\Endpoint\Ldap\QueryTransformer;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

class Ldap extends AbstractEndpoint
{
    /**
     * Ldap.
     *
     * @var Ldap
     */
    protected $ldap;

    /**
     * Uri.
     *
     * @var string
     */
    protected $uri = 'ldap://127.0.0.1:389';

    /**
     * Binddn.
     *
     * @var string
     */
    protected $binddn;

    /**
     * Bindpw.
     *
     * @var string
     */
    protected $bindpw;

    /**
     * Basedn.
     *
     * @var string
     */
    protected $basedn = '';

    /**
     * tls.
     *
     * @var bool
     */
    protected $tls = false;

    /**
     *  Options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, LdapServer $ldap, DataTypeInterface $datatype, WorkflowFactory $workflow, LoggerInterface $logger, array $resource = [])
    {
        $this->filter_all = '(objectClass=*)';
        $this->ldap = $ldap;

        if (isset($resource['resource'])) {
            $this->setLdapOptions($resource['resource']);
        }

        parent::__construct($name, $type, $datatype, $workflow, $logger, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function setup(bool $simulate = false): EndpointInterface
    {
        $this->logger->debug('connect to ldap server ['.$this->uri.']', [
            'category' => get_class($this),
        ]);

        if (null === $this->binddn) {
            $this->logger->warning('no binddn set for ldap connection, you should avoid anonymous bind', [
                'category' => get_class($this),
            ]);
        }

        if (false === $this->tls && 'ldaps' !== substr($this->uri, 0, 5)) {
            $this->logger->warning('neither tls nor ldaps enabled for ldap connection, it is strongly reccommended to encrypt ldap connections', [
                'category' => get_class($this),
            ]);
        }

        $this->ldap->connect($this->uri);

        foreach ($this->options as $opt => $value) {
            $this->ldap->setOption(constant($opt), $value);
        }

        if (true === $this->tls) {
            $this->ldap->startTls();
        }

        $this->logger->info('bind to ldap server ['.$this->uri.'] with binddn ['.$this->binddn.']', [
            'category' => get_class($this),
        ]);

        $this->ldap->bind($this->binddn, $this->bindpw);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setLdapOptions(?array $config = null): EndpointInterface
    {
        if ($config === null) {
            return $this;
        }

        foreach ($config as $option => $value) {
            switch ($option) {
                case 'options':
                    $this->options = $value;

                    break;
                case 'uri':
                case 'binddn':
                case 'bindpw':
                case 'basedn':
                    $this->{$option} = (string) $value;

                    break;
                case 'tls':
                    $this->tls = (bool) $value;

                    break;
                default:
                    throw new InvalidArgumentException('unknown ldap option '.$option.' given');
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(bool $simulate = false): EndpointInterface
    {
        $this->ldap->close();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function change(AttributeMapInterface $map, array $diff, array $object, array $endpoint_object, bool $simulate = false): ?string
    {
        $object = array_change_key_case($object);
        $dn = $this->getDn($object, $endpoint_object);

        if (isset($diff['entrydn'])) {
            unset($diff['entrydn']);
        }

        $this->logger->info('update ldap object ['.$dn.'] on endpoint ['.$this->getIdentifier().'] with attributes [{attributes}]', [
            'category' => get_class($this),
            'attributes' => $diff,
        ]);

        if ($dn !== $endpoint_object['entrydn']) {
            $this->moveLdapObject($dn, $endpoint_object['entrydn'], $simulate);
            $rdn_attr = explode('=', $dn);
            $rdn_attr = strtolower(array_shift($rdn_attr));

            if (isset($diff[$rdn_attr])) {
                unset($diff[$rdn_attr]);
            }
        }

        if ($simulate === false) {
            $this->ldap->modifyBatch($dn, $diff);
        }

        return $dn;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AttributeMapInterface $map, array $object, array $endpoint_object, bool $simulate = false): bool
    {
        $dn = $this->getDn($object, $endpoint_object);
        $this->logger->debug('delete ldap object ['.$dn.']', [
            'category' => get_class($this),
        ]);

        if ($simulate === false) {
            $this->ldap->delete($dn);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string
    {
        $dn = $this->getDn($object);

        if (isset($object['entrydn'])) {
            unset($object['entrydn']);
        }

        $this->logger->info('create new ldap object ['.$dn.'] on endpoint ['.$this->getIdentifier().'] with attributes [{attributes}]', [
            'category' => get_class($this),
            'attributes' => $object,
        ]);

        if ($simulate === false) {
            $this->ldap->add($dn, $object);
        }

        return $dn;
    }

    /**
     * {@inheritdoc}
     */
    public function transformQuery(?array $query = null)
    {
        $result = null;

        if($this->filter_all !== null) {
            $result = $this->filter_all;
        }

        if(!empty($query)) {
            if($this->filter_all === null) {
                $result = QueryTransformer::transform($query);
            } else {
                $result = '&('.$this->filter_all.')('.QueryTransformer::transform($query).')';
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(?array $query=null: Generator
    {
        $filter = $this->transformQuery($query);
        $this->logger->debug('find all ldap objects with ldap filter ['.$filter.'] on endpoint ['.$this->name.']', [
            'category' => get_class($this),
        ]);

        $i = 0;
        $result = $this->ldap->ldapSearch($this->basedn, $filter);
        foreach ($result->getEntries() as $object) {
            yield $this->build($object);
        }

        return $i;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiff(AttributeMapInterface $map, array $diff): array
    {
        $result = [];
        foreach ($diff as $attribute => $update) {
            switch ($update['action']) {
                case AttributeMapInterface::ACTION_REPLACE:
                    $result[] = [
                        'attrib' => $attribute,
                        'modtype' => LDAP_MODIFY_BATCH_REPLACE,
                        'values' => (array) $update['value'],
                    ];

                break;
                case AttributeMapInterface::ACTION_REMOVE:
                    $result[] = [
                        'attrib' => $attribute,
                        'modtype' => LDAP_MODIFY_BATCH_REMOVE_ALL,
                    ];

                break;
                case AttributeMapInterface::ACTION_ADD:
                    $result[] = [
                        'attrib' => $attribute,
                        'modtype' => LDAP_MODIFY_BATCH_ADD,
                        'values' => (array) $update['value'],
                    ];

                break;
                default:
                    throw new InvalidArgumentException('unknown diff action '.$update['action'].' given');
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(array $object, ?array $attributes = []): EndpointObjectInterface
    {
        $filter = $this->getFilterOne($object);
        $this->logger->debug('find ldap object with ldap filter ['.$filter.'] in ['.$this->basedn.'] on endpoint ['.$this->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        $result = $this->ldap->ldapSearch($this->basedn, $filter, $attributes);
        $count = $result->countEntries();

        if ($count > 1) {
            throw new Exception\ObjectMultipleFound('found more than one object with filter '.$filter);
        }
        if ($count === 0) {
            throw new Exception\ObjectNotFound('no object found with filter '.$filter);
        }

        return $this->build($this->prepareRawObject($result->getEntries()[0]));
    }

    /**
     * Prepare object.
     */
    protected function prepareRawObject(array $result): array
    {
        $object = [];
        foreach ($result as $key => $attr) {
            if ($key === 'dn') {
                $object['entrydn'] = $attr;
            } elseif (!is_int($key)) {
                if ($attr['count'] === 1) {
                    $object[$key] = $attr[0];
                } else {
                    $val = $attr;
                    unset($val['count']);
                    $object[$key] = $val;
                }
            }
        }

        return $object;
    }

    /**
     * Move ldap object.
     */
    protected function moveLdapObject(string $new_dn, string $current_dn, bool $simulate = false): bool
    {
        $this->logger->info('found object ['.$current_dn.'] but is not at the expected place ['.$new_dn.'], move object', [
            'category' => get_class($this),
        ]);

        $result = explode(',', $new_dn);
        $rdn = array_shift($result);
        $parent_dn = implode(',', $result);

        if ($simulate === false) {
            $this->ldap->rename($current_dn, $rdn, $parent_dn, true);
        }

        return true;
    }

    /**
     * Get dn.
     */
    protected function getDn(array $object, array $endpoint_object = []): string
    {
        if (isset($object['entrydn'])) {
            return $object['entrydn'];
        }
        if (isset($endpoint_object['entrydn'])) {
            return $endpoint_object['entrydn'];
        }

        throw new LdapEndpointException\NoEntryDn('no attribute entrydn found in data object');
    }
}
