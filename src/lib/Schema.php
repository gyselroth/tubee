<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use Psr\Log\LoggerInterface;
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
        $default = [
            'label' => null,
            'required' => false,
            'type' => null,
            'require_regex' => null,
        ];

        $result = [];
        foreach ($this->schema as $attribute => $schema) {
            $result[$attribute] = array_merge($default, $schema);
        }

        return $result;
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

            if ($value['type'] !== null && gettype($data[$attribute]) !== $value['type']) {
                throw new Exception\AttributeInvalidType('attribute '.$attribute.' value is not of type '.$value['type']);
            }

            if (isset($value['require_regex'])) {
                $this->requireRegex($data[$attribute], $attribute, $value['require_regex']);
            }

            $this->logger->debug('schema attribute ['.$attribute.'] to [<'.$value['type'].'> {value}]', [
                'category' => get_class($this),
                'value' => $data[$attribute],
            ]);
        }

        return true;
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
