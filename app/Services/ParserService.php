<?php

namespace App\Services;

use App\Services\HeadlessBrowser\HeadlessBrowserWrapper;
use App\Services\HelperService;
use Illuminate\Support\Facades\{Log, DB};
use HeadlessChromium\Page;
use App\Models\{Ad, AdView, AdReview, SearchQuery, ParserTask} ;
use HeadlessChromium\Dom\Selector\CssSelector;

class ParserService 
{

    private ParserTask $task;

    private int $portNumber = 0;

    private int $instanceNumber = 0;

    private ?string $proxy = null;

    private $browser = null;

    private $page = null;

    private $url = '';

    public function __construct(int $instanceNumber = 1, ?string $proxy = null)
    {
        $this->instanceNumber = $instanceNumber;
        
        $this->portNumber = intval(config('headless-chrome.default_debug_port')) + $this->instanceNumber - 1;  
       
        if (!empty($proxy)) {
            $this->proxy = $proxy;
        }
    }


    private function log(mixed $message, string $level = 'debug') {

        if (is_array($message) || is_object($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT, 3);
        }

        Log::channel('browser')->$level($message);
    }

    public function getPortNumber() : int 
    {
        return $this->portNumber;
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
            'user_data_dir'         => 'avito'.(string)($this->instanceNumber),
            //открываемый URL
            // 'url'                   => $url,
            //порт для связи с браузером
            'debug_port'            => $this->portNumber,// + $this->inst - 1,
            //очистка кэша скрипта    
            'clear_script_cache'    => true,
            //каждый раз стартовать принудительно новый инстанс
            'fresh_start'           => false,
            //событие загрузки страницы, после которого разрешено выполнять скрипты
            'onload_event'          => 'page',
            //Отключить уведомления
            'disable_notifications' => true,
            //скрипт предзагрузки
            'preload_script'         => null,//'avito/capture_ratings',
            //ID потока
            'thread_id'             => 'thread-id-'.$this->instanceNumber,
            //прокси
            //'proxy'                 => '176.9.113.112:48000:889946558:41V9JHxLBsNiTvD8Lcrw',
        ];

        if (!empty($this->proxy)) {
            $params['proxy'] = $this->proxy;
        }

