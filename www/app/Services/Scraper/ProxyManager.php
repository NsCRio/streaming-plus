<?php

namespace App\Services\Scraper;

use App\Http\Controllers\Controller;
use stdClass;
use Symfony\Component\DomCrawler\Crawler;

/**
 *
 * SOCKS5
 *
 * */
define('SOCKS5_ADDRESSES', ['proxy.torguard.com']);
define('SOCKS5_PORTS', [1080,1085,1090]);
define('SOCKS5_HOST', 'torguard.com');

/**
 *
 * HTTP
 *
 * */
define('HTTP_ADDRESSES',
    [
        'bul.torguard.com',
        'fin.torguard.com',
        'frank.gr.torguard.com',
        'ice.torguard.com',
        'ire.torguard.com',
        'nl.torguard.com',
        'ro.torguard.com',
        'swe.torguard.com',
        'swiss.torguard.com',
        'bg.torguard.com',
        'hg.torguard.com',
        'fr.torguard.com',
        'pl.torguard.com',
        'czech.torguard.com',
        'gre.torguard.com',
        'it.torguard.com',
        'sp.torguard.com',
        'no.torguard.com',
        'por.torguard.com',
        'den.torguard.com',
        'lux.torguard.com',
        'slk.torguard.com'
    ]
);
define('HTTP_PORTS', [1080,1085,1090]);
define('HTTP_HOST', 'torguard.com');

/**
 *
 * HTTPS/SSL
 *
 * */
define('SSL_ADDRESSES',
    [
        'aus.secureconnect.me',
        'bg.secureconnect.me',
        'bul.secureconnect.me',
        'cz.secureconnect.me',
        'dn.secureconnect.me',
        'fn.secureconnect.me',
        'fr.secureconnect.me',
        'ger.secureconnect.me',
        'gre.secureconnect.me',
        'hg.secureconnect.me',
        'ice.secureconnect.me',
        'ire.secureconnect.me',
        'it.secureconnect.me',
        'md.secureconnect.me',
        'nl.secureconnect.me',
        'no.secureconnect.me',
        'pl.secureconnect.me',
        'pg.secureconnect.me',
        'ro.secureconnect.me',
        'ru.secureconnect.me',
        'serbia.secureconnect.me',
        'slk.secureconnect.me',
        'sp.secureconnect.me',
        'swe.secureconnect.me',
        'swiss.secureconnect.me',
        'tk.secureconnect.me',
        'ukr.secureconnect.me',
        'uk.man.secureconnect.me',
        'uk.secureconnect.me'
    ]
);
define('SSL_PORTS', [23,592,778,489,282,993,465,7070]);
define('SSL_HOST', 'secureconnect.me');


class ProxyManager extends Controller
{
    static $proxy_auth = "eurekaintergroup@gmail.com:1eupro/xyka1";

    /** Seleziona il Proxy */
    public static function getProxy(String $type='http', String $country=null, Int $port=null){
        switch ($type) {
            case 'http':
                return self::getHTTPProxy($country, $port);
                break;
            case 'socks5':
                return self::getSOCKS5Proxy($port);
                break;
            case 'ssl':
                return self::getSSLProxy($country, $port);
                break;
            default:
                return null;
        }
    }

    /** Socks5 Proxy Configuration */
    protected static function getSOCKS5Proxy(Int $port=null){
        $addresses = SOCKS5_ADDRESSES;
        $ports = SOCKS5_PORTS;
        $index_addr = rand(0,count($addresses)-1); //Porta casuale
        $index_port = rand(0,count($ports)-1); //Porta casuale
        /** Ritorno l'oggetto Proxy */
        $proxy = new stdClass();
        $proxy->address = $addresses[$index_addr];
        $proxy->port = isset($port) ? $port : $ports[$index_port];
        $proxy->auth = self::$proxy_auth;
        $proxy->agent = self::getRandomAgent();
        $proxy->type = CURLPROXY_SOCKS5;
        return $proxy;
    }

    /** HTTP Proxy Configuration */
    protected static function getHTTPProxy(String $country=null, Int $port=null, String $continent=null){
        $addresses = HTTP_ADDRESSES;
        $ports = HTTP_PORTS;
        $index_addr = rand(0,count($addresses)-1); //Indirizzo casuale
        $index_port = rand(0,count($ports)-1); //Porta casuale
        /** Ritorno l'oggetto Proxy */
        $proxy = new stdClass();
        $proxy->address = isset($country) ? $country.'.'.HTTP_HOST : $addresses[$index_addr];
        $proxy->port = isset($port) ? $port : $ports[$index_port];
        $proxy->auth = self::$proxy_auth;
        $proxy->agent = self::getRandomAgent();
        $proxy->type = CURLPROXY_HTTP;
        return $proxy;
    }

    /** HTTPS/SSL Proxy Configuration */
    protected static function getSSLProxy(String $country=null, Int $port=null){
        $addresses = SSL_ADDRESSES;
        $ports = SSL_PORTS;
        $index_addr = rand(0,count($addresses)-1); //Indirizzo casuale
        $index_port = rand(0,count($ports)-1); //Porta casuale
        /** Ritorno l'oggetto Proxy */
        $proxy = new stdClass();
        $proxy->address = isset($country) ? $country.'.'.SSL_HOST : $addresses[$index_addr];
        $proxy->port = isset($port) ? $port : $ports[$index_port];
        $proxy->auth = self::$proxy_auth;
        $proxy->agent = self::getRandomAgent();
        $proxy->type = CURLPROXY_HTTP;
        return $proxy;
    }

    /** Ottiene un User Agent Random */
    public static function getRandomAgent(){
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
        $agent = $agent_random[rand(1,8)];
        return $agent;
    }
}
