<?php

namespace App\Services\Scraper\WebDriver;

use App\Services\Scraper\BadMethodCallException;
use App\Services\Scraper\InvalidArgumentException;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\Remote\DriverCommand;
use Facebook\WebDriver\Remote\WebDriverCommand;
use Facebook\WebDriver\Remote\WebDriverResponse;

class HttpCommandExecutor extends \Facebook\WebDriver\Remote\HttpCommandExecutor
{
    public function execute(WebDriverCommand $command)
    {
        if (!isset(self::$commands[$command->getName()])) {
            throw new InvalidArgumentException($command->getName() . ' is not a valid command.');
        }

        $raw = self::$commands[$command->getName()];
        $http_method = $raw['method'];
        $url = $raw['url'];
        $url = str_replace(':sessionId', $command->getSessionID(), $url);
        $params = $command->getParameters();
        foreach ($params as $name => $value) {
            if ($name[0] === ':') {
                $url = str_replace($name, $value, $url);
                if ($http_method != 'POST') {
                    unset($params[$name]);
                }
            }
        }

        if ($params && is_array($params) && $http_method !== 'POST') {
            throw new BadMethodCallException(sprintf(
                'The http method called for %s is %s but it has to be POST' .
                ' if you want to pass the JSON params %s',
                $url,
                $http_method,
                json_encode($params)
            ));
        }

        curl_setopt($this->curl, CURLOPT_URL, $this->url . $url);

        // https://github.com/facebook/php-webdriver/issues/173
        if ($command->getName() === DriverCommand::NEW_SESSION) {
            curl_setopt($this->curl, CURLOPT_POST, 1);
        } else {
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $http_method);
        }

        $encoded_params = null;
        if ($http_method === 'POST' && $params && is_array($params)) {
            $encoded_params = json_encode($params);
        }

        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $encoded_params);

        $raw_results = trim(curl_exec($this->curl));

        if ($error = curl_error($this->curl)) {
            $msg = sprintf(
                'Curl error thrown for http %s to %s',
                $http_method,
                $url
            );
            if ($params && is_array($params)) {
                $msg .= sprintf(' with params: %s', json_encode($params));
            }
            WebDriverException::throwException(-1, $msg . "\n\n" . $error, array());
        }

        $results = json_decode($raw_results, true);

        if ($results === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new WebDriverException(
                sprintf(
                    "JSON decoding of remote response failed.\n" .
                    "Error code: %d\n" .
                    "The response: '%s'\n",
                    json_last_error(),
                    $raw_results
                )
            );
        }

        $value = null;
        if (is_array($results) && array_key_exists('value', $results)) {
            $value = $results['value'];
        }

        $message = null;
        if (is_array($value) && array_key_exists('message', $value)) {
            $message = $value['message'];
        }

        $sessionId = null;
        if (is_array($value) && array_key_exists('sessionId', $value)) {
            $sessionId = $value['sessionId'];
        }

        $status = isset($results['status']) ? $results['status'] : 0;
        WebDriverException::throwException($status, $message, $results);

        $response = new WebDriverResponse($sessionId);

        return $response
            ->setStatus($status)
            ->setValue($value);
    }

}
