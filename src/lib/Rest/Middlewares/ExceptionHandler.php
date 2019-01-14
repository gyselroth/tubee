<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Rest\Middlewares;

use Exception;
use Lcobucci\ContentNegotiation\UnformattedResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Tubee\Rest\Exception\ExceptionInterface;
use Zend\Diactoros\Response;

class ExceptionHandler implements MiddlewareInterface
{
    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Default response code.
     *
     * @var int
     */
    protected $response_code = 500;

    /**
     * Set the resolver instance.
     */
    public function __construct(LoggerInterface $logger, int $response_code = 500)
    {
        $this->logger = $logger;
        $this->response_code = $response_code;
    }

    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Exception $e) {
            return $this->sendException($e);
        }
    }

    /**
     * Sends a exception response to the client.
     */
    public function sendException(Exception $exception): ResponseInterface
    {
        $message = $exception->getMessage();
        $class = get_class($exception);

        $body = [
            'error' => $class,
            'message' => $message,
            'code' => $exception->getCode(),
        ];

        if ($exception instanceof ExceptionInterface) {
            $http_code = $exception->getStatusCode();
        } else {
            $http_code = $this->response_code;
        }

        $this->logger->error('uncaught exception '.$message.']', [
            'category' => get_class($this),
            'exception' => $exception,
        ]);

        return new UnformattedResponse(
            (new Response())->withStatus($http_code),
            $body
        );
    }
}
