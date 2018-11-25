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
use MongoDB\BSON\Binary;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTimeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\AttributeMap\Exception;

class AttributeMap implements AttributeMapInterface
{
    /**
     * Attribute map.
     *
     * @var iterable
     */
    protected $map = [];

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Expression language.
     *
     * @var ExpressionLanguage
     */
    protected $expression;

    /**
     * Init attribute map.
     */
    public function __construct(array $map = [], ExpressionLanguage $expression, LoggerInterface $logger)
    {
        $this->map = $map;
        $this->logger = $logger;
        $this->expression = $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function getMap(): Iterable
    {
        return $this->map;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
    {
        return array_keys($this->map);
    }

    /**
     * {@inheritdoc}
     */
    public function map(array $data, UTCDateTimeInterface $ts): array
    {
        $result = [];
        foreach ($this->map as $attr => $value) {
            if (isset($attrv)) {
                unset($attrv);
            }

            if (isset($value['ensure'])) {
                if ($value['ensure'] === AttributeMapInterface::ENSURE_MERGE && isset($value['type']) && $value['type'] !== AttributeMapInterface::TYPE_ARRAY) {
                    throw new InvalidArgumentException('attribute '.$attr.' ensure is set to merge but type is not an array');
                }

                if ($value['ensure'] === AttributeMapInterface::ENSURE_ABSENT) {
                    continue;
                }
            }

            /*$attrv = $this->resolveValue($attr, $value, $data, $ts);
            $attrv = $this->transformAttribute($attr, $value, $data, $attrv);

            if (isset($value['type'])) {
                $attrv = $this->serializeClass($attr, $attrv, $value['type']);
            }

            if ($this->requireAttribute($attr, $value, $attrv) === null) {
                continue;
            }

            if (isset($value['type'])) {
                $attrv = $this->convert($attrv, $attr, $value['type']);
            }*/

            $result[$attr] = $this->mapField($attr, $value, $data, $ts);

            $this->logger->debug('mapped attribute ['.$attr.'] to [<'.gettype($result[$attr]).'> {value}]', [
                'category' => get_class($this),
                'value' => $result[$attr],
            ]);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiff(array $object, array $endpoint_object): array
    {
        $diff = [];
        foreach ($this->map as $attr => $value) {
            if (isset($value['ensure'])) {
                $exists = isset($endpoint_object[$attr]);

                if ($value['ensure'] === AttributeMapInterface::ENSURE_EXISTS && ($exists === true || !isset($object[$attr]))) {
                    continue;
                }
                if (($value['ensure'] === AttributeMapInterface::ENSURE_LAST || $value['ensure'] === AttributeMapInterface::ENSURE_EXISTS) && isset($object[$attr])) {
                    if ($exists && is_array($object[$attr]) && is_array($endpoint_object[$attr]) && Helper::arrayEqual($endpoint_object[$attr], $object[$attr])) {
                        continue;
                    }
                    if ($exists && $object[$attr] === $endpoint_object[$attr]) {
                        continue;
                    }

                    $diff[$attr] = [
                        'action' => AttributeMapInterface::ACTION_REPLACE,
                        'value' => $object[$attr],
                    ];
                } elseif ($value['ensure'] === AttributeMapInterface::ENSURE_ABSENT && isset($endpoint_object[$attr]) || isset($endpoint_object[$attr]) && !isset($object[$attr]) && $value['ensure'] !== AttributeMapInterface::ENSURE_MERGE) {
                    $diff[$attr] = [
                        'action' => AttributeMapInterface::ACTION_REMOVE,
                    ];
                } elseif ($value['ensure'] === AttributeMapInterface::ENSURE_MERGE && isset($object[$attr])) {
                    $new_values = [];

                    foreach ($object[$attr] as $val) {
                        if (!$exists) {
                            $new_values[] = $val;
                        } elseif (is_array($endpoint_object[$attr]) && in_array($val, $endpoint_object[$attr]) || $val === $endpoint_object[$attr]) {
                            continue;
                        } else {
                            $new_values[] = $val;
                        }
                    }

                    if (!empty($new_values)) {
                        $diff[$attr] = [
                            'action' => AttributeMapInterface::ACTION_ADD,
                            'value' => $new_values,
                        ];
                    }
                }
            }
        }

        return $diff;
    }

    protected function mapField($attr, $value, $data, $ts)
    {
        $attrv = $this->resolveValue($attr, $value, $data, $ts);
        $attrv = $this->transformAttribute($attr, $value, $data, $attrv);

        if (isset($value['type'])) {
            $attrv = $this->serializeClass($attr, $attrv, $value['type']);
        }

        if ($this->requireAttribute($attr, $value, $attrv) === null) {
            return;
        }

        if (isset($value['type'])) {
            $attrv = $this->convert($attrv, $attr, $value['type']);
        }

        if (isset($value['unwind'])) {
            foreach ($attrv as $key => $element) {
                $attrv[$key] = $this->mapField($attr, $value['unwind'], $element, $ts);
            }
        }

        return $attrv;
    }

    /**
     * Check if attribute is required.
     */
    protected function requireAttribute(string $attr, array $value, $attrv)
    {
        if ($attrv === null || is_string($attrv) && strlen($attrv) === 0 || is_array($attrv) && count($attrv) === 0) {
            if (isset($value['required']) && $value['required'] === false) {
                $this->logger->debug('found attribute ['.$attr.'] but source attribute is empty, remove attribute from mapping', [
                     'category' => get_class($this),
                ]);

                return null;
            }

            throw new Exception\AttributeNotResolvable('required attribute '.$attr.' could not be resolved');
        }

        return $attrv;
    }

    /**
     * Transform attribute.
     */
    protected function transformAttribute(string $attr, array $value, array $data, $attrv)
    {
        if ($attrv === null) {
            return null;
        }

        if (isset($value['type']) && $value['type'] !== AttributeMapInterface::TYPE_ARRAY && is_array($attrv)) {
            $attrv = $this->firstArrayElement($attrv, $attr);
        }

        if (isset($value['rewrite'])) {
            $attrv = $this->rewrite($attrv, $attr, $value['rewrite'], $data);
        }

        if (isset($value['require_regex'])) {
            $this->requireRegex($attrv, $attr, $value['require_regex']);
        }

        return $attrv;
    }

    /**
     * Convert to class.
     */
    protected function serializeClass(string $attr, $attrv, string $type)
    {
        if ($attrv === null || !in_array($type, AttributeMapInterface::SERIALIZABLE_TYPES)) {
            return $attrv;
        }

        $args = [];

        if ($attrv !== null) {
            if ($attrv instanceof $type) {
                return $attrv;
            }

            $args[] = $attrv;
        }

        if ($type === Binary::class) {
            $args[] = Binary::TYPE_GENERIC;
        }

        return new $type(...$args);
    }

    /**
     * Check if attribute is required.
     */
    protected function resolveValue(string $attr, array $value, array $data, UTCDateTimeInterface $ts)
    {
        $result = null;

        if (isset($value['value'])) {
            $result = $value['value'];
        }

        try {
            if (isset($value['from'])) {
                $result = Helper::getArrayValue($data, $value['from']);
            }
        } catch (\Exception $e) {
            $this->logger->warning('failed to resolve value of attribute ['.$attr.'] from ['.$value['from'].']', [
                'category' => get_class($this),
                'exception' => $e,
            ]);
        }

        try {
            if (isset($value['script'])) {
                $result = $this->expression->evaluate($value['script'], $data);
            }
        } catch (\Exception $e) {
            $this->logger->warning('failed to execute script ['.$value['script'].'] of attribute ['.$attr.']', [
                'category' => get_class($this),
                'exception' => $e,
            ]);
        }

        //if (isset($data[$attr])) {
        //    return $data[$attr];
        //}

        return $result;
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

    /**
     * Shift first array element.
     */
    protected function firstArrayElement(Iterable $value, string $attribute)
    {
        if (empty($value)) {
            return $value;
        }

        $this->logger->debug('resolved value for attribute ['.$attribute.'] is an array but is not declared as an array, use first array element instead', [
             'category' => get_class($this),
        ]);

        return current($value);
    }

    /**
     * Rewrite attribute value.
     */
    protected function rewrite($value, string $attribute, Iterable $ruleset, Iterable $data)
    {
        if (is_iterable($value)) {
            foreach ($value as &$value_child) {
                $new = $this->processRules($value_child, $ruleset, $data);
                if ($new !== null) {
                    $value_child = $new;
                }
            }

            return $value;
        }

        return $this->processRules($value, $ruleset, $data);
    }

    /**
     * Process ruleset.
     */
    protected function processRules($value, Iterable $ruleset, Iterable $data)
    {
        foreach ($ruleset as $rule) {
            if (!isset($rule['match'])) {
                throw new InvalidArgumentException('match in filter is not set');
            }

            if (!isset($rule['to'])) {
                throw new InvalidArgumentException('to in filter is not set');
            }

            if (isset($rule['regex']) && $rule['regex'] === false) {
                if ($value === $rule['match']) {
                    $value = $rule['to'];

                    return $value;
                }
            } elseif (isset($rule['regex']) && $rule['regex'] === true || !isset($rule['regex'])) {
                $value = preg_replace($rule['match'], $rule['to'], $value, -1, $count);
                if ($count > 0) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Convert value.
     */
    protected function convert($value, string $attribute, string $type)
    {
        switch ($type) {
            case AttributeMapInterface::TYPE_ARRAY:
                return (array) $value;

            break;
            case AttributeMapInterface::TYPE_STRING:
                return (string) $value;

            break;
            case AttributeMapInterface::TYPE_INT:
                return (int) $value;

            break;
            case AttributeMapInterface::TYPE_BOOL:
                return (bool) $value;

            break;
            case AttributeMapInterface::TYPE_FLOAT:
                return (float) $value;

            break;
            case AttributeMapInterface::TYPE_NULL:
                return null;

            break;
            default:
                if (is_object($value)) {
                    return $value;
                }

                throw new InvalidArgumentException('invalid type set for attribute '.$attribute);
        }
    }
}
