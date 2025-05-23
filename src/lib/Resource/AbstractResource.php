<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Resource;

use MongoDB\BSON\ObjectIdInterface;
use MongoDB\BSON\UTCDateTimeInterface;

abstract class AbstractResource implements ResourceInterface
{
    /**
     * Kind.
     */
    public const KIND = 'Resource';

    /**
     * Data.
     *
     * @var array
     */
    protected $resource = [];

    /**
     * {@inheritdoc}
     */
    public function getId(): ObjectIdInterface
    {
        return $this->resource['_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function getKind(): string
    {
        if (isset($this->resource['kind'])) {
            return $this->resource['kind'];
        }

        return static::KIND;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        if (isset($this->resource['name'])) {
            return $this->resource['name'];
        }

        return (string) $this->resource['_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        if (!isset($this->resource['name'])) {
            $this->resource['name'] = (string) $this->resource['_id'];
        }

        return $this->resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(): array
    {
        if (!isset($this->resource['data'])) {
            return [];
        }

        return $this->resource['data'];
    }

    /**
     * {@inheritdoc}
     */
    public function getSecrets(): array
    {
        if (!isset($this->resource['secrets'])) {
            return [];
        }

        return $this->resource['secrets'];
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
    public function getChanged(): ?UTCDateTimeInterface
    {
        return $this->resource['changed'];
    }

    /**
     * {@inheritdoc}
     */
    public function getCreated(): UTCDateTimeInterface
    {
        return $this->resource['created'];
    }

    /**
     * {@inheritdoc}
     */
    public function getDeleted(): ?UTCDateTimeInterface
    {
        return $this->resource['deleted'];
    }
}
