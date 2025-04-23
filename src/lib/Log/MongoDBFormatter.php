<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Log;

use MongoDB\BSON\Type as BSONType;
use MongoDB\BSON\UTCDateTime;
use Monolog\Formatter\FormatterInterface;
use Monolog\Utils;

class MongoDBFormatter implements FormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function format(array $record): array
    {
        $formatted = [
            'changed' => $record['datetime'],
            'namespace' => $record['context']['namespace'] ?? null,
            'collection' => $record['context']['collection'] ?? null,
            'endpoint' => $record['context']['endpoint'] ?? null,
        ];

        unset($record['context']['namespace'], $record['context']['collection'], $record['context']['endpoint'], $record['datetime']);

        $formatted['data'] = $record;

        return $this->formatArray($formatted);
    }

    /**
     * {@inheritdoc}
     */
    public function formatBatch(array $records): array
    {
        foreach ($records as $key => $record) {
            $records[$key] = $this->format($record);
        }

        return $records;
    }

    /**
     * Format record.
     */
    protected function formatArray(array $record)
    {
        foreach ($record as $name => $value) {
            if ($value instanceof \DateTimeInterface) {
                $record[$name] = $this->formatDate($value);
            } elseif ($value instanceof \Throwable) {
                $record[$name] = $this->formatException($value);
            } elseif (($name === 'data' || $name === 'context')) {
                $record[$name] = $this->formatArray($value);
            } elseif ($value instanceof BSONType) {
                continue;
            } elseif (is_object($value) || is_array($value)) {
                $record[$name] = json_encode($value);
            }
        }

        return $record;
    }

    protected function formatObject($value)
    {
        $objectVars = get_object_vars($value);
        $objectVars['class'] = Utils::getClass($value);

        return $this->formatArray($objectVars);
    }

    protected function formatException(\Throwable $exception)
    {
        $formattedException = [
            'class' => Utils::getClass($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile().':'.$exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        return $this->formatArray($formattedException);
    }

    protected function formatDate(\DateTimeInterface $value): UTCDateTime
    {
        return new UTCDateTime((int) (string) floor($value->format('U.u') * 1000));
    }
}
