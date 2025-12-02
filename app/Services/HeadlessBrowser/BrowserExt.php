<?php
/**
 * Расширение класса Browser
 * 
 * @author Alex Ryassky 
 * @copyright (c) 2020, M1 <m1-shop.ru> 
 */
namespace Components\GHC;

use HeadlessChromium\Communication\Message;
use HeadlessChromium\Exception\OperationTimedOut;
use HeadlessChromium\Browser;
use Components\GHC\PageExt;

class BrowserExt extends Browser
{
    
    public function close()
    {
        $this->sendClosingMessage();
    }

    /**
     * Закрыть браузер
     * 
     * @throws OperationTimedOut
     */
    public function sendClosingMessage()
    {
        $r = $this->connection->sendMessageSync(new Message('Browser.close'));
        if (!$r->isSuccessful()) {
            // log
            $this->connection->getLogger()->debug('process: ✗ could not close gracefully');
            throw new \Exception('cannot close, Browser.close not supported');
        }
    }
    
    /**
     * Создает новую страницу
     * 
     * @throws NoResponseAvailable
     * @throws CommunicationException
     * @throws OperationTimedOut
     * 
     * @return Components\GHC\PageExt
     */
    public function createPageExt(): PageExt
    {

        // page url
        $params = ['url' => 'about:blank'];

        // create page and get target id
        $response = $this->connection->sendMessageSync(new Message('Target.createTarget', $params));
        $targetId = $response['result']['targetId'];

        // todo handle error

        $target = $this->getTarget($targetId);
        if (!$target) {
            throw new \RuntimeException('Target could not be created for page.');
        }

        // get initial frame tree
        $frameTreeResponse = $target->getSession()->sendMessageSync(new Message('Page.getFrameTree'));

        // make sure frame tree was found
        if (!$frameTreeResponse->isSuccessful()) {
            throw new ResponseHasError('Cannot read frame tree. Please, consider upgrading chrome version.');
        }

        // create page
        $page = new PageExt($target, $frameTreeResponse['result']['frameTree']);

        // Page.enable
        $page->getSession()->sendMessageSync(new Message('Page.enable'));

        // Network.enable
        $page->getSession()->sendMessageSync(new Message('Network.enable'));

        // Runtime.enable
        $page->getSession()->sendMessageSync(new Message('Runtime.enable'));

        // Page.setLifecycleEventsEnabled
        $page->getSession()->sendMessageSync(new Message('Page.setLifecycleEventsEnabled', ['enabled' => true]));

        // add prescript
        if ($this->pagePreScript) {
            $page->addPreScript($this->pagePreScript);
        }

        return $page;
    }
    
     /**
     * Аттачится к вкладке
     * 
     * @throws NoResponseAvailable
     * @throws CommunicationException
     * @throws OperationTimedOut
     * 
     * @return Components\GHC\PageExt
     */
    public function attachToPage($number): ?PageExt
    {
        $targetsResponse = $this->connection->sendMessageSync(new Message('Target.getTargets'));
        $tabs = [];
        if ($targetsResponse->isSuccessful()) {
            foreach ($targetsResponse['result']['targetInfos'] as $target) {
                if ($target['type'] === 'page') {
                        $tabs[] = $target;
                }
            }
        }        
        if (!isset($tabs[$number]['targetId']))
        {
            return null;
        }
        $targetId = $tabs[$number]['targetId'];
        $sessionId = $this->connection->sendMessageSync(new Message('Target.attachToTarget', 
            ['targetId' => $targetId ]));       
        $target = $this->getTarget($targetId);
        if (!$target) {
            throw new \RuntimeException('Target could not be created for page.');
        }

        $frameTreeResponse = $target->getSession()->sendMessageSync(new Message('Page.getFrameTree'));
       
        if (!$frameTreeResponse->isSuccessful()) {
            throw new ResponseHasError('Cannot read frame tree. Please, consider upgrading chrome version.');
        }
        
        $page = new PageExt($target, $frameTreeResponse['result']['frameTree']);
        $page->getSession()->sendMessageSync(new Message('Page.enable'));
        $page->getSession()->sendMessageSync(new Message('Network.enable'));
        $page->getSession()->sendMessageSync(new Message('Runtime.enable'));
        $page->getSession()->sendMessageSync(new Message('Page.setLifecycleEventsEnabled', ['enabled' => true]));

        if ($this->pagePreScript) {
            $page->addPreScript($this->pagePreScript);
        }        
        return $page;
    }

}
