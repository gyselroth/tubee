<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

use Tubee\Bootstrap\ContainerBuilder;

define('TUBEE_PATH', (getenv('TUBEE_PATH') ? getenv('TUBEE_PATH') : realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..')));

define('TUBEE_CONFIG_DIR', (getenv('TUBEE_CONFIG_DIR') ? getenv('TUBEE_CONFIG_DIR') : constant('TUBEE_PATH').DIRECTORY_SEPARATOR.'config'));
!getenv('TUBEE_CONFIG_DIR') ? putenv('TUBEE_CONFIG_DIR='.constant('TUBEE_CONFIG_DIR')) : null;

define('TUBEE_LOG_DIR', (getenv('TUBEE_LOG_DIR') ? getenv('TUBEE_LOG_DIR') : constant('TUBEE_PATH').DIRECTORY_SEPARATOR.'log'));
!getenv('TUBEE_LOG_DIR') ? putenv('TUBEE_LOG_DIR='.constant('TUBEE_LOG_DIR')) : null;

set_include_path(implode(PATH_SEPARATOR, [
    constant('TUBEE_PATH').DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'lib',
    constant('TUBEE_PATH').DIRECTORY_SEPARATOR,
    get_include_path(),
]));

$composer = require 'vendor/autoload.php';

$dic = ContainerBuilder::get($composer);
$request = Zend\Diactoros\ServerRequestFactory::fromGlobals();
$logger = $dic->get(Psr\Log\LoggerInterface::class);

set_exception_handler(function ($e) use ($logger) {
    http_response_code(500);
    $logger->emergency('uncaught exception: '.$e->getMessage(), [
        'category' => 'Http',
        'exception' => $e,
    ]);
});

$dic->get(Tubee\Rest\Routes::class);
$dispatcher = $dic->get(\mindplay\middleman\Dispatcher::class);
$response = $dispatcher->dispatch($request);

$emitter = new \Zend\Diactoros\Response\SapiEmitter();
$emitter->emit($response);
