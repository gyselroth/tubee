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
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\Database;
use Psr\Log\LoggerInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Resource\Factory as ResourceFactory;
use Tubee\Workflow\Factory as WorkflowFactory;

class Factory extends ResourceFactory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'endpoints';

    /**
     * Factory.
     *
     * @var WorkflowFactory
     */
    protected $workflow_factory;

    /**
     * Initialize.
     */
    public function __construct(Database $db, WorkflowFactory $workflow_factory, LoggerInterface $logger)
    {
        $this->workflow_factory = $workflow_factory;
        parent::__construct($db, $logger);
    }

    /**
     * Has endpoint.
     */
    public function has(DataTypeInterface $datatype, string $name): bool
    {
        return $this->db->{self::COLLECTION_NAME}->count([
            'name' => $name,
            'mandator' => $datatype->getMandator()->getName(),
            'datatype' => $datatype->getName(),
        ]) > 0;
    }

    /**
     * Get all.
     */
    public function getAll(DataTypeInterface $datatype, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $filter = [
            'mandator' => $datatype->getMandator()->getName(),
            'datatype' => $datatype->getName(),
        ];

        if (!empty($query)) {
            $filter = [
                '$and' => [$filter, $query],
            ];
        }

        return $this->getAllFrom($this->db->{self::COLLECTION_NAME}, $filter, $offset, $limit, $sort, function (array $resource) use ($datatype) {
            return $this->build($resource, $datatype);
        });
    }

    /**
     * Get one.
     */
    public function getOne(DataTypeInterface $datatype, string $name): EndpointInterface
    {
        $result = $this->db->{self::COLLECTION_NAME}->findOne([
            'name' => $name,
            'mandator' => $datatype->getMandator()->getName(),
            'datatype' => $datatype->getName(),
        ]);

        if ($result === null) {
            throw new Exception\NotFound('endpoint '.$name.' is not registered');
        }

        return $this->build($result, $datatype);
    }

    /**
     * Delete by name.
     */
    public function deleteOne(DataTypeInterface $datatype, string $name): bool
    {
        $resource = $this->getOne($datatype, $name);

        return $this->deleteFrom($this->db->{self::COLLECTION_NAME}, $resource->getId());
    }

    /**
     * Add.
     */
    public function add(DataTypeInterface $datatype, array $resource): ObjectIdInterface
    {
        $resource = Validator::validate($resource);

        if ($this->has($datatype, $resource['name'])) {
            throw new Exception\NotUnique('endpoint '.$resource['name'].' does already exists');
        }

        $resource['_id'] = new ObjectId();
        $endpoint = $this->build($resource, $datatype);
        $endpoint->setup();

        if ($resource['data']['type'] === EndpointInterface::TYPE_SOURCE) {
            $this->ensureIndex($datatype, $resource['data']['options']['import']);
        } else {
            $this->ensureIndex($datatype, array_keys($resource['data']['options']['filter_all']));
        }

        $resource['mandator'] = $datatype->getMandator()->getName();
        $resource['datatype'] = $datatype->getName();

        return $this->addTo($this->db->{self::COLLECTION_NAME}, $resource);
    }

    /**
     * Update.
     */
    public function update(EndpointInterface $resource, array $data): bool
    {
        $data['name'] = $resource->getName();
        $data = Validator::validate($data);

        return $this->updateIn($this->db->{self::COLLECTION_NAME}, $resource->getId(), $data);
    }

    /**
     * Change stream.
     */
    public function watch(DataTypeInterface $datatype, ?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        return $this->watchFrom($this->db->{self::COLLECTION_NAME}, $after, $existing, $query, function (array $resource) use ($datatype) {
            return $this->build($resource, $datatype);
        }, $offset, $limit, $sort);
    }

    /**
     * Build instance.
     */
    public function build(array $resource, DataTypeInterface $datatype)
    {
        $factory = $resource['data']['class'].'\\Factory';

        return $this->initResource($factory::build($resource, $datatype, $this->workflow_factory, $this->logger));
    }

    /**
     * Ensure indexes.
     */
    protected function ensureIndex(DataTypeInterface $datatype, array $fields): string
    {
        $list = iterator_to_array($this->db->{$datatype->getCollection()}->listIndexes());
        $keys = array_fill_keys($fields, 1);

        $this->logger->debug('verify if mongodb index exists for import attributes [{import}]', [
            'category' => get_class($this),
            'import' => $keys,
        ]);

        foreach ($list as $index) {
            if ($index['key'] === $keys) {
                $this->logger->debug('found existing mongodb index ['.$index['name'].']', [
                    'category' => get_class($this),
                    'fields' => $keys,
                ]);

                return $index['name'];
            }
        }

        $this->logger->info('create new mongodb index', [
            'category' => get_class($this),
        ]);

        return $this->db->{$datatype->getCollection()}->createIndex($keys);
    }
}
