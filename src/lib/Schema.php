<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use InvalidArgumentException;
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
    public function __construct(array $schema = [], LoggerInterface $logger)
    {
        $this->validateSchema($schema);
        $this->logger = $logger;
    }

    /**
     * Validate schema.
     */
    public function validateSchema(array $schema): self
    {
        foreach ($schema as $attribute => $definition) {
            if (!is_array($definition)) {
                throw new InvalidArgumentException('schema attribute '.$attribute.' definition must be an array');
            }

            $this->addAttribute($attribute, $definition);
        }

        return $this;
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
    public function validate(Iterable $data): bool
    {
        $result = [];
        foreach ($this->schema as $attribute => $value) {
            if ($value['required'] === true && !isset($data[$attribute])) {
                throw new Exception\AttributeNotFound('attribute '.$attribute.' is required');
            }

            if (isset($value['type']) && gettype($data[$attribute]) !== $value['type']) {
                throw new Exception\AttributeInvalidType('attribute '.$attribute.' value is not of type '.$value['type']);
            }

            if (isset($value['require_regex'])) {
                $this->requireRegex($data[$attribute], $attribute, $value['require_regex']);
            }

            $this->logger->debug('schema attribute ['.$attribute.'] to [<'.$value['type'].'> {value}]', [
                'category' => get_class($this),
                'value' => $result[$attr],
            ]);
        }

        return true;
    }

    /**
     * Add attribute.
     */
    protected function addAttribute(string $name, array $schema): self
    {
        $default = [
            'required' => false,
        ];

        foreach ($schema as $option => $definition) {
            switch ($option) {
                case 'description':
                case 'label':
                case 'type':
                case 'require_regex':
                    if (!is_string($definition)) {
                        throw new InvalidArgumentException('schema attribute '.$name.' has an invalid option '.$option.', value must be of type string');
                    }

                    $default[$option] = $definition;

                break;
                case 'required':
                    if (!is_bool($definition)) {
                        throw new InvalidArgumentException('schema attribute '.$name.' has an invalid option '.$option.', value must be of type boolean');
                    }

                    $default[$option] = $definition;

                break;
                default:
                    throw new InvalidArgumentException('schema attribute '.$name.' has an invalid option '.$option);
            }
        }

        $this->schema[$name] = $default;

        return $this;
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
