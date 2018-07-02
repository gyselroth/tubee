<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Psr\Http\Message\ServerRequestInterface;
use Tubee\DataObject\AttributeDecorator;
use Tubee\DataObject\DataObjectInterface;
use Tubee\DataType\DataTypeInterface;

class DataObject implements DataObjectInterface
{
    /**
     * Object id.
     *
     * @var ObjectId
     */
    protected $_id;

    /**
     * Created.
     *
     * @var UTCDateTime
     */
    protected $created;

    /**
     * Changed.
     *
     * @var UTCDateTime
     */
    protected $changed;

    /**
     * Disabled (Deleted).
     *
     * @var UTCDateTime
     */
    protected $deleted;

    /**
     * Object version.
     *
     * @var int
     */
    protected $version = 1;

    /**
     * Data.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Endpoints.
     *
     * @var array
     */
    protected $endpoints = [];

    /**
     * Datatype.
     *
     * @var DataTypeInterface
     */
    protected $datatype;

    /**
     * Data object.
     */
    public function __construct(array $data, DataTypeInterface $datatype)
    {
        $this->datatype = $datatype;
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): ObjectId
    {
        return $this->_id;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return [
            '_id' => $this->_id,
            'created' => $this->created,
            'changed' => $this->changed,
            'deleted' => $this->deleted,
            'version' => $this->version,
            'data' => $this->data,
            'endpoints' => $this->endpoints,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function decorateFromRequest(ServerRequestInterface $request): array
    {
        return AttributeDecorator::decorateFromRequest($this, $request);
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(array $attributes): array
    {
        return AttributeDecorator::decorate($this, $attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function getHistory(?int $offset = null, ?int $limit = null): Iterable
    {
        return $this->datatype->getObjectHistory($this->_id);
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * {@inheritdoc}
     */
    public function getChanged(): ?UTCDateTime
    {
        return $this->changed;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreated(): UTCDateTime
    {
        return $this->created;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeleted(): ?UTCDateTime
    {
        return $this->deleted;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataType(): DataTypeInterface
    {
        return $this->datatype;
    }

    /**
     * Get endpoints.
     */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }
}
