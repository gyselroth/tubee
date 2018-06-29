<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface as Logger;
use Tubee\DataType\DataTypeInterface;
use Tubee\Mandator\Exception;
use Tubee\Mandator\MandatorInterface;

class Mandator implements MandatorInterface
{
    /**
     * Name.
     *
     * @var string
     */
    protected $name;

    /**
     * Manager.
     *
     * @var Manager
     */
    protected $manager;

    /**
     * Logger.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Type.
     *
     * @var array
     */
    protected $datatypes = [];

    /**
     * Initialize.
     */
    public function __construct(string $name, Manager $manager, Logger $logger)
    {
        $this->name = $name;
        $this->manager = $manager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function hasDataType(string $name): bool
    {
        return isset($this->datatypes[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function injectDataType(DataTypeInterface $datatype, string $name): MandatorInterface
    {
        $this->logger->debug('inject datatype ['.$name.'] of type ['.get_class($datatype).']', [
            'category' => get_class($this),
        ]);

        if ($this->hasDataType($name)) {
            throw new Exception\DataTypeNotUnique('datatype '.$name.' is already registered');
        }

        $this->datatypes[$name] = $datatype;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataType(string $name): DataTypeInterface
    {
        if (!$this->hasDataType($name)) {
            throw new Exception\DataTypeNotFound('datatype '.$name.' is not registered');
        }

        return $this->datatypes[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getDataTypes(Iterable $datatypes = []): array
    {
        if (count($datatypes) === 0) {
            return $this->datatypes;
        }
        $list = [];
        foreach ($datatypes as $name) {
            if (!$this->hasDataType($name)) {
                throw new Exception\DataTypeNotFound('datatype '.$name.' is not registered');
            }
            $list[$name] = $this->datatypes[$name];
        }

        return $list;
    }

    /**
     * Decorate.
     */
    public function decorate(ServerRequestInterface $request): array
    {
        return [
            '_links' => [
                'self' => ['href' => (string) $request->getUri()],
            ],
            'kind' => 'Mandator',
            'name' => $this->name,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }
}
