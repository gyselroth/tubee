<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\DataType;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\Database;
use Psr\Log\LoggerInterface;
use Tubee\DataObject\Factory as DataObjectFactory;
use Tubee\DataType;
use Tubee\Endpoint\Factory as EndpointFactory;
use Tubee\Mandator\MandatorInterface;
use Tubee\Resource\Factory as ResourceFactory;
use Tubee\Schema;

class Factory extends ResourceFactory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'datatypes';

    /**
     * Object factory.
     *
     * @var DataObjectFactory
     */
    protected $object_factory;

    /**
     * Endpoint.
     *
     * @var EndpointFactory
     */
    protected $endpoint_factory;

    /**
     * Initialize.
     */
    public function __construct(Database $db, EndpointFactory $endpoint_factory, DataObjectFactory $object_factory, LoggerInterface $logger)
    {
        $this->endpoint_factory = $endpoint_factory;
        $this->object_factory = $object_factory;
        parent::__construct($db, $logger);
    }

    /**
     * Has mandator.
     */
    public function has(MandatorInterface $mandator, string $name): bool
    {
        return $this->db->{self::COLLECTION_NAME}->count([
            'name' => $name,
            'mandator' => $mandator->getName(),
        ]) > 0;
    }

    /**
     * Get all.
     */
    public function getAll(MandatorInterface $mandator, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $filter = [
            'mandator' => $mandator->getName(),
        ];

        if (!empty($query)) {
            $filter = [
                '$and' => [$filter, $query],
            ];
        }

        return $this->getAllFrom($this->db->{self::COLLECTION_NAME}, $filter, $offset, $limit, $sort, function (array $resource) use ($mandator) {
            return $this->build($resource, $mandator);
        });
    }

    /**
     * Get one.
     */
    public function getOne(MandatorInterface $mandator, string $name): DataTypeInterface
    {
        $result = $this->db->{self::COLLECTION_NAME}->findOne([
            'name' => $name,
            'mandator' => $mandator->getName(),
        ]);

        if ($result === null) {
            throw new Exception\NotFound('datatype '.$name.' is not registered');
        }

        return $this->build($result, $mandator);
    }

    /**
     * Delete by name.
     */
    public function deleteOne(MandatorInterface $mandator, string $name): bool
    {
        $resource = $this->getOne($mandator, $name);

        return $this->deleteFrom($this->db->{self::COLLECTION_NAME}, $resource->getId());
    }

    /**
     * Add mandator.
     */
    public function add(MandatorInterface $mandator, array $resource): ObjectIdInterface
    {
        $resource = Validator::validate($resource);

        if ($this->has($mandator, $resource['name'])) {
            throw new Exception\NotUnique('datatype '.$resource['name'].' does already exists');
        }

        $resource['mandator'] = $mandator->getName();

        return $this->addTo($this->db->{self::COLLECTION_NAME}, $resource);
    }

    /**
     * Update.
     */
    public function update(DataTypeInterface $resource, array $data): bool
    {
        $data['name'] = $resource->getName();
        $data = Validator::validate($data);

        return $this->updateIn($this->db->{self::COLLECTION_NAME}, $resource, $data);
    }

    /**
     * Change stream.
     */
    public function watch(MandatorInterface $mandator, ?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        return $this->watchFrom($this->db->{self::COLLECTION_NAME}, $after, $existing, $query, function (array $resource) use ($mandator) {
            return $this->build($resource, $mandator);
        }, $offset, $limit, $sort);
    }

    /**
     * Build instance.
     */
    public function build(array $resource, MandatorInterface $mandator): DataTypeInterface
    {
        $schema = new Schema($resource['data']['schema'], $this->logger);

        return $this->initResource(new DataType($resource['name'], $mandator, $this->endpoint_factory, $this->object_factory, $schema, $this->logger, $resource));
    }
}
