<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

trait LoggerTrait
{
    /**
     * Log getAll().
     */
    protected function logGetAll($query): void
    {
        $this->logger->debug('find all resources from endpoint [{identifier}] with query [{query}]', [
            'category' => get_class($this),
            'identifier' => $this->getIdentifier(),
            'query' => $query,
        ]);
    }

    /**
     * Log getOne().
     */
    protected function logGetOne($query): void
    {
        $this->logger->debug('find one resource from endpoint [{identifier}] with query [{query}]', [
            'category' => get_class($this),
            'identifier' => $this->getIdentifier(),
            'query' => $query,
        ]);
    }

    /**
     * Log create().
     */
    protected function logCreate($record): void
    {
        $this->logger->debug('create new resource on endpoint [{identifier}] from record [{record}]', [
            'category' => get_class($this),
            'identifier' => $this->getIdentifier(),
            'record' => $record,
        ]);
    }

    /**
     * Log change().
     */
    protected function logChange($resource, $record): void
    {
        $this->logger->debug('change resource [{resource}] on endpoint [{identifier}] from update [{record}]', [
            'category' => get_class($this),
            'identifier' => $this->getIdentifier(),
            'resource' => $resource,
            'record' => $record,
        ]);
    }

    /**
     * Log delete().
     */
    protected function logDelete($resource): void
    {
        $this->logger->debug('delete resource [{resource}] from endpoint [{identifier}]', [
            'category' => get_class($this),
            'identifier' => $this->getIdentifier(),
        ]);
    }
}
