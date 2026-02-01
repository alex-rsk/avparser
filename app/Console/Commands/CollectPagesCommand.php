<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HeadlessBrowser\HeadlessBrowserWrapper;
use Illuminate\Support\Facades\Log;
use HeadlessChromium\Page;
use App\Models\Ad;
use App\Models\SearchQuery;
use App\Services\HelperService;

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


    private function log(mixed $message, string $level = 'debug') {

        if (is_array($message) || is_object($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT, 3);
        }

        Log::channel('browser')->$level($message);
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

        $pattern = [
            "urlPattern" => "https://*gcaptcha4.geetest.com/*",
        ];
        $this->browser->startRequestIntercept([$pattern], function($request) {
            $requestUrl = $request['request']['url'];
            //$this->solve($requestUrl, 'https://avito.ru');
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
            $this->log("Jump to page ".$page);
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
            $this->log($params);
            $newParams = array_merge(['localPriority' => 0,'p' => $page,], ['q' => $params['q']]);
            $newUrl = $url . '?' . http_build_query($newParams);
            $this->log("New URL:".$newUrl);
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
            $this->log('no items', 'warning');
            return [];
        }
    
    }

    private function getTotalPages() : ?int 
    {
        $selector = '//ul[@data-marker="pagination-button"]/li[position()=last()-1]';
        $count = $this->browser->runScript(0, 'get_element_content', ['elementSelector' => $selector, 'Xpath' => true]);
        $this->log('Pages count: ' . $count);
        return $count ? intval($count)  : null;
    }


    private function processCaptcha() {
        echo "Captcha detected!".PHP_EOL;
        $this->browser->getTab(0)->mouse()->find('button')->click();
        sleep(15);                
        //Найти элемент div.geetest_bg - это подложка. Картинка в свойстве background-image
        $geeBgCoords = $this->browser->runScript(0, 'get_element_coords', [
            'elementSelector' => 'div.geetest_bg',
            'Xpath' => false
        ]);

        $this->log("Geetest background coords");
        $this->log($geeBgCoords);

        //Найти элемент div.geetest_slice_bg - это пазл. Картинка в свойстве background-image getBoundingClientRect получит bbox
        $geeSliceCoords = $this->browser->runScript(0, 'get_element_coords', [
            'elementSelector' => 'div.geetest_slice_bg',
            'Xpath' => false
        ]);

        $this->log("Geetest Puzzle coords:");
        $this->log($geeSliceCoords);

        //div.geetest_btn - это  ползунок. getBoundingClientRect()  - получит bbox
        $geeButtonCoords = $this->browser->runScript(0, 'get_element_coords', [
            'elementSelector' => 'div.geetest_btn',
            'Xpath' => false
        ]);

        $this->log("Geetest Button coords");
        $this->log($geeButtonCoords);

        if (!($geeBgCoords && $geeSliceCoords && $geeButtonCoords)) {
            throw new \Exception("Geetest elements not found!");                    
        }

        $puzzleHeight = 80;
        $puzzleWidth = 80;

        $puzzleRelativeTop = (int)(ceil(floatval($geeSliceCoords[1])-floatval($geeBgCoords[1])));
        $this->log("Puzzle relative Y: " . $puzzleRelativeTop);

        $cssUrlRegexp = '~url\([\"\']([^\)]+)[\"\']\)~';
        
        //Get puzzle image
        $imageSmall =$this->browser->runScript(0, 'get_element_bgimage', [
            'elementSelector' => 'div.geetest_slice_bg',
            'Xpath' => false
        ]);
        $this->log('CSS for small:'.$imageSmall);
        $smallUrl = preg_match($cssUrlRegexp, $imageSmall, $matches) ? $matches[1] : null;
        if (!$smallUrl) {
            throw new \Exception("Can't parse puzzle image URL!");
        }
        $this->log('Small URL: ' . $smallUrl);

        //Get background image
        $imageLarge = $this->browser->runScript(0, 'get_element_bgimage', [
            'elementSelector' => 'div.geetest_bg',
            'Xpath' => false
        ]);
        $this->log('Image large:'.$imageLarge);
        $largeUrl = preg_match($cssUrlRegexp, $imageLarge, $matches) ? $matches[1] : null;
        if (!$largeUrl) {
            throw new \Exception("Can't parse background image URL!");
        }
        $this->log('Large URL: ' . $largeUrl);


        $this->log("Downloading..");
        $smDestFilename = base_path('capsolver/input/small.png');
        HelperService::downloadFile($smallUrl, $smDestFilename);
    
        $lgDestFilename = base_path('capsolver/input/large.png');
        HelperService::downloadFile($largeUrl, $lgDestFilename);
                
        $this->log('Captcha background: '. $imageLarge);

        $solverCmd = base_path('dist/capsolver'). ' ' . $smDestFilename . ' ' . $lgDestFilename . ' ' . $puzzleRelativeTop;
        $this->log($solverCmd);

        $output = shell_exec($solverCmd);
        $cleanedOutput = preg_replace('~\s+~', '', $output);
        if (!is_numeric($cleanedOutput)) {
            $this->log('Dirty output: '.$cleanedOutput, 'warning');
        }
        
        //X- координата места для пазла, относительно X- координаты подложки
        $targetX = intval($cleanedOutput);
        //$absoluteTargetX = 
        $this->log('X-coordinate :' .$targetX);
        
        $steps = random_int(4, 8);
        $initialOffsetX = random_int(5, 10);
        $initialOffsetY = random_int(2, 10);
        $startX = intval($geeButtonCoords[0])+$initialOffsetX;
        $startY = intval($geeButtonCoords[1])-80 ; //80 is magic number
        
        
        $this->log('Start X:'. $startX.' Y:'.$startY);
        sleep(random_int(2,5));
        $this->log("Pointing, steps " . $steps);
        $this->browser->getTab(0)->mouse()->move($startX, $startY, ['steps' => $steps])->press(); 
        
        sleep(random_int(2,5));
        //Test
        $distance = $targetX;
        $steps = random_int(4, 8);
        $this->log("Dragging");
        $this->browser->getTab(0)->mouse()->move($startX + $distance, $startY, ['steps' => $steps])->release();

    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $searchQueries = SearchQuery::query()->select(['id', 'query_text'])->orderBy('observed_at', 'DESC')->limit(1)->get();
        $this->initBrowser();
        sleep(5);
        try {
            if ($this->browser->getTab(0)->mouse()->find('#geetest_captcha')) {                
                $this->processCaptcha();
                return;
            }
        } catch (\Throwable $th) {
            $this->log('No captcha', 'debug');
        }

        foreach ($searchQueries as $query) {
           $this->search($query->query_text);
           $totalPages = $this->getTotalPages();
           for ($i=1 ; $i < $totalPages; $i++ ) {
                $items = $this->collectAds($i);
                $this->log('Parsed '.count($items).' ads');
                $query->update(['observed_at' => now(), 'total_pages' => $totalPages,]);

                $preparedItems = Ad::prepareInsertData($items, $query->id);
                
                Ad::insert($preparedItems);
                $this->log('Page '.($i+1).' processed');
                sleep(random_int(2, 4));
           }
       }
    }
}
