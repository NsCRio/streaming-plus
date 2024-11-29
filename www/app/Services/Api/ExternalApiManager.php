<?php

namespace App\Services\Api;

class ExternalApiManager
{
    protected $response;

    public function __construct($url, $method = 'GET', array $data = null)
    {
        $this->sendRequest($url, $method, $data);
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

    public static function decryptForCustomerArea($encryptedKey){
        try {
            $salt = env('APP_SALT');
            $c = unserialize(base64_decode($encryptedKey));
            $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
            $iv = substr($c, 0, $ivlen);
            $ciphertext_raw = substr($c, $ivlen);
            $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $salt, $options = OPENSSL_RAW_DATA, $iv);
            $original_plaintext = urldecode(base64_decode($original_plaintext));
            return $original_plaintext;
        }catch (\Exception $e){
            return null;
        }
    }

}
