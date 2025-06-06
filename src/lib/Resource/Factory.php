<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Resource;

use Closure;
use Garden\Schema\ArrayRefLookup;
use Garden\Schema\Schema;
use Generator;
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Database;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Yaml\Yaml;

class Factory
{
    /**
     * OpenAPI.
     */
    const SPEC = __DIR__.'/../Rest/v1/openapi.yml';

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Database.
     *
     * @var Database
     */
    protected $db;

    /**
     * Cache.
     *
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Initialize.
     */
    public function __construct(LoggerInterface $logger, CacheInterface $cache)
    {
        $this->logger = $logger;
        $this->cache = $cache;
    }

    /**
     * Get resource schema.
     */
    public function getSchema(string $kind): Schema
    {
        if ($this->cache->has($kind)) {
            $this->logger->debug('found resource kind ['.$kind.'] in cache', [
                'category' => get_class($this),
            ]);

            return $this->cache->get($kind);
        }

        $spec = $this->loadSpecification();
        $key = 'core.v1.'.$kind;

        if (!isset($spec['components']['schemas'][$key])) {
            throw new InvalidArgumentException('provided resource kind is invalid');
        }

        $schema = new Schema($spec['components']['schemas'][$key]);
        $schema->setRefLookup(new ArrayRefLookup($spec));
        $schema->setFlags(Schema::VALIDATE_EXTRA_PROPERTY_EXCEPTION);
        $this->cache->set($kind, $schema);

        return $schema;
    }

    /**
     * Validate resource.
     */
    public function validate(array $resource): array
    {
        $this->logger->debug('validate resource [{resource}] against schema', [
            'category' => get_class($this),
            'resource' => $resource,
        ]);

        $resource = $this->getSchema($resource['kind'])->validate($resource, [
            'request' => true,
        ]);

        $this->logger->debug('clean resource [{resource}]', [
            'category' => get_class($this),
            'resource' => $resource,
        ]);

        return $resource;
    }

    /**
     * Add resource.
     */
    public function addTo(Collection $collection, array $resource, bool $simulate = false): ObjectIdInterface
    {
        $ts = new UTCDateTime();
        $resource += [
            'created' => $ts,
            'changed' => $ts,
            'version' => 1,
        ];

        $this->logger->debug('add new resource to ['.$collection->getCollectionName().']', [
            'category' => get_class($this),
            'resource' => $resource,
        ]);

        if ($simulate === true) {
            return new ObjectId();
        }

        $result = $collection->insertOne($resource);
        $id = $result->getInsertedId();

        $this->logger->info('created new resource ['.$id.'] in ['.$collection->getCollectionName().']', [
            'category' => get_class($this),
        ]);

        return $id;
    }

    /**
     * Update resource.
     */
    public function updateIn(Collection $collection, ResourceInterface $resource, array $update, bool $simulate = false): bool
    {
        $this->logger->debug('update resource ['.$resource->getId().'] in ['.$collection->getCollectionName().']', [
            'category' => get_class($this),
            'update' => $update,
        ]);

        $op = [
            '$set' => $update,
        ];

        if (!isset($update['data']) || $resource->getData() === $update['data']) {
            $this->logger->info('resource ['.$resource->getId().'] version ['.$resource->getVersion().'] in ['.$collection->getCollectionName().'] is already up2date', [
                'category' => get_class($this),
            ]);
        } else {
            $this->logger->info('add new history record for resource ['.$resource->getId().'] in ['.$collection->getCollectionName().']', [
                'category' => get_class($this),
            ]);

            $op['$set']['changed'] = new UTCDateTime();
            $op += [
                '$addToSet' => ['history' => array_intersect_key($resource->toArray(), array_flip(['data', 'version', 'changed', 'description', 'endpoints']))],
                '$inc' => ['version' => 1],
            ];
        }

        if ($simulate === true) {
            return true;
        }

        $result = $collection->updateOne(['_id' => $resource->getId()], $op);

        $this->logger->info('updated [{modified}/{match}] resource ['.$resource->getId().'] in ['.$collection->getCollectionName().']', [
            'category' => get_class($this),
            'match' => $result->getMatchedCount(),
            'modified' => $result->getModifiedCount(),
        ]);

        return true;
    }

