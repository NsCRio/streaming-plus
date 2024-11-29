<?php

namespace App\Services\Scraper;

use Illuminate\Support\Facades\Cache;
use Exception;
use stdClass;

class ScraperManager
{
    /** Ottiene la pagina */
    public static function getPage(string $url, ?string $prev_url=null, ?bool $post=false, ?bool $cached=true, ?bool $proxed=true, ?string $proxy_type="socks5"){
        try{
            /** Controllo se la pagina è rimasta nella cache **/
            if($cached){
                try{
                    if(Cache::has('scraped-'.md5($url))) {
                        $page = Cache::get('scraped-'.md5($url));
                        if($page->start_url == $url){
                            $page->cached = 1;
                            return $page;
                        }
                    }
                }catch(Exception $e){}
            }
            $agent = ProxyManager::getRandomAgent();
            ini_set('default_socket_timeout', 240);
            ini_set('Accept_Language', 'it-it');
            ini_set('user_agent', $agent);
            /** Prendo dati Proxy **/
            $proxy = ProxyManager::getProxy($proxy_type, 'it');
            $url = str_replace("\r", "", str_replace("\"", "", $url));
            $dominio = parse_url($url)['host'];
            $prev_url = isset($prev_url) ? $prev_url : 'https://www.google.it/search?q='.$dominio;
            /** Avvio la richiesta cURL */
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            if($post){ /** Richiesta in POST */
                curl_setopt($ch, CURLOPT_POST, 1);
            }
            if($proxed){ /** Con Proxy */
                curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy->type);
                curl_setopt($ch, CURLOPT_PROXY, $proxy->address); //PROXY
                curl_setopt($ch, CURLOPT_PROXYPORT, $proxy->port);
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy->auth); //AUTH
            }
            /** Imposto l'header */
            $header = [
                'User-Agent: '.$agent,
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Accept-Language: it-IT,it;',
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
                'Referer: '.$prev_url
            ];
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 180); //Timeout di 3 Minuti
            curl_setopt($ch, CURLOPT_USERAGENT, $agent); //Agent
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 18);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_ENCODING, 1);
            /** Creo un nuovo oggetto pagina */
            $page = new stdClass();
            $html = curl_exec($ch);
            $page->error = false;
            $page->response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $page->start_url = $url;
            $page->final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $page->previous_url = $prev_url;
            $page->header = $header;
            if($proxed){
                $page->proxy = $proxy;
            }
            $page->request_ip = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
            $page->request_time = round(curl_getinfo($ch, CURLINFO_TOTAL_TIME), 1);
            $page->cached = 0;
            $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            $page->response_size = $size != -1 ? round($size/1024).' Kb' : null;
            $page->html = $html;
            //$page->html = $html != false ? StringHelper::beautifyHtml($html) : false;
            $page->error = $html == false ? curl_error($ch) : null;
            curl_close($ch);
            /** Salvo in cache */
            try{
                if($page->response_code == 200){
                    if($page->html != false){
                        Cache::put('scraped-'.md5($url), $page, 600); //Salvo la pagina 10 minuti in cache 600 sec = 10 min
                    }
                }
            }catch(Exception $e){}
            /** Ritorno l'oggetto */
            return $page;
        }
        catch(Exception $e){
            return null;
        }
    }

    /** Prende un User Agent Random */
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

    /** Filter by Property */
    public static function getProperty($crawler, $filter) {
        try{
            $func = $crawler;
            foreach(explode('->', $filter) as $filter){
                $property = StringHelper::get_string_between($filter, "('", "')");
                $remove = "('".$property."')";
                $filter = str_replace($remove, '', $filter);
                $filter = str_replace('(', '', str_replace(')', '', $filter));
                $func = call_user_func(array($func, $filter), $property);
            }
            return $func;
        }
        catch(Exception $e){
            return null;
        }
    }
}
