<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use InvalidArgumentException;
use MongoDB\BSON\Binary;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\AttributeMap\Diff;
use Tubee\AttributeMap\Exception;
use Tubee\AttributeMap\Transform;
use Tubee\V8\Engine as V8Engine;
use V8Js;

class AttributeMap implements AttributeMapInterface
{
    /**
     * Attribute map.
     *
     * @var array
     */
    protected $map = [];

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * V8.
     *
     * @var V8Engine
     */
    protected $v8;

    /**
     * Init attribute map.
     */
    public function __construct(array $map = [], V8Engine $v8, LoggerInterface $logger)
    {
        $this->map = $map;
        $this->logger = $logger;
        $this->v8 = $v8;
    }

    /**
     * {@inheritdoc}
     */
    public function getMap(): array
    {
        return $this->map;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
    {
        return array_column($this->map, 'name');
    }

    /**
     * {@inheritdoc}
     */
    public function map(array $data): array
    {
        $this->v8->object = $data;
        $attrv = null;

        $result = [];
        foreach ($this->map as $value) {
            if (isset($attrv)) {
                unset($attrv);
            }

            $attr = $value['name'];

            if (isset($value['ensure'])) {
                if ($value['ensure'] === AttributeMapInterface::ENSURE_MERGE && isset($value['type']) && $value['type'] !== AttributeMapInterface::TYPE_ARRAY) {
                    throw new InvalidArgumentException('attribute '.$attr.' ensure is set to merge but type is not an array');
                }

                if ($value['ensure'] === AttributeMapInterface::ENSURE_ABSENT) {
                    continue;
                }
            }

            $mapped = $this->mapField($attr, $value, $data);

            if (isset($value['name'])) {
                $attr = $value['name'];
            }

            if ($mapped !== null) {
                $result[$attr] = $mapped;

                $this->logger->debug('mapped attribute ['.$attr.'] to [<'.gettype($result[$attr]).'> {value}]', [
                    'category' => get_class($this),
                    'value' => ($result[$attr] instanceof Binary) ? '<bin '.mb_strlen($result[$attr]->getData()).'>' : $result[$attr],
                ]);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiff(array $object, array $endpoint_object): array
    {
        return Diff::calculate($this->map, $object, $endpoint_object);
    }

    /**
     * Map field.
     */
    protected function mapField($attr, $value, $data)
    {
        $attrv = $this->resolveValue($attr, $value, $data);
        $attrv = $this->transformAttribute($attr, $value, $attrv);

        if ($this->requireAttribute($attr, $value, $attrv) === null) {
            return;
        }

        if (isset($value['type'])) {
            $attrv = Transform::convertType($attrv, $attr, $value['type']);
        }

        if (isset($value['unwind'])) {
            $unwind = [];
            foreach ($attrv as $key => $element) {
                $result = $this->mapField($attr, $value['unwind'], [
                    'root' => $element,
                ]);

                if ($result !== null) {
                    $unwind[$key] = $result;
                }
            }

            $attrv = $unwind;
        }

        return $attrv;
    }

    /**
     * Check if attribute is required.
     */
    protected function requireAttribute(string $attr, array $value, $attrv)
    {
        if ($attrv === null || is_string($attrv) && strlen($attrv) === 0 || is_array($attrv) && count($attrv) === 0) {
            if (isset($value['required']) && $value['required'] === false || !isset($value['required'])) {
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
    protected function transformAttribute(string $attr, array $value, $attrv)
    {
        if ($attrv === null) {
            return null;
        }

        if (isset($value['type']) && $value['type'] !== AttributeMapInterface::TYPE_ARRAY && is_array($attrv)) {
            $attrv = $this->firstArrayElement($attrv, $attr);
        }

        if (isset($value['rewrite'])) {
            $attrv = $this->rewrite($attrv, $value['rewrite']);
        }

        if (isset($value['require_regex'])) {
            if (!preg_match($value['require_regex'], $attrv)) {
                throw new Exception\AttributeRegexNotMatch('resolve attribute '.$attr.' value does not match require_regex');
            }
        }

        return $attrv;
    }

    /**
     * Check if attribute is required.
     */
    protected function resolveValue(string $attr, array $value, array $data)
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
                $this->v8->executeString($value['script'], '', V8Js::FLAG_FORCE_ARRAY);
                $result = $this->v8->getLastResult();
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
     * Process ruleset.
     */
    protected function rewrite($value, array $ruleset)
    {
        foreach ($ruleset as $rule) {
            if (isset($rule['from'])) {
                if ($value === $rule['from']) {
                    $value = $rule['to'];

                    return $value;
                }
            } elseif (isset($rule['match'])) {
                $value = preg_replace($rule['match'], $rule['to'], $value, -1, $count);
                if ($count > 0) {
                    return $value;
                }
            }
        }

        return $value;
    }
}
