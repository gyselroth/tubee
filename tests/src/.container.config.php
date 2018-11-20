<?php
use Tubee\Auth\Adapter\Basic\Db;
use Tubee\Exception;
use Composer\Autoload\ClassLoader as Composer;
use Micro\Auth\Auth;
use Micro\Container\Container;
use MongoDB\Client;
use MongoDB\Database;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Processor;
use Tubee\Console;
use Zend\Mail\Transport\TransportInterface;
use Zend\Mail\Transport\Smtp;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Tubee\ExpressionLanguage\DateTimeLanguageProvider;
use Tubee\ExpressionLanguage\StringLanguageProvider;
use mindplay\middleman\Dispatcher;
use mindplay\middleman\ContainerResolver;
use Tubee\Rest\Middlewares\ExceptionHandler;
use Tubee\Rest\Middlewares\QueryDecoder;
use Tubee\Rest\Middlewares\Acl as AclMiddleware;
use Tubee\Testsuite\Integration\Mock\AuthMiddleware;
use Micro\Http\Middlewares\Router;
use Micro\Http\Middlewares\RequestHandler;
use Lcobucci\ContentNegotiation\ContentTypeMiddleware;
use Lcobucci\ContentNegotiation\Formatter\Json;
use Middlewares\JsonPayload;
use Middlewares\FastRoute;
use Tubee\Migration;
use Tubee\Rest\Routes;
use Tubee\Async\WorkerFactory;
use TaskScheduler\Queue;
use TaskScheduler\WorkerFactoryInterface;
use TaskScheduler\WorkerManager;
use Micro\Auth\Identity;
use Micro\Auth\Adapter\AdapterInterface as AuthAdapterInterface;
use Micro\Auth\Adapter\None as AuthAdapterNone;
use Micro\Auth\AttributeMap as AuthAttributeMap;
use Helmich\MongoMock\MockDatabase;
use Middlewares\TrailingSlash;

return [
    Identity::class => [
        'arguments' => [
            'identity' => 'foo'
        ],
        'services' => [
            AuthAdapterInterface::class => [
                'use' => AuthAdapterNone::class
            ],
            AuthAttributeMap::class => [
                'arguments' => [
                    'map' => []
                ]
            ]
        ]
    ],
    Dispatcher::class => [
        'arguments' => [
            'stack' => [
                '{'.ContentTypeMiddleware::class.'}',
                '{'.ExceptionHandler::class.'}',
                '{'.JsonPayload::class.'}',
                '{'.QueryDecoder::class.'}',
                '{'.TrailingSlash::class.'}',
                '{'.FastRoute::class.'}',
                '{'.RequestHandler::class.'}',
            ],
            'resolver' => '{'.ContainerResolver::class.'}'
        ],
        'services' => [
            Routes::class => [
                'factory' => 'collect',
            ],
            FastRoute::class => [
                'arguments' => [
                    'router' => '{'.Routes::class.'}'
                ]
            ],
            ContentTypeMiddleware::class => [
                'factory' => 'fromRecommendedSettings',
                'arguments' => [
                    'formats' => [
                        'json' => [
                            'extension' => ['json'],
                            'mime-type' => ['application/json', 'text/json', 'application/x-json'],
                            'charset' => true,
                        ],
                    ],
                    'formatters' => [
                       'application/json' => '{'.Json::class.'}',
                    ],
                ],
            ]
        ]
    ],
    Queue::class => [
        'services' => [
            WorkerFactoryInterface::class => [
                'use' => WorkerFactory::class
            ]
        ]
    ],
    WorkerManager::class => [
        'services' => [
            WorkerFactoryInterface::class => [
                'use' => WorkerFactory::class
            ]
        ]
    ],
    Database::class => [
        'use' => MockDatabase::class,
        'arguments' => [
            'options' => [
                'typeMap' => [
                    'root' => 'array',
                    'document' => 'array',
                    'array' => 'array',
                ]
            ]
        ]
    ],
    LoggerInterface::class => [
        'use' => Logger::class,
        'arguments' => [
            'name' => 'default',
        ],
    ],
    ExpressionLanguage::class => [
        'calls' => [
            [
                'method' => 'registerProvider',
                'arguments' => ['provider' => '{'.StringLanguageProvider::class.'}']
            ],
        ],
    ],
    TransportInterface::class => [
        'use' => Smtp::class
    ],
];
