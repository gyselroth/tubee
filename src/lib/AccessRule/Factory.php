<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\AccessRule;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use Tubee\AccessRule;
use Tubee\Resource\Factory as ResourceFactory;

class Factory extends ResourceFactory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'access_rules';

    /**
     * Has resource.
     */
    public function has(string $name): bool
    {
        return $this->db->{self::COLLECTION_NAME}->count(['name' => $name]) > 0;
    }

    /**
     * Get resources.
     */
    public function getAll(?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        return $this->getAllFrom($this->db->{self::COLLECTION_NAME}, $query, $offset, $limit, $sort);
    }

    /**
     * Get resource.
     */
    public function getOne(string $name): AccessRuleInterface
    {
        $result = $this->db->{self::COLLECTION_NAME}->findOne(['name' => $name]);

        if ($result === null) {
            throw new Exception\NotFound('access rule '.$name.' is not registered');
        }

        return $this->build($result);
    }

    /**
     * Delete by name.
     */
    public function deleteOne(string $name): bool
    {
        $resource = $this->getOne($name);
        $this->deleteFrom($this->db->{self::COLLECTION_NAME}, $resource->getId());

        return true;
    }

    /**
     * Add resource.
     */
    public function add(array $resource): ObjectIdInterface
    {
        $resource = Validator::validate($resource);

        if ($this->has($resource['name'])) {
            throw new Exception\NotUnique('access rule '.$resource['name'].' does already exists');
        }

        return $this->addTo($this->db->{self::COLLECTION_NAME}, $resource);
    }

    /**
     * Update.
     */
    public function update(AccessRuleInterface $resource, array $data): bool
    {
        $data = Validator::validate($data);

        return $this->updateIn($this->db->{self::COLLECTION_NAME}, $resource->getId(), $data);
    }

    /**
     * Change stream.
     */
    public function watch(?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        return $this->watchFrom($this->db->{self::COLLECTION_NAME}, $after, $existing, $query, $offset, $limit, $sort);
    }

    /**
     * Build instance.
     */
    public function build(array $resource): AccessRuleInterface
    {
        return $this->initResource(new AccessRule($resource));
    }
}
