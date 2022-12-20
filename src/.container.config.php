<?php
use Tubee\Auth\Adapter\Basic\Db;
use Tubee\Exception;
use Composer\Autoload\ClassLoader as Composer;
use Micro\Auth\Auth;
use Micro\Container\Container;
use MongoDB\Client;
use Tubee\Rest\Middlewares\CorsHandler;
use Tubee\User\AuthAdapter;
use MongoDB\Database;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Processor;
use Tubee\Console;
use Zend\Mail\Transport\TransportInterface;
use Zend\Mail\Transport\Smtp;
use mindplay\middleman\Dispatcher;
use mindplay\middleman\ContainerResolver;
use Tubee\Rest\Middlewares\ExceptionHandler;
use Tubee\Rest\Middlewares\QueryDecoder;
use Tubee\Rest\Middlewares\Acl as AclMiddleware;
use Tubee\Rest\Middlewares\Auth as AuthMiddleware;
use Tubee\Rest\Middlewares\RequestHandler;
use Lcobucci\ContentNegotiation\ContentTypeMiddleware;
use Lcobucci\ContentNegotiation\Formatter\Json;
use Middlewares\JsonPayload;
use Middlewares\FastRoute;
use Middlewares\AccessLog;
use Middlewares\TrailingSlash;
use Tubee\Migration;
use Tubee\Rest\Routes;
use Tubee\Async\WorkerFactory;
use TaskScheduler\Queue;
use TaskScheduler\WorkerFactoryInterface;
use TaskScheduler\WorkerManager;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\HiddenString\HiddenString;
use Tubee\V8\Engine as V8Engine;
use Psr\SimpleCache\CacheInterface;
use Cache\Adapter\Void\VoidCachePool;
use Cache\Adapter\Apcu\ApcuCachePool;
use Dreamscapes\Ldap\Core\Ldap as DreamscapesLdap;
use Tubee\Log\MongoDBFormatter as MongoDBFormatter;
use TaskScheduler\JobInterface;

return [
    JobInterface::class => [
        'singleton' => false,
    ],
    DreamscapesLdap::class => [
        'singleton' => false
    ],
    Dispatcher::class => [
        'arguments' => [
            'stack' => [
                '{'.ContentTypeMiddleware::class.'}',
                '{'.AccessLog::class.'}',
                '{'.ExceptionHandler::class.'}',
                '{'.JsonPayload::class.'}',
                '{'.QueryDecoder::class.'}',
                '{'.CorsHandler::class.'}',
                '{'.FastRoute::class.'}',
                '{'.AuthMiddleware::class.'}',
                '{'.AclMiddleware::class.'}',
                '{'.TrailingSlash::class.'}',
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
    EncryptionKey::class => [
        'use' => KeyFactory::class,
        'factory' => 'importEncryptionKey',
        'arguments' => [
            'keyData' => '{'.HiddenString::class.'}'
        ],
        'services' => [
            HiddenString::class => [
                'arguments' => [
                    'value' => "{ENV(TUBEE_ENCRYPTION_KEY,314004004b3cef33ba8ea540b424736408364317d9ebfbc9293b8478a8d2478e23dba1ba30ded48ab0dd059cfe3dce2daf00d10eb40af1c0bf429553a2d64802272a514cfde95ac31956baa3929ee01c7338c95805c3a619e254f7aa2966e6a7cdad4783)}"
                ]
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
    Migration::class => [
        'calls' => [
            [
                'method' => 'injectDelta',
                'arguments' => ['delta' => '{'.Migration\AddWriteableAttributeMap::class.'}']
            ],
            [
                'method' => 'injectDelta',
                'arguments' => ['delta' => '{'.Migration\AddKindAttributeMap::class.'}']
            ],
            [
                'method' => 'injectDelta',
                'arguments' => ['delta' => '{'.Migration\AddWorkgroupSmbStorage::class.'}']
            ],
            [
                'method' => 'injectDelta',
                'arguments' => ['delta' => '{'.Migration\CoreInstallation::class.'}']
            ],
        ]
    ],
    Client::class => [
        'arguments' => [
            'uri' => '{ENV(TUBEE_MONGODB_URI,mongodb://localhost:27017)}',
            'driverOptions' => [
                'typeMap' => [
                    'root' => 'array',
                    'document' => 'array',
                    'array' => 'array',
                ]
            ]
        ],
    ],
    Database::class => [
        'use' => '{MongoDB\Client}',
        'calls' => [[
            'select' => true,
            'method' => 'selectDatabase',
            'arguments' => [
                'databaseName' => 'tubee'
            ]
        ]]
    ],
    CacheInterface::class => [
        'use' => '{ENV(TUBEE_CACHE_ADAPTER,'.ApcuCachePool::class.')}'
    ],
    LoggerInterface::class => [
        'use' => Logger::class,
        'arguments' => [
            'name' => 'default',
            'processors' => [
                '{'.Processor\PsrLogMessageProcessor::class.'}',
            ]
        ],
        'calls' => [
            'mongodb' => [
                'method' => 'pushHandler',
                'arguments' => ['handler' => '{mongodb}']
            ],
            'stderr' => [
                'method' => 'pushHandler',
                'arguments' => ['handler' => '{stderr}']
            ],
            'stdout' => [
                'method' => 'pushHandler',
                'arguments' => ['handler' => '{stdout}']
            ],
        ],
        'services' => [
            Monolog\Formatter\FormatterInterface::class => [
                'use' => Monolog\Formatter\LineFormatter::class,
                'arguments' => [
                    'dateFormat' => 'Y-d-m H:i:s',
                    'format' => "%datetime% [%context.category%,%level_name%]: %message% %context.params% %context.exception%\n"
                ],
                'calls' => [
                    ['method' => 'includeStacktraces']
                ]
            ],
            'mongodb' => [
                'use' => Monolog\Handler\MongoDBHandler::class,
                'arguments' => [
                    'mongodb' => '{'.Client::class.'}',
                    'database' => 'tubee',
                    'collection' => 'logs',
                    'level' => 1000,
                ],
                'calls' => [
                    'formatter' => [
                        'method' => 'setFormatter',
                        'arguments' => [
                            'formatter' => '{'.MongoDBFormatter::class.'}'
                        ]
                    ]
                ],
            ],
            'stderr' => [
                'use' => Monolog\Handler\StreamHandler::class,
                'arguments' => [
                    'stream' => 'php://stderr',
                    'level' => 100,
                ],
                'calls' => [
                    'formatter' => [
                        'method' => 'setFormatter'
                    ]
                ],
            ],
            'stdout' => [
                'use' => Monolog\Handler\FilterHandler::class,
                'arguments' => [
                    'handler' => '{output}',
                    'minLevelOrList' => 100,
                    'maxLevel' => 550
                ],
                'services' => [
                    'output' => [
                        'use' => Monolog\Handler\StreamHandler::class,
                        'arguments' => [
                            'stream' => 'php://stdout',
                            'level' => 100
                        ],
                        'calls' => [
                            'formatter' => [
                                'method' => 'setFormatter'
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ],
    V8Js::class => [
        'use' => V8Engine::class,
    ],
    Auth::class => [
        'calls' => [
            'basic_db' => [
                'method' => 'injectAdapter',
                'arguments' => ['adapter' => '{'.AuthAdapter::class.'}', 'name' => 'basic_db']
            ],
        ],
    ],
    TransportInterface::class => [
        'use' => Smtp::class
    ],
];