        return HeadlessBrowserWrapper::factory($params);
        //$this->browser->startRequestIntercept();
    }
    
    private function check404() : bool
    {
        $is404 = $this->browser->runScriptOnPage($this->page, 'avito/check_404');
        return intval($is404) > 0;
    }

    private function getPrice() : ?float
    {
        $selector = '//span[@data-marker="item-view/item-price"]';
        $price = $this->browser->runScriptOnPage($this->page, 'get_element_content', ['elementSelector' => $selector, 'Xpath' => true]);
        return $price ? floatval(preg_replace('~[^\d]~ ','', $price)) : null;
    }

    private function getViews() : array
    {
        $selectorTotalViews = '//span[@data-marker="item-view/total-views"]';
        $selectorTodayViews = '//span[@data-marker="item-view/today-views"]';

        $todayViews = $this->browser->runScriptOnPage($this->page, 'get_element_content', [
            'elementSelector' => $selectorTodayViews, 'Xpath' => true
        ]);

        $totalViews = $this->browser->runScriptOnPage($this->page, 'get_element_content', [
            'elementSelector' => $selectorTotalViews, 'Xpath' => true
        ]);

        return [ intval(preg_replace('~\D~', '',  $todayViews)) ?? null,  intval($totalViews) ?? null];
    }

    private function getRatings(string $title) : array
    {
        $result = ['average_rating' => 0, 'reviews' => []];

        $avgRatingsSelector = 'div.seller-info-rating>span:nth-child(1)';
        $avgRating = $this->browser->runScriptOnPage($this->page, 'get_element_content', [
            'elementSelector' => $avgRatingsSelector, 'Xpath' => false]);
        $result['average_rating'] = floatval(str_replace(',', '.', $avgRating));

        sleep(2);
        $ratingsSelector = 'a[data-marker="rating-caption/rating"]';
        //$this->browser->getTab(0)->mouse()->find($ratingsSelector)->click();        
        $newURL = $this->url.'#open-reviews-list';
        try {
            $this->page->navigate($newURL)->waitForNavigation(Page::DOM_CONTENT_LOADED, 5);
        }
        catch (\Exception $ex)
        {
            $this->log('No signal about readiness to navigation', 'warning');
        }

        $reviewsModalSelector = 'div[role="dialog"]'; 

        $this->page->waitUntilContainsElement(new CssSelector($reviewsModalSelector), 5 * 10**3);        
        
        $totalReviews = intval($this->browser->runScriptOnPage($this->page, 'get_element_content', [
            'elementSelector' => 'a[data-marker="rating-caption/rating"]',
             'Xpath' => false
        ]),5 );

        dump('Total reviews:'.$totalReviews);

        list($modalX, $modalY) = $this->browser->runScriptOnPage($this->page, 'get_element_coords', [
            'elementSelector' => $reviewsModalSelector,
            'Xpath' => false
        ], 5);

        if ($totalReviews > 25) {
            $this->page->mouse()->move(500, 300 )->scrollDown(100);
                //->move($modalX+random_int(35, 60), $modalY+random_int(35, 60), ['steps' => random_int(20,30)])->click();

            $count = ceil($totalReviews/25);

            if ($count == 0) {
                $count = 1;
            }

            for ($i = 0; $i < $count; $i++) {
                //dump('Sweep '.$sweep);
                $this->page->mouse()->scrollDown(200);
                sleep(random_int(1,2));
            }
        }

        $reviews = $this->browser->runScriptOnPage($this->page, 'avito/get_reviews');
        if ($reviews) {
            $result['reviews'] = $reviews;
        }        

        $result['average_rating'] = floatval(str_replace(',', '.', $avgRating));


        return $result;
    }

    private function getAdInfo(string $adUrl, string $title)  : ?array
    {
        try {            
            if ($this->check404()) {
                $this->page->close();
                dump("Closing");
                return null;
            }
            $price = $this->getPrice();
            list ($today, $total) = $this->getViews();
            $ratings = $this->getRatings($title);
            

            $filteredReviews = array_filter($ratings['reviews'], function ($review) use ($title) {
                return trim($review['title']) == trim($title);
            });

            array_walk($filteredReviews, function (&$review) {
                unset($review['title']);
                $review['score'] = intval($review['score']);
            });

            return [
                'price' => $price,
                'today_views' => $today,
                'total_views' => $total,
                'reviews' => $filteredReviews,
                'average_rating' => $ratings['average_rating']
            ];
        } 
        catch (\Exception $ex) { 
            $this->log('Error getAdInfo: '.$ex->getMessage().' '.$ex->getTraceAsString(), 'warning');
            return null;
        }
    }

    public function processAdPage(Ad $ad)
    {  
        $this->url = 'https://www.avito.ru'.$ad->clean_url;
        $this->page = $this->browser->openTab($this->url);
        $adInfo  = $this->getAdInfo($ad->clean_url, $ad->title);

        if (is_null($adInfo)) {
            $this->log('Ad info is empty: '.$ad->id, 'warning');
            $ad->status = 'error';
            $ad->save(); 
            return;
        }

        //Log::channel('browser')->debug($adInfo);
        $ad->update([
            'status' => 'visited',
            'title' => $ad->title,
            'price' => $adInfo['price'],
            'rating' => $adInfo['average_rating'],
            'last_visited_at' => now(),
            'created_at' => now(),
        ]);

        dump($adInfo);

        AdView::upsert(
            [ 
                'ad_id' => $ad->id,
                'plus_views'  => $adInfo['today_views'], 
                'total_views' => $adInfo['total_views'],
            ],
            ['id'],
            [
                'plus_views'  => $adInfo['today_views'],
                'total_views' => $adInfo['total_views'],
            ]
        );

        foreach ($adInfo['reviews'] as $review) {
            $reviewObj = AdReview::create([
                'ad_id' => $ad->id,
                'rating' => $review['score'],
                'created_at' => now()
            ]);

            $reviewObj->save();
        }
            
        $rands = array_fill(0, random_int(2, 5), [ 'x' => random_int(100, 500), 'y' => random_int(100, 500)]);

        foreach ($rands as $rand) {
            $randSteps = random_int(2, 5);
            $this->page->mouse()->move($rand['x'], $rand['y'], ['steps' => $randSteps]);
            sleep(random_int(2, 3));
        }

        dump("Closing page");
        $this->page->close();
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

    public function processAds(SearchQuery $sQuery) {
        $ads = Ad::query()->select(['ads.id', 'ads.url', 'ads.clean_url', 'ads.title'])
            ->join('search_queries', 'search_queries.id', '=', 'ads.search_query_id')
            ->where('search_query_id', $sQuery->id)
            ->whereIn('status', ['new', 'visited'])
            ->orderByRaw('IF(ads.status="visited", 0, 1) ASC, ads.last_visited_at DESC, ads.created_at DESC')
            ->limit(10)->get();
        foreach ($ads as $ad) {
            Log::channel('browser')->debug('Advertisement id: '.$ad->id);
            $this->processAdPage($ad);       
        }
    }


    public function run(ParserTask $task)
    {
        if ($task->process_pid > 0 && HelperService::checkProcess($task->process_pid)) {
            $this->log("Task is already running, query: " . $task->searchQuery->query_text . " PID: " . process_pid);
            return;
        }

        $query = $task->searchQuery;

        $this->browser = $this->initBrowser();
        sleep(5);

        try {
            if ($this->browser->getTab(0)->mouse()->find('#geetest_captcha')) {                
                $this->processCaptcha();
                return;
            }
        } catch (\Throwable $th) {
            $this->log('No captcha', 'debug');
        }

        $this->log('STEP 1 >>>>> Collecting pages');
        $task->stage = 'pages';
        $task->save();
        $this->search($query->query_text);
        $totalPages = $this->getTotalPages();

        //Для теста
        if ($totalPages  > 3) {
            $totalPages = 3;
        }

        for ($i=1 ; $i < $totalPages; $i++ ) {
            $items = $this->collectAds($i);
            $this->log('Parsed '.count($items).' ads');
            $query->update(['observed_at' => now(), 'total_pages' => $totalPages,]);

            $preparedItems = Ad::prepareInsertData($items, $query->id);
            
            Ad::insert($preparedItems);
            $this->log('Page '.($i+1).' processed');
            sleep(random_int(2, 4));
        }

        $this->log('STEP 2 >>>> Processing ads');
        $this->processAds($query->id);
        $this->log('STEP 2 >>>> Done');
        
    }
}