    /**
     * Delete resource.
     */
    public function deleteFrom(Collection $collection, ObjectIdInterface $id, bool $simulate = false): bool
    {
        $this->logger->info('delete resource ['.$id.'] from ['.$collection->getCollectionName().']', [
            'category' => get_class($this),
        ]);

        if ($simulate === true) {
            return true;
        }

        $result = $collection->deleteOne(['_id' => $id]);

        $this->logger->info('removed resource ['.$id.'] in ['.$collection->getCollectionName().']', [
            'category' => get_class($this),
        ]);

        return true;
    }

    /**
     * Get all.
     */
    public function getAllFrom(Collection $collection, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null, ?Closure $build = null): Generator
    {
        $total = 0;

        if ($limit !== null) {
            $total = $collection->count($query);
        }

        $offset = $this->calcOffset($total, $offset);
        $result = $collection->find($query, [
            'projection' => ['history' => 0],
            'skip' => $offset,
            'limit' => $limit,
            'sort' => $sort,
        ]);

        foreach ($result as $resource) {
            $result = $build->call($this, $resource);
            if ($result !== null) {
                yield (string) $resource['_id'] => $result;
            }
        }

        return $total;
    }

    /**
     * Change stream.
     */
    public function watchFrom(Collection $collection, ?ObjectIdInterface $after = null, bool $existing = true, ?array $query = [], ?Closure $build = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $pipeline = $query;
        if (!empty($pipeline)) {
            $pipeline = [['$match' => $this->prepareQuery($query)]];
        }

        $stream = $collection->watch($pipeline, [
            'resumeAfter' => $after,
            'fullDocument' => 'updateLookup',
        ]);

        if ($existing === true) {
            $total = $collection->count($query);

            if ($offset !== null && $total === 0) {
                $offset = null;
            } elseif ($offset < 0 && $total >= $offset * -1) {
                $offset = $total + $offset;
            }

            $result = $collection->find($query, [
                'projection' => ['history' => 0],
                'skip' => $offset,
                'limit' => $limit,
                'sort' => $sort,
            ]);

            foreach ($result as $resource) {
                $bound = $build->call($this, $resource);

                if ($bound === null) {
                    continue;
                }

                yield (string) $resource['_id'] => [
                    'insert',
                    $bound,
                ];
            }
        }

        for ($stream->rewind(); true; $stream->next()) {
            if (!$stream->valid()) {
                continue;
            }

            $event = $stream->current();
            $bound = $build->call($this, $event['fullDocument']);

            if ($bound === null) {
                continue;
            }

            yield (string) $event['fullDocument']['_id'] => [
                $event['operationType'],
                $bound,
            ];
        }
    }

    /**
     * Build.
     */
    public function initResource(ResourceInterface $resource)
    {
        $this->logger->debug('initialized resource ['.$resource->getId().'] as ['.get_class($resource).']', [
            'category' => get_class($this),
        ]);

        return $resource;
    }

    /**
     * Load openapi specs.
     */
    protected function loadSpecification(): array
    {
        if ($this->cache->has('openapi')) {
            return $this->cache->get('openapi');
        }

        $data = Yaml::parseFile(self::SPEC);
        $this->cache->set('openapi', $data);

        return $data;
    }

    /**
     * Add fullDocument prefix to keys.
     */
    protected function prepareQuery(array $query): array
    {
        $new = [];
        foreach ($query as $key => $value) {
            switch ($key) {
                case '$and':
                case '$or':
                    foreach ($value as $sub_key => $sub) {
                        $new[$key][$sub_key] = $this->prepareQuery($sub);
                    }

                break;
                default:
                    $new['fullDocument.'.$key] = $value;
            }
        }

        return $new;
    }

    /**
     * Calculate offset.
     */
    protected function calcOffset(int $total, ?int $offset = null): ?int
    {
        if ($offset !== null && $total === 0) {
            $offset = 0;
        } elseif ($offset < 0 && $total >= $offset * -1) {
            $offset = $total + $offset;
        } elseif ($offset < 0) {
            $offset = 0;
        }

        return $offset;
    }
}
