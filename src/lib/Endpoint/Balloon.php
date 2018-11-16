<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Generator;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\EndpointObject\EndpointObjectInterface;

class Balloon extends AbstractRest
{
    /**
     * {@inheritdoc}
     */
    public function change(AttributeMapInterface $map, array $diff, array $object, array $endpoint_object, bool $simulate = false): ?string
    {
        $this->logger->info('update balloon object on endpoint ['.$this->getIdentifier().'] using ['.$this->update_method.'] to ['.$this->client->getConfig('base_uri').'/'.$this->getResourceId($endpoint_object).']', [
            'category' => get_class($this),
        ]);

        if ($simulate === false) {
            $result = $this->client->patch('/'.$this->getResourceId($endpoint_object), [
                'json' => $diff,
            ]);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function transformQuery(?array $query = null)
    {
        $result = null;
        if ($this->filter_all !== null) {
            $result = '{"query":'.stripslashes($this->filter_all).'}';
        }

        if (!empty($query)) {
            if ($this->filter_all === null) {
                $result = '{"query":'.json_encode($query).'}';
            } else {
                $result = '{"query":{"$and":['.stripslashes($this->filter_all).', '.json_encode($query).']}}';
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(?array $query = null): Generator
    {
        $this->logger->debug('find all balloon objects using ['.$this->client->getConfig('base_uri').']', [
            'category' => get_class($this),
        ]);

        $options = $this->getRequestOptions();
        $options['query'] = $this->transformQuery($query);

        if ($options['query'] !== null) {
            $options['headers']['Content-Type'] = 'application/json';
        }

        $i = 0;
        $response = $this->client->get('', $options);
        $data = $this->getResponse($response);

        foreach ($data as $object) {
            yield $this->build($object);
        }

        return $i;
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(array $object, ?array $attributes = []): EndpointObjectInterface
    {
        $filter = $this->getFilterOne($object);
        $this->logger->debug('find rest resource with filter ['.$filter.'] in endpoint ['.$this->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        $options = $this->getRequestOptions();
        $options['query'] = '{"query":'.stripslashes($filter).'}';
        $options['headers']['Content-Type'] = 'application/json';
        $result = $this->client->get('', $options);
        $data = $this->getResponse($result);

        if (count($data) > 1) {
            throw new Exception\ObjectMultipleFound('found more than one object with filter '.$filter);
        }
        if (count($data) === 0) {
            throw new Exception\ObjectNotFound('no object found with filter '.$filter);
        }

        return $this->build(array_shift($data));
    }
}
