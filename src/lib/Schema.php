<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use MongoDB\BSON\Binary;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Schema\Exception;
use Tubee\Schema\SchemaInterface;

class Schema implements SchemaInterface
{
    /**
     * Attribute schema.
     *
     * @var iterable
     */
    protected $schema = [];

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Init attribute schema.
     */
    public function __construct(array $schema, LoggerInterface $logger)
    {
        $this->schema = $schema;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchema(): array
    {
        return $this->schema;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
    {
        return array_keys($this->schema);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $data): bool
    {
        foreach ($this->schema as $attribute => $value) {
            if (isset($value['required']) && $value['required'] === true && !isset($data[$attribute])) {
                throw new Exception\AttributeNotFound('attribute '.$attribute.' is required');
            }

            if (!isset($data[$attribute])) {
                continue;
            }

            if (isset($value['type']) && $this->getType($data[$attribute]) !== $value['type']) {
                throw new Exception\AttributeInvalidType('attribute '.$attribute.' value is not of type '.$value['type']);
            }

            if (isset($value['require_regex'])) {
                $this->requireRegex($data[$attribute], $attribute, $value['require_regex']);
            }

            $this->logger->debug('schema attribute ['.$attribute.'] with value [{value}] is valid', [
                'category' => get_class($this),
                'value' => $data[$attribute],
            ]);
        }

        return true;
    }

    /**
     * Get type.
     */
    protected function getType($value): ?string
    {
        $map = [
            'string' => AttributeMapInterface::TYPE_STRING,
            'array' => AttributeMapInterface::TYPE_ARRAY,
            'integer' => AttributeMapInterface::TYPE_INT,
            'double' => AttributeMapInterface::TYPE_FLOAT,
            'boolean' => AttributeMapInterface::TYPE_BOOL,
            'null' => AttributeMapInterface::TYPE_NULL,
        ];

        $type = gettype($value);

        if (isset($map[$type])) {
            return $map[$type];
        }
        if ($value instanceof Binary) {
            return AttributeMapInterface::TYPE_BINARY;
        }

        return null;
    }

    /**
     * Require regex value.
     */
    protected function requireRegex($value, string $attribute, string $regex): bool
    {
        if (is_iterable($value)) {
            foreach ($value as $value_child) {
                if (!preg_match($regex, $value_child)) {
                    throw new Exception\AttributeRegexNotMatch('resolve attribute '.$attribute.' value does not match require_regex');
                }
            }
        } else {
            if (!preg_match($regex, $value)) {
                throw new Exception\AttributeRegexNotMatch('resolve attribute '.$attribute.' value does not match require_regex');
            }
        }

        return true;
    }
}
