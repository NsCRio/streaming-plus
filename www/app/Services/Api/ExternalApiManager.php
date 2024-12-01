<?php

namespace App\Services\Api;

class ExternalApiManager
{
    protected $response;

    public function __construct($url, $method = 'GET', array $data = null, array $headers = [])
    {
        $this->sendRequest($url, $method, $data, $headers);
    }

    public function sendRequest($url, $method, array $data, array $headers = []){
        $curl = curl_init();

        switch ($method){
            case "POST":
                curl_setopt($curl, CURLOPT_POST, true);
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                break;
        }
        if (isset($data))
            $url = sprintf("%s?%s", $url, http_build_query($data));

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        $this->response = curl_exec($curl);
        curl_close($curl);

        return $this->response;
    }

    public function getResponse(){
        return ($this->response) ? json_decode($this->response, true) : null;
    }

}
