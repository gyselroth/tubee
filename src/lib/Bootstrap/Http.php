<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Bootstrap;

use Micro\Auth\Adapter\None as AuthNone;
use Micro\Auth\Auth;
use Micro\Http\Response;
use Micro\Http\Router;
use MongoDB\BSON\Binary;
use Psr\Log\LoggerInterface;

class Http extends AbstractBootstrap
{
    /**
     * Auth.
     *
     * @var Auth
     */
    protected $auth;

    /**
     * Router.
     *
     * @var Router
     */
    protected $router;

    /**
     * Http.
     */
    public function __construct(/*LoggerInterface $logger, Auth $auth, Router $router*/)
    {
        /*$this->setExceptionHandler();
        $this->setErrorHandler();

        $this->logger = $logger;
        $this->auth = $auth;
        $this->router = $router;

        $router
            ->appendRoute(new Route('/api/v1/mandators', v1\Mandators::class))
            ->appendRoute(new Route('/api/v1/mandators/{mandator:#([0-9a-zA-Z_-])#}(/|\z)', v2\Mandators::class))
            ->appendRoute(new Route('/api/v1/mandators/{mandator:#([0-9a-zA-Z_-])#}/datatypes(/|\z)/', v1\DataTypes::class))
            ->appendRoute(new Route('/api/v1/mandators/{mandator:#([0-9a-zA-Z_-])#}/datatypes/{datatype:#([0-9a-zA-Z_-])#}(/|\z)/', v1\DataTypes::class))
            ->appendRoute(new Route('/api/v1/mandators/{mandator:#([0-9a-zA-Z_-])#}/datatypes/{datatype:#([0-9a-zA-Z_-])#}/objects(/|\z)/', v1\Objects::class))
            ->appendRoute(new Route('/api/v1', v2\Api::class))
            ->appendRoute(new Route('/api$', v2\Api::class))
            ->appendRoute(new Route('^$', v2\Api::class));*/
    }

    /**
     * Process.
     *
     * @return Http
     */
    public function process()
    {
        $this->logger->info('processing incoming http ['.$_SERVER['REQUEST_METHOD'].'] request to ['.$_SERVER['REQUEST_URI'].']', [
            'category' => get_class($this),
        ]);

        if ($this->auth->requireOne()) {
            if (!($this->auth->getIdentity()->getAdapter() instanceof AuthNone)) {
                $this->auth->getIdentity()->getAttributeMap()->addMapper('binary', function ($value) {
                    return new Binary($value, Binary::TYPE_GENERIC);
                });
            }

            $this->router->run();
        } else {
            $this->invalidAuthentication();
        }

        return $this;
    }

    /**
     * Send invalid authentication response.
     */
    protected function invalidAuthentication(): void
    {
        if (isset($_SERVER['PHP_AUTH_USER']) && '_logout' === $_SERVER['PHP_AUTH_USER']) {
            (new Response())
                ->setCode(401)
                ->setBody('Unauthorized')
                ->send();
        } else {
            if ('/api/auth' === $_SERVER['PATH_INFO']) {
                $code = 403;
            } else {
                $code = 401;
            }

            (new Response())
                ->setHeader('WWW-Authenticate', 'Basic realm="tubee"')
                ->setCode($code)
                ->setBody('Unauthorized')
                ->send();
        }
    }

    /**
     * Set exception handler.
     *
     * @return Http
     */
    protected function setExceptionHandler(): self
    {
        set_exception_handler(function ($e) {
            $this->logger->emergency('uncaught exception: '.$e->getMessage(), [
                'category' => get_class($this),
                'exception' => $e,
            ]);

            (new Response())
                ->setCode(500)
                ->setBody([
                    'error' => get_class($e),
                    'message' => $e->getMessage(),
                ])
                ->send();
        });

        return $this;
    }
}
