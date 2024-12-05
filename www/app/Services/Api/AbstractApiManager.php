<?php

namespace App\Services\Api;

use GuzzleHttp\Client;

class AbstractApiManager
{
    protected $endpoint, $default_headers = [];

    public static function call(string $uri, string $method = 'GET', array $data = [], array $headers = []) : array|null {
        $api = new self();
        return $api->apiCall($uri, $method, $data, $headers);
    }

    protected function apiCall(string $uri, string $method = 'GET', array $data = [], array $headers = [], $returnBody = false) : string|array|null {
        try {
            $cli = new Client();
            $uri = !str_starts_with('/', $uri) ? $uri : '/' . $uri;
            $headers = array_merge($this->default_headers, $headers);
            $options = [
                'connect_timeout' => 120,
                'headers' => $headers,
            ];
            if($method == 'POST' || $method == 'PUT') {
                $options['form_params'] = $data;
            }elseif($method == 'POST_BODY') {
                $method = 'POST';
                $options['body'] = json_encode($data);
            }elseif($method == 'POST_JSON') {
                $method = 'POST';
                $options['json'] = $data;
            }elseif($method == 'GET'){
                $uri = sprintf("%s?%s", $uri, http_build_query($data));
            }
            $url = isset($this->endpoint) ? $this->endpoint . $uri : $uri;
            //dd($method, $url, $options);
            $r = $cli->request($method, $url, $options);
            $body = $r->getBody();
            if($returnBody) {
                $response = $body->getContents();
            }else{
                $response = json_decode((string) $body, true);
            }
            return $response;
        }catch (\GuzzleHttp\Exception\ClientException $e){
            dd($e->getResponse());
        }
        return null;
    }

    protected function getRandomAgent(): string
    {
        $agent_version_rand = rand(0,99);
        $agent_random = array(
            '1' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:64.0) Gecko/201001'.$agent_version_rand.' Firefox/64.0',
            '2' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/536.'.$agent_version_rand.' (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/536.'.$agent_version_rand,
            '3' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.'.$agent_version_rand.' (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.'.$agent_version_rand.' Edge/17.17134',
            '4' => 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.'.$agent_version_rand.') like Gecko',
            '5' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.'.$agent_version_rand.' (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.'.$agent_version_rand,
            '6' => 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.'.$agent_version_rand.' (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.'.$agent_version_rand,
            '7' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_2) AppleWebKit/537.'.$agent_version_rand.' (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.'.$agent_version_rand,
            '8' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_2) AppleWebKit/605.1.'.$agent_version_rand.' (KHTML, like Gecko) Version/12.0.2 Safari/605.1.'.$agent_version_rand.''
        );
        return $agent_random[rand(1,8)];
    }

}
