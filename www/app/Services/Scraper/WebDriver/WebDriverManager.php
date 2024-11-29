<?php

namespace App\Services\Scraper\WebDriver;

use App\Services\Scraper\ScraperManager;
use App\Services\Scraper\WebDriver;
use Facebook\WebDriver\Chrome\ChromeDriverService;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Firefox\FirefoxOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\DriverCommand;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\WebDriverBrowserType;
use Facebook\WebDriver\Remote\WebDriverCapabilityType;
use Facebook\WebDriver\Remote\WebDriverCommand;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverPlatform;
use Symfony\Component\DomCrawler\Crawler;

class WebDriverManager extends RemoteWebDriver
{
    public static function driverFirefox($headless=true, $port="4444"){

        $page = ScraperManager::getPage('https://streamingcommunity.computer/iframe/10574', null, false, false, false);
        dd($page);
        if(isset($page) && $page->response_code == 200){
            dd($page->html);
            $crawler = new Crawler($page->html);
        }


        //putenv('WEBDRIVER_FIREFOX_DRIVER=/usr/local/bin/geckodriver');
        //shell_exec('/usr/local/bin/geckodriver');

        //$desiredCapabilities = DesiredCapabilities::firefox();
        //$desiredCapabilities = DesiredCapabilities::chrome();
        $desiredCapabilities = DesiredCapabilities::firefox();

        // Disable accepting SSL certificates
        //$desiredCapabilities->setCapability('acceptSslCerts', false);

        // Add arguments via FirefoxOptions to start headless firefox
//        $firefoxOptions = new FirefoxOptions();
//        $firefoxOptions->addArguments(['-headless']);
//        $desiredCapabilities->setCapability(FirefoxOptions::CAPABILITY, $firefoxOptions);
        //dd($desiredCapabilities->toArray());

        //$profile = new FirefoxProfile();
        //$profile->addExtension('/var/src/video_downloadhelper-9.1.0.44.xpi');
        //$desiredCapabilities->setCapability(FirefoxDriver::PROFILE, $profile);

        //https://addons.mozilla.org/firefox/downloads/file/4347883/video_downloadhelper-9.1.0.44.xpi

        //$desiredCapabilities->setPlatform('linux');
        //dd($desiredCapabilities->toArray());
        $capabilities = [
            "alwaysMatch" => [
                "cloud:user" => "alice",
                "cloud:password" => "hunter2",
                "platformName" => "linux"
            ],
            "firstMatch" => [
                ["browserName" => "firefox"],
                ["browserName" => "chrome"],
                ["browserName" => "edge"]
            ]
        ];

        //$driver = FirefoxDriver::create($desiredCapabilities);
        //$serverUrl = 'http://localhost:4444';
        $serverUrl = 'http://streamingplusselenium:4444';
        $driver = self::create($serverUrl, $capabilities);

//        if(Cache::has('driver-session-id')){
//            $driver->setSessionId(Cache::get('driver-session-id'));
//        }else{
//            Cache::put('driver-session-id', $driver->getSessionId());
//        }

        $driver->get('https://streamingcommunity.computer/iframe/10574');
        $driver->wait('30');
//        $driver->click();
//        $html = $driver->getPageSource();
//        dd($html);

        $iframe = $driver->findElement(WebDriverBy::xpath('/html/body/iframe'));
//        $driver->switchTo()->frame($iframe);
//        $script = $driver->findElement(WebDriverBy::xpath('/html/body/script[1]/text()'));

        //$driver->findElement(WebDriverBy::xpath('//*[@id="player"]/div[2]/div[13]/div[1]/div/div/div[2]/div'))->click();

        if(isset($page) && $page->response_code == 200){
            $crawler = new Crawler($page->html);
        }

        $driver->close();
        $driver->quit();

        dd($iframe);

        //https://vixcloud.co/playlist/273108?type=video&rendition=1080p&token=wMVU_x5nwFgfGi6IkbPIfw&expires=1737915418&b=1
        //https://vixcloud.co/embed/273108?token=182149f47198846e8fa0f6704f94553e&t=MTk5Mg%3D%3D&referer=1&expires=1732731428&canPlayFHD=1

        //https://vixcloud.co/playlist/273108?type=video&rendition=1080p&token=wMVU_x5nwFgfGi6IkbPIfw&expires=1737915418&b=1
        //https://vixcloud.co/playlist/273108?type=audio&rendition=ita&token=mejY--EXq8A0d4RBOWl67g&expires=1737915418&b=1

        //https://vixcloud.co/playlist/273108?b=1&token=a07bda3a0488378305d354fb21dab45f&expires=1737915418&h=1

        //https://vixcloud.co/playlist/273108?type=video&rendition=1080p&token=182149f47198846e8fa0f6704f94553e&expires=1732731428&b=1

        //FUNZIONAMENTO DEI LINK

        //Link dell'iframe in full screen
        //https://streamingcommunity.computer/iframe/10574

        //Link dell'inframe embedded
        //https://vixcloud.co/embed/273108?token=e74027881891d6ebec3961ebc1fb539c&t=MTk5Mg%3D%3D&referer=1&expires=1732732658&canPlayFHD=1

        //Link della riproduzione URL
        //https://vixcloud.co/playlist/273108?b=1&token=da8f612c5e99bf574d429d0dcbf0452a&expires=1737916648&h=1

        return $html;
    }

