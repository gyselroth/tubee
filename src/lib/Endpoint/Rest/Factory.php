<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Rest;

use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class Factory
{
    /**
     * Build instance.
     */
    public static function buildClient(array $resource, LoggerInterface $logger): Client
    {
        $options = [
            'base_uri' => $resource['data']['resource']['base_uri'],
        ];

        if (isset($resource['data']['resource']['auth']) && $resource['data']['resource']['auth'] === 'basic') {
            $options['auth'] = [];

            if (isset($resource['data']['resource']['basic']['token'])) {
                $options['headers']['Authorization'] = 'Bearer ' . $resource['data']['resource']['basic']['token'];
            } else {
                if (isset($resource['data']['resource']['basic']['username'])) {
                    $options['auth'][] = $resource['data']['resource']['basic']['username'];
                }

                if (isset($resource['data']['resource']['basic']['password'])) {
                    $options['auth'][] = $resource['data']['resource']['basic']['password'];
                }
            }
        }

        $options = array_merge($resource['data']['resource']['request_options'], $options);

        $stack = \GuzzleHttp\HandlerStack::create();
        if (isset($resource['data']['resource']['auth']) && $resource['data']['resource']['auth'] === 'oauth2') {
            $stack->push(self::renewToken($resource, $logger));
        }

        $options['handler'] = $stack;
        $client = new Client($options);

        return $client;
    }

    public static function renewToken(array $resource, LoggerInterface $logger)
    {
        $token = '';

        return function (callable $handler) use ($resource, &$token, $logger) {
            return function (
                RequestInterface $request,
                array $options
            ) use ($handler, $resource, &$token, $logger) {
                $request = $request->withHeader('Authorization', "Bearer {$token}");

                $promise = $handler($request, $options);

                return $promise->then(
                    function (ResponseInterface $response) use ($resource, &$token, $logger, $handler, $request, $options) {
                        if ($response->getStatusCode() !== 401) {
                            return $response;
                        }

                        if ($token != '') {
                            $logger->info('request failed: invalid access token, try to create a new one', [
                                'category' => __CLASS__,
                            ]);
                        }

                        $token = self::fetchToken($resource, $logger);
                        $request = $request->withHeader('Authorization', "Bearer {$token}");

                        $promise = $handler($request, $options);

                        return $promise->then(function (ResponseInterface $response) {
                            return $response;
                        });
                    }
                );
            };
        };
    }

    protected static function fetchToken(array $resource, LoggerInterface $logger): string
    {
        $client = new Client();

        $oauth = $resource['data']['resource']['oauth2'];

        $logger->debug('create new access_token from ['.$oauth['token_endpoint'].']', [
            'category' => __CLASS__,
        ]);

        try {
            $response = $client->post($oauth['token_endpoint'], [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $oauth['client_id'],
                    'client_secret' => $oauth['client_secret'],
                    'scope' => $oauth['scope'],
                ],
            ]);
        } catch (\Exception $e) {
            $logger->error('failed to fetch access_token with message: '.$e->getMessage(), [
                'category' => __CLASS__,
            ]);

            throw new Exception\AccessTokenNotAvailable('access_token could not be fetched');
        }

        $logger->debug('fetch access_token ended with status ['.$response->getStatusCode().']', [
            'category' => __CLASS__,
        ]);

        $body = json_decode($response->getBody()->getContents(), true);

        if (isset($body['access_token'])) {
            return $body['access_token'];
        }

        throw new Exception\AccessTokenNotAvailable('No access_token in token_endpoint response');
    }
}
