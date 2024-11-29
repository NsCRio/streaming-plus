<?php

namespace App\Services\Scraper\WebDriver;

use Facebook\WebDriver\Chrome\ChromeDriverService;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\DriverCommand;
//use Facebook\WebDriver\Remote\Service\DriverCommandExecutor;
use Facebook\WebDriver\Remote\WebDriverCommand;

class ChromeDriver extends \Facebook\WebDriver\Chrome\ChromeDriver
{
    public static function start(DesiredCapabilities $desired_capabilities = null, ChromeDriverService $service = null)
    {
        if ($desired_capabilities === null) {
            $desired_capabilities = DesiredCapabilities::chrome();
        }
        if ($service === null) {
            $service = ChromeDriverService::createDefaultService();
        }
        $executor = new DriverCommandExecutor($service);
        $driver = new static();
        $driver->setCommandExecutor($executor)
            ->startSession($desired_capabilities);

        return $driver;
    }

    public function startSession($desired_capabilities)
    {
        $command = new WebDriverCommand(
            null,
            DriverCommand::NEW_SESSION,
            array(
                'capabilities' => $desired_capabilities->toArray(),
            )
        );
        $response = $this->executor->execute($command);
        $this->setSessionID($response->getSessionID());
    }

}
