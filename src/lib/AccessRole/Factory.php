<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\AccessRole;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use Tubee\AccessRole;
use Tubee\Resource\Factory as ResourceFactory;

class Factory extends ResourceFactory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'access_roles';

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
    public function getOne(string $name): AccessRoleInterface
    {
        $result = $this->db->{self::COLLECTION_NAME}->findOne([
            'name' => $name,
        ], [
            'projection' => ['history' => 0],
        ]);

        if ($result === null) {
            throw new Exception\NotFound('access role '.$name.' is not registered');
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
        $resource = $this->validate($resource);

        if ($this->has($resource['name'])) {
            throw new Exception\NotUnique('access role '.$resource['name'].' does already exists');
        }

        return $this->addTo($this->db->{self::COLLECTION_NAME}, $resource);
    }

    /**
     * Update.
     */
    public function update(AccessRoleInterface $resource, array $data): bool
    {
        $data['name'] = $resource->getName();
        $data = $this->validate($data);

        return $this->updateIn($this->db->{self::COLLECTION_NAME}, $resource, $data);
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
    public function build(array $resource): AccessRoleInterface
    {
        return $this->initResource(new AccessRole($resource));
    }
}
