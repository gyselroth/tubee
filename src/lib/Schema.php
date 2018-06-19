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
    public function __construct(Iterable $schema = [], LoggerInterface $logger)
    {
        $this->schema = $schema;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getMap(): Iterable
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
    public function validate(Iterable $data): array
    {
        $result = [];
        foreach ($this->schema as $attr => $value) {
            if (!is_array($value)) {
                throw new InvalidArgumentException('attribute '.$attr.' definiton must be an array');
            }

            if (isset($value['required']) && $value['required'] === true && !isset($data[$attr])) {
                throw new Exception\AttributeNotFound('attribute '.$attribute.' is required');
            }

            continue;
            if (isset($value['type']) && gettype($data[$attr]) !== $value['type']) {
                throw new Exception\AttributeInvalidType('attribute '.$attribute.' value is not of type '.$value['type']);
            }

            if (isset($value['require_regex'])) {
                $this->requireRegex($data[$attr], $attr, $value['require_regex']);
            }

            $this->logger->debug('schema attribute ['.$attr.'] to [<'.$value['type'].'> {value}]', [
                'category' => get_class($this),
                'value' => $result[$attr],
            ]);
        }

        return bool;
    }

    /**
     * Require regex value.
     *
     * @param iterable|string $value
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
