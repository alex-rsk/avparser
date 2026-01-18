<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HeadlessBrowser\HeadlessBrowserWrapper;
use Illuminate\Support\Facades\Log;
use HeadlessChromium\Page;
use App\Models\Ad;
use App\Models\SearchQuery;
use TwoCaptcha\TwoCaptcha;

class CollectPagesCommand extends Command
{
    protected $browser = null;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'browser:collect-pages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Collect pages with ads';

    private function solve(string $url, string $pageUrl) {
        $urlParams = parse_url($url, PHP_URL_QUERY);
        parse_str($urlParams, $params);
        $apiKey = config('services.rucaptcha.api_key');
        $solver = new TwoCaptcha($apiKey);
        $solverParams  = [
            'url' => $pageUrl,
            'challenge' => $params['challenge'],
            'captchaId' => $params['captcha_id'],
        ];
        dump($solverParams);
        try {
            //$result = $solver->geetest_v4($solverParams);

            dump($result);
            //sleep(random_int(2, 4));
            
        } catch (\Exception $e) {
            Log::channel('browser')->error('Error solving  captcha: '.$e->getMessage());
        }        

    }
    

    private function initBrowser() 
    {
        $params            = [
            //путь к библиотеке скриптов
            'scripts'               => resource_path('js/headless-scripts'),
            // режим без GUI
            'headless'              => false,
            //строка юзерагента
            'user_agent'            => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.6778.80 Safari/537.36',
            //имя инстанса
            'user_data_dir'         => 'avito1',
            //открываемый URL
            'url'                   => 'about:blank',
            //порт для связи с браузером
            //'debug_port'            => $this->portNumber + $this->instance - 1,
            //очистка кэша скрипта    
            'clear_script_cache'    => true,
            //каждый раз стартовать принудительно новый инстанс
            'fresh_start'           => true,
            //событие загрузки страницы, после которого разрешено выполнять скрипты
            'onload_event'          => 'page',
            //Отключить уведомления
            'disable_notifications' => true,
            //скрипт предзагрузки
            'preloadScript'         => null,
            //ID потока
            'thread_id'             => 'test-thread',
            //прокси
            //'proxy'                 => $this->account['proxy'],
        ];


        $this->browser = HeadlessBrowserWrapper::factory($params);
/*
        $pattern = [
            "urlPattern" => "*",
            "requestStage"=> "Response",
            'resourceType' => 'Script'
        ];
        $this->browser->startRequestIntercept([$pattern], function($request) {
            dump('intercepted');
            dump($request['request']['url']);
        });
*/

        $pattern = [
            "urlPattern" => "https://*gcaptcha4.geetest.com/*",
        ];
        $this->browser->startRequestIntercept([$pattern], function($request) {
            $requestUrl = $request['request']['url'];
            $this->solve($requestUrl, 'https://avito.ru');
        });

        $this->browser->navigateTab(0, 'https://avito.ru/');
        sleep(3);
    }

    private function getCaptchaResult() {

    }

    private function search(string $query) {
        $searchSelector = '#bx_search > div.index-suggest-Me4w_ > div > div > label > div > div > div > input';
        $tab = $this->browser->getTab(0);
        sleep(random_int(1, 3));
        $tab->mouse()->find($searchSelector)->click();

        $this->emulateHumanType($tab, $query, 3);
        sleep(random_int(1, 3));
        $tab->keyboard()->typeRawKey("\r");
        sleep(random_int(4, 6));
    }

    private function emulateHumanType(Page $tab,  string $text, int $gaps = 1) {
        $length = mb_strlen($text);
        $gapPositions = [];
        $previousGap = 0;    
        for ($i = 0; $i < $gaps; $i ++) {
            $nextGap =   random_int($previousGap+1, $length/2+$previousGap);
            $gapPositions[] = $nextGap-$previousGap;
            $previousGap = $nextGap;
        }
        
        $pieces  = [];
        $ppos = 0;
     
        foreach ($gapPositions as $gp) {
            $pieces[] = ['text' => mb_substr($text, $ppos, $gp), 'delay' => random_int(100, 200) ];
            $ppos += $gp;
        }

        if ($gp < $length) {
            $pieces[] = ['text' => mb_substr($text, $ppos, $length), 'delay' => random_int(80,150) ];
        }

        foreach ($pieces as $piece) {
            $tab->keyboard()->setKeyInterval($piece['delay']);
            $tab->keyboard()->typeText($piece['text']);
        }
    }

    private function collectAds(int $page = 1) : ?array
    {        
        if ($page > 1) {
            dump("Jump to page ".$page);
            $pageSelector = 'a[data-value="'.$page.'"]';
            $currentUrl = $this->browser->runScript(0, 'get_current_url', []);
            $urlParts = parse_url($currentUrl);
            $url = $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'];
            
            $queryParts = preg_split('~[\?\&]~', $urlParts['query']);
            $params = [];
            foreach ($queryParts as $part) {
                $params[] = (fn($el) => preg_match('~^([^=]+)=(.+)$~', $part, $matches) ? [$matches[1] => $matches[2]] : null)($part);
            }
            $params  = array_merge(...$params);
            dump($params);
            $newParams = array_merge(['localPriority' => 0,'p' => $page,], ['q' => $params['q']]);
            $newUrl = $url . '?' . http_build_query($newParams);
            dump("New URL:".$newUrl);
            $this->browser->navigateTab(0, $newUrl);


        
            //https://www.avito.ru/krasnodar/dlya_doma_i_dachi?q=...
            //https://www.avito.ru/krasnodar/dlya_doma_i_dachi?localPriority=0&p=2&q=...

           //$this->browser->runScript(0, 'avito/jump_to_page', ['elementSelector' => $pageSelector, 'Xpath' => true]);
            sleep(5);
        }

        $itemsSelector =  '//div[@data-marker="item"]//h2/a[@itemprop="url"]';

        $items = $this->browser->runScript(0, 'avito/find_ads', ['elementSelector' => $itemsSelector, 'Xpath' => true]);

        $result = false;

        if ($items) {
            return $items;
        }
        else {
            Log::channel('daily')->warning('no items');
            return [];
        }
    
    }

    private function getTotalPages() : ?int 
    {
        $selector = '//ul[@data-marker="pagination-button"]/li[position()=last()-1]';
        $count = $this->browser->runScript(0, 'get_element_content', ['elementSelector' => $selector, 'Xpath' => true]);
        dump('Pages count: ' . $count);             
        return $count ? intval($count)  : null;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $searchQueries = SearchQuery::query()->select(['id', 'query_text'])->orderBy('observed_at', 'DESC')->limit(1)->get();
        $this->initBrowser();
        sleep(10);
        try {
            if ($this->browser->getTab(0)->mouse()->find('#geetest_captcha')) {
                dump("Captcha detected!");
                dump("click!");
                $this->browser->getTab(0)->mouse()->find('button')->click();
                sleep(5);
                exit;
            }
        } catch (\Throwable $th) {
            dump($th->getMessage());
        }
       foreach ($searchQueries as $query) {

           $this->search($query->query_text);

           $totalPages = $this->getTotalPages();

           
           for ($i=1 ; $i < $totalPages; $i++ ) {
                $items = $this->collectAds($i);
                Log::channel('daily')->debug('Parsed '.count($items).' ads');
                $query->update(['observed_at' => now(), 'total_pages' => $totalPages,]);

                $preparedItems = Ad::prepareInsertData($items, $query->id);
                
                Ad::insert($preparedItems);
                Log::channel('daily')->debug('Page '.($i+1).' processed');
                sleep(random_int(2, 4));
           }
       }
    }
}
