<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Console;

use GetOpt\GetOpt;
use mindplay\middleman\Dispatcher;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Swoole;
use Swoole\Http\Server;
use Tubee\Rest\Routes;
use Zend\Expressive\Swoole\ServerRequestSwooleFactory;
use Zend\Expressive\Swoole\SwooleEmitter;

class Http
{
    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Getopt.
     *
     * @var GetOpt
     */
    protected $getopt;

    /**
     * Server.
     *
     * @var Server
     */
    protected $scheduler;

    /**
     * Constructor.
     */
    public function __construct(Server $server, ServerRequestSwooleFactory $request_factory, Routes $routes, Dispatcher $dispatcher, ContainerInterface $container, LoggerInterface $logger, GetOpt $getopt)
    {
        $this->server = $server;
        $this->logger = $logger;
        $this->getopt = $getopt;
        $this->request_factory = $request_factory;
        $this->dispatcher = $dispatcher;
        $this->container = $container;
    }

    /**
     * Fire up daemon.
     */
    public function __invoke(): bool
    {
        $this->logger->info('start http server', [
            'category' => get_class($this),
        ]);

        $request_factory = $this->request_factory;
        $dispatcher = $this->dispatcher;
        $container = $this->container;

        $this->server->on('request', function (Swoole\Http\Request $request, Swoole\Http\Response $response) use ($request_factory, $dispatcher, $container) {
            $psr7 = ($request_factory)($container)($request);

            $emitter = new SwooleEmitter($response);
            $response = $dispatcher->dispatch($psr7);
            $emitter->emit($response);
        });

        $this->server->start();

        return true;
    }

    /**
     * Get options.
     */
    public static function getOptions(): array
    {
        return [];
    }

    /**
     * Get operands.
     */
    public static function getOperands(): array
    {
        return [];
    }
}
