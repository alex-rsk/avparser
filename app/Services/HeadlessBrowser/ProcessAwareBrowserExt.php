<?php
/**
 * Расширение класса ProcessAwareBrowser
 * @author Alex Ryassky 
 * @copyright (c) 2020, M1 <m1-shop.ru> 
 */

namespace Components\GHC;

use Components\GHC\BrowserExt;
use Components\GHC\BrowserProcessExt;
use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Browser\ProcessAwareBrowser;
use Symfony\Component\Process\Process;


class ProcessAwareBrowserExt extends BrowserExt
{
    
    /**
     * @var BrowserProcess
     */
    protected $browserProcess;
    
    public function __construct(Connection $connection, BrowserProcessExt $browserProcess)
    {
        parent::__construct($connection);

        $this->browserProcess = $browserProcess;
    }

    
    public function close()
    {
        $this->browserProcess->kill();
    }

    /**
     * @return string
     */
    public function getSocketUri()
    {
        return $this->browserProcess->getSocketUri();
    }
    
    /**
     * Получить командную строку
     * 
     * @return string
     */
    public function getCommandLine()
    {
        return $this->browserProcess ?
            $this->browserProcess->getCommandLine() : false;
    }
}
