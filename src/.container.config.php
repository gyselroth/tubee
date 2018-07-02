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
use Tubee\Console;
use Zend\Mail\Transport\TransportInterface;
use Zend\Mail\Transport\Smtp;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Tubee\ExpressionLanguage\DateTimeLanguageProvider;
use Tubee\ExpressionLanguage\StringLanguageProvider;
use mindplay\middleman\Dispatcher;
use mindplay\middleman\ContainerResolver;
use Tubee\Rest\ExceptionHandler;
use Tubee\Rest\Acl as AclMiddleware;
use Micro\Http\Middlewares\Router;
use Micro\Http\Middlewares\RequestHandler;
use Lcobucci\ContentNegotiation\ContentTypeMiddleware;
use Lcobucci\ContentNegotiation\Formatter\Json;
use Micro\Auth\Middleware\Auth as AuthMiddleware;
use Middlewares\JsonPayload;

return [
    Dispatcher::class => [
        'arguments' => [
            'stack' => [
                ContentTypeMiddleware::class,
                ExceptionHandler::class,
                JsonPayload::class,
                AuthMiddleware::class,
                AclMiddleware::class,
                Router::class,
                RequestHandler::class,
            ],
            'resolver' => '{'.ContainerResolver::class.'}'
        ],
        'services' => [
            ContentTypeMiddleware::class => [
                'factory' => [
                    'method' => 'fromRecommendedSettings',
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
                    ]
                ],
            ]
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
        'selects' => [[
            'method' => 'selectDatabase',
            'arguments' => [
                'databaseName' => 'tubee'
            ]
        ]]
    ],
    LoggerInterface::class => [
        'use' => Logger::class,
        'arguments' => [
            'name' => 'default'
        ],
        'calls' => [
            'file' => [
                'method' => 'pushHandler',
                'arguments' => ['handler' => '{file}']
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
            'Monolog\Formatter\FormatterInterface' => [
                'use' => 'Monolog\Formatter\LineFormatter',
                'arguments' => [
                    'dateFormat' => 'Y-d-m H:i:s',
                    'format' => "%datetime% [%context.category%,%level_name%]: %message% %context.params% %context.exception%\n"
                ],
                'calls' => [
                    ['method' => 'includeStacktraces']
                ]
            ],
            'file' => [
                'use' => 'Monolog\Handler\StreamHandler',
                'arguments' => [
                    'stream' => '{ENV(BALLOON_LOG_DIR,/tmp)}/out.log',
                    'level' => 100
                 ],
                'calls' => [
                    'formatter' => [
                        'method' => 'setFormatter'
                    ]
                ]
            ],
            'stderr' => [
                'use' => 'Monolog\Handler\StreamHandler',
                'arguments' => [
                    'stream' => 'php://stderr',
                    'level' => 600,
                ],
                'calls' => [
                    'formatter' => [
                        'method' => 'setFormatter'
                    ]
                ],
            ],
            'stdout' => [
                'use' => 'Monolog\Handler\FilterHandler',
                'arguments' => [
                    'handler' => '{output}',
                    'minLevelOrList' => 100,
                    'maxLevel' => 550
                ],
                'services' => [
                    'output' => [
                        'use' => 'Monolog\Handler\StreamHandler',
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
    ExpressionLanguage::class => [
        'calls' => [
            [
                'method' => 'registerProvider',
                'arguments' => ['provider' => '{'.StringLanguageProvider::class.'}']
            ],
        ],
    ],
    /*Auth::class => [
        'calls' => [
            'basic_db' => [
                'method' => 'injectAdapter',
                'arguments' => ['adapter' => '{'.Db::class.'}', 'name' => 'basic_db']
            ],
        ],
    ],*/
    TransportInterface::class => [
        'use' => Smtp::class
    ],
];