    /** Get Configured Chrome Driver */
    public static function driverChrome($headless=1, $port="4444"){
        ini_set('memory_limit', '-1');
        set_time_limit(3800);
        ini_set('default_socket_timeout', 1200);
        //Indirizzo server Selenium o Chromedriver
        //$host = 'http://localhost:4444';
        // Create an instance of ChromeOptions:

        $chromeOptions = new ChromeOptions();
        //Avvio il driver senza schermata
        $chromeOptions->setBinary('/usr/local/bin/chrome');
        if($headless){
            $chromeOptions->addArguments(["--headless"]); //Senza schermata grafica
        }
        $chromeOptions->addArguments([
            //"--port=".$port,
            "--window-size=1024,768",
            "--no-sandbox",
            "--disable-dev-shm-usage",
            "--disable-infobars",
            "--disable-extensions",
            "--disable-gpu",
            "--disable-dev-shm-usage",
            "--log-level=3",
            "--remote-debugging-pipe"
        ]);

        //$proxy = ProxyManager::getProxy();

        $capabilities = new DesiredCapabilities([
            WebDriverCapabilityType::BROWSER_NAME => WebDriverBrowserType::CHROME,
            WebDriverCapabilityType::PLATFORM => WebDriverPlatform::LINUX
//            WebDriverCapabilityType::PROXY => [
//                'proxyType' => 'manual',
//                'httpProxy' => $proxy->url,
//                'sslProxy' => $proxy->url,
//            ],
        ]);
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

        //putenv('WEBDRIVER_CHROME_DRIVER=C:/webdriver/chromedriver.exe');
        putenv('WEBDRIVER_CHROME_DRIVER=/usr/local/bin/chromedriver');
        putenv('webdriver.chrome.driver=/usr/local/bin/chromedriver');
        //dd('ok');
        $driverService = new ChromeDriverService('/usr/local/bin/chromedriver', $port, [
            "--port=".$port,
            "--no-sandbox",
            "--disable-dev-shm-usage",
            "--disable-infobars",
            "--disable-extensions",
            "--disable-gpu",
            "--disable-dev-shm-usage",
            "--log-level=3",
            "--remote-debugging-pipe"
        ]);
        //dd($driverService);
        //$driverService = ChromeDriverService::createDefaultService();
        //dd(getenv('WEBDRIVER_CHROME_DRIVER'));

        $driver = ChromeDriver::start($capabilities, $driverService);
        //$serverUrl = 'http://streamingplusselenium:4444';
        //$driver = ChromeDriver::create($serverUrl, $capabilities);
        //$driver = RemoteWebDriver::create($serverUrl, DesiredCapabilities::chrome());
        //Schermata full screen
        $driver->manage()->window()->maximize();
        $driver->get('https://google.it');

        $html = $driver->getPageSource();
        $driver->close();
        $driver->quit();

        return $html;
    }


    public static function create(
        $url = 'http://localhost:4444/wd/hub',
        $desired_capabilities = null,
        $connection_timeout_in_ms = null,
        $request_timeout_in_ms = null,
        $http_proxy = null,
        $http_proxy_port = null
    ) {
        $url = preg_replace('#/+$#', '', $url);

        // Passing DesiredCapabilities as $desired_capabilities is encouraged but
        // array is also accepted for legacy reason.
        if ($desired_capabilities instanceof DesiredCapabilities) {
            $desired_capabilities = $desired_capabilities->toArray();
        }

        $executor = new HttpCommandManager($url, $http_proxy, $http_proxy_port);
        if ($connection_timeout_in_ms !== null) {
            $executor->setConnectionTimeout($connection_timeout_in_ms);
        }
        if ($request_timeout_in_ms !== null) {
            $executor->setRequestTimeout($request_timeout_in_ms);
        }

        $command = new WebDriverCommand(
            null,
            DriverCommand::NEW_SESSION,
            array('capabilities' => $desired_capabilities)
        );

        $response = $executor->execute($command);

        $driver = new static();
        $driver->setSessionID($response->getSessionID())
            ->setCommandExecutor($executor);

        return $driver;
    }
}
