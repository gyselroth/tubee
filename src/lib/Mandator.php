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
use Psr\Http\Message\ServerRequestInterface;
use Tubee\Mandator\MandatorInterface;

class Mandator implements MandatorInterface
{
    /**
     * Data.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Initialize.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
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
            'name' => $this->data['name'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return $this->data['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->data['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): ObjectId
    {
        return $this->data['_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
