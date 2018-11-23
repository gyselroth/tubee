<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Mandator;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\Database;
use Psr\Log\LoggerInterface;
use Tubee\DataType\Factory as DataTypeFactory;
use Tubee\Mandator;
use Tubee\Mandator\Validator as MandatorValidator;
use Tubee\Resource\Factory as ResourceFactory;

class Factory extends ResourceFactory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'mandators';

    /**
     * Datatype.
     *
     * @var DataTypeFactory
     */
    protected $datatype_factory;

    /**
     * Initialize.
     */
    public function __construct(Database $db, DataTypeFactory $datatype_factory, LoggerInterface $logger)
    {
        $this->datatype_factory = $datatype_factory;
        parent::__construct($db, $logger);
    }

    /**
     * Has mandator.
     */
    public function has(string $name): bool
    {
        return $this->db->{self::COLLECTION_NAME}->count(['name' => $name]) > 0;
    }

    /**
     * Get all.
     */
    public function getAll(?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        return $this->getAllFrom($this->db->{self::COLLECTION_NAME}, $query, $offset, $limit, $sort);
    }

    /**
     * Get mandator.
     */
    public function getOne(string $name): MandatorInterface
    {
        $result = $this->db->{self::COLLECTION_NAME}->findOne(['name' => $name]);

        if ($result === null) {
            throw new Exception\NotFound('mandator '.$name.' is not registered');
        }

        return $this->build($result);
    }

    /**
     * Delete by name.
     */
    public function deleteOne(string $name): bool
    {
        $resource = $this->getOne($name);

        return $this->deleteFrom($this->db->{self::COLLECTION_NAME}, $resource->getId());
    }

    /**
     * Add mandator.
     */
    public function add(array $resource): ObjectIdInterface
    {
        MandatorValidator::validate($resource);

        if ($this->has($resource['name'])) {
            throw new Exception\NotUnique('mandator '.$resource['name'].' does already exists');
        }

        return $this->addTo($this->db->{self::COLLECTION_NAME}, $resource);
    }

    /**
     * Change stream.
     */
    public function watch(?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        return $this->watchFrom($this->db->{self::COLLECTION_NAME}, $after, $existing, $query, null, $offset, $limit, $sort);
    }

    /**
     * Build instance.
     */
    public function build(array $resource): MandatorInterface
    {
        return $this->initResource(new Mandator($resource['name'], $this, $this->datatype_factory, $resource));
    }
}
