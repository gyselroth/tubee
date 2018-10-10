<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Resource;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

abstract class AbstractResource implements ResourceInterface
{
    /**
     * Data.
     *
     * @var array
     */
    protected $resource = [];

    /**
     * {@inheritdoc}
     */
    public function getId(): ObjectId
    {
        return $this->resource['_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return $this->resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(): array
    {
        return array_diff_key($this->resource, array_flip([
            'created',
            'changed',
            'deleted',
            '_id',
            'version',
            'mandator',
            'datatype',
            'endpoint',
        ]));
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion(): int
    {
        return $this->resource['version'];
    }

    /**
     * {@inheritdoc}
     */
    public function getChanged(): ?UTCDateTime
    {
        return $this->resource['changed'];
    }

    /**
     * {@inheritdoc}
     */
    public function getCreated(): UTCDateTime
    {
        return $this->resource['created'];
    }

    /**
     * {@inheritdoc}
     */
    public function getDeleted(): ?UTCDateTime
    {
        return $this->resource['deleted'];
    }
}
