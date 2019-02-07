<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Storage\Balloon;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class ApiClient
{
    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * URI.
     *
     * @var string
     */
    protected $uri = 'http://127.0.0.1';

    /**
     * Username.
     *
     * @var string
     */
    protected $username;

    /**
     * Password.
     *
     * @var string
     */
    protected $password;

    /**
     * CURL options.
     *
     * @var array
     */
    protected $curl_options = [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ];

    /**
     * construct.
     */
    public function __construct(array $config = [], LoggerInterface $logger)
    {
        $this->setOptions($config);
        $this->logger = $logger;
    }

    /**
     * Set options.
     */
    public function setOptions(array $config = []): ApiClient
    {
        foreach ($config as $option => $value) {
            switch ($option) {
                case 'url':
                    $this->uri = (string) $value;

                    break;
                case 'username':
                    $this->username = (string) $value;

                    break;
                case 'password':
                    $this->password = (string) $value;

                    break;
                case 'request_options':
                    foreach ($value as $key => $opt) {
                        $name = constant($key);
                        $this->curl_options[$name] = $opt;
                    }

                    break;
                default:
                    throw new InvalidArgumentException('unknown option '.$option.' given');
            }
        }

        return $this;
    }

    /**
     * Open stream.
     */
    public function openSocket(string $url, array $params = [], string $method = 'GET')
    {
        $opts = [
            'http' => [
                'method' => $method,
                'header' => "Content-Type: application/json\r\n".
                          'Authorization: Basic '.base64_encode($this->username.':'.$this->password)."\r\n",
            ],
        ];

        $context = stream_context_create($opts);

        $params = json_encode($params);

        $url = $this->uri.$url.'?'.urlencode($params);

        $this->logger->info('open socket for ['.$url.']', [
            'category' => get_class($this),
        ]);

        return fopen($url, 'r', false, $context);
    }

    /**
     * REST call.
     */
    public function restCall(string $url, array $params = [], string $method = 'GET'): ?array
    {
        $url = $this->uri.$url;
        $ch = curl_init();

        $params = json_encode($params);
        switch ($method) {
            case 'GET':
                $url .= '?'.urlencode($params);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                // no break
            default:
                break;
        }

        $this->logger->info('execute curl request ['.$url.']', [
            'category' => get_class($this),
            'params' => $params,
        ]);

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);

        curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");
        foreach ($this->curl_options as $opt => $value) {
            curl_setopt($ch, $opt, $value);
        }

        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch)['http_code'];
        curl_close($ch);

        if (substr((string) $http_code, 0, 1) !== '2') {
            if ($result !== false) {
                $this->logger->error('failed process balloon request ['.$result.']', [
                    'category' => get_class($this),
                ]);
            }

            throw new Exception\FailedProcessRequest('http request failed with response code '.$http_code);
        }

        $body = json_decode($result, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception\InvalidApiResponse('failed decode balloon json response with error '.json_last_error());
        }

        return $body;
    }
}
