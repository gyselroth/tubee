<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Integration\Api\Rest;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use Tubee\Bootstrap\ContainerBuilder;

class ApiTest extends TestCase
{
    public function testApi()
    {
        define('TUBEE_PATH', (getenv('TUBEE_PATH') ? getenv('TUBEE_PATH') : realpath(__DIR__.'/../../..')));

        define('TUBEE_CONFIG_DIR', (getenv('TUBEE_CONFIG_DIR') ? getenv('TUBEE_CONFIG_DIR') : constant('TUBEE_PATH')));
        !getenv('TUBEE_CONFIG_DIR') ? putenv('TUBEE_CONFIG_DIR='.constant('TUBEE_CONFIG_DIR')) : null;

        define('TUBEE_LOG_DIR', (getenv('TUBEE_LOG_DIR') ? getenv('TUBEE_LOG_DIR') : constant('TUBEE_PATH').DIRECTORY_SEPARATOR.'log'));
        !getenv('TUBEE_LOG_DIR') ? putenv('TUBEE_LOG_DIR='.constant('TUBEE_LOG_DIR')) : null;

        $composer = require __DIR__.'/../../../../vendor/autoload.php';

        $dic = ContainerBuilder::get($composer);

        //var_dump(Yaml::parseFile(dirname(__FILE__).'/../../../../src/lib/Rest/v1/swagger.yml'));
        //exit();

        // do stuff with your Swagger entity

        $spec = Yaml::parseFile(__DIR__.'/../../../../src/lib/Rest/v1/swagger.yml');

        foreach ($spec['paths'] as $path => $rpath) {
            foreach ($rpath as $method => $rmethod) {
                $method = strtoupper($method);
                var_dump('/api/v1'.$path.' -- '.$method);

                $parameters = [];
                if (isset($rmethod['parameters'])) {
                    $parameters = $rmethod['parameters'];
                }

                var_dump($parameters);
                $request = new \Zend\Diactoros\ServerRequest([], [], '/api/v1'.$path, $method);
                $dic->get(\Tubee\Rest\Routes::class);
                $dispatcher = $dic->get(\mindplay\middleman\Dispatcher::class);
                $response = $dispatcher->dispatch($request);

                var_dump($response);
                /*if(isset($responses->{$response->getStatusCode()})) {
                    var_dump("FINE");
                } else {
                    exit();
                }*/

//var_dump($responses);
            //foreach($responses as $expect) {
                //if($this)
            //}
            }
        }
    }
}

/*
 80     public function __construct(
 81         array $serverParams = [],
 82         array $uploadedFiles = [],
 83         $uri = null,
 84         $method = null,
 85         $body = 'php://input',
 86         array $headers = [],
 87         array $cookies = [],
 88         array $queryParams = [],
 89         $parsedBody = null,
 90         $protocol = '1.1'
 91     ) {
*/

/*        $request = new \Zend\Diactoros\ServerRequest([],[], '/api/v1/access-roles', 'GET');
        //var_dump($request);
        $dic->get(\Tubee\Rest\Routes::class);
        $dispatcher = $dic->get(\mindplay\middleman\Dispatcher::class);
        $response = $dispatcher->dispatch($request);
        var_dump($response);

        //$emitter = new \Zend\Diactoros\Response\SapiEmitter();
//$emitter->emit($response);*/
