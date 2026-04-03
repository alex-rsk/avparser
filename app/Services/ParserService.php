<?php

namespace App\Services;

use App\Services\HeadlessBrowser\HeadlessBrowserWrapper;
use App\Services\HelperService;
use Illuminate\Support\Facades\{Log, DB};
use HeadlessChromium\Page;
use Illuminate\Support\Str;
use App\Models\{Ad, AdView, AdReview, SearchQuery, ParserTask} ;
use HeadlessChromium\Dom\Selector\CssSelector;
use HeadlessChromium\Dom\Selector\XPathSelector;

class ParserService
{
    const PAGE_LIMIT = 100;
    const CAPTCHA_WAIT_ATTEMPTS = 5;
    const LOG_PREFIX = 'parser_';
    const ALLOWED_LEVELS = ['debug', 'warning', 'error', 'info'];


    private ParserTask $task;

    private int $portNumber = 0;

    private int $instanceNumber = 0;

    private ?string $proxy = null;

    private $browser = null;

    private $page = null;

    private $url = '';

    private $logFile =  null;

    public function __construct(int $instanceNumber = 1, ?string $proxy = null)
    {
        $this->instanceNumber = $instanceNumber;

        $this->portNumber = intval(config('headless-chrome.default_debug_port')) + $this->instanceNumber - 1;
        $this->logFile = storage_path('logs/' . self::LOG_PREFIX . $this->instanceNumber . '.log');
        if (!empty($proxy)) {
            $this->proxy = $proxy;
        }
    }


    /*
    * Write a message to a log file.
    *
    * @param mixed  $message    Log message; arrays are JSON-encoded with pretty-print
    * @param string $level      Severity level: debug | warning | error | info
    * @param array  $data       Additional context data
    *
    * @throws \InvalidArgumentException If an unsupported log level is provided
    */
    private function log( mixed $message, string $level='debug', array $data = []): void
    {
        $index = $this->instanceNumber;
        if (!in_array($level, self::ALLOWED_LEVELS, strict: true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid log level "%s". Allowed levels are: %s.',
                $level,
                implode(', ', self::ALLOWED_LEVELS)
            ));
        }

        if (is_array($message)) {
            $message = json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $filename = $this->logFile;

        $entry = sprintf(
            "[%s] [%s] %s%s",
            now()->toDateTimeString(),
            strtoupper($level),
            $message,
            !empty($data) ? PHP_EOL . 'Context: ' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ''
        );

        file_put_contents($filename, $entry . PHP_EOL, FILE_APPEND | LOCK_EX);
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
            'headless'              => config('headless-chrome.headless'),
            //строка юзерагента
            'user_agent'            => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.7680.80 Safari/537.36',
            //имя инстанса
            'user_data_dir'         => 'avito'.(string)($this->instanceNumber),
            //открываемый URL
            // 'url'                   => $url,
            //порт для связи с браузером
            'debug_port'            => $this->portNumber,// + $this->inst - 1,
            //'debug_output'          => logger(),
            //очистка кэша скрипта
            'clear_script_cache'    => true,
            //каждый раз стартовать принудительно новый инстанс
            'fresh_start'           => true,
            //событие загрузки страницы, после которого разрешено выполнять скрипты
            'onload_event'          => 'page',
            //Отключить уведомления
            'disable_notifications' => true,
            //скрипт предзагрузки
            'preload_script'         => 'preload',
            //ID потока
            'thread_id'             => 'thread-id-'.$this->instanceNumber,
            //прокси
            //'proxy'                 => '176.9.113.112:48000:889946558:41V9JHxLBsNiTvD8Lcrw',
        ];

        if (!empty($this->proxy)) {
            $params['proxy'] = $this->proxy;
        }

        return HeadlessBrowserWrapper::factory($params);
        sleep(5);
        //$this->browser->startRequestIntercept();
    }


    private function search(string $query) {
        $this->log('Checking captcha in search');
        $this->page = $this->browser->getTab(0);

        if (!$this->handleCaptcha($this->page)) {
            $this->log('Cannot solve captcha');
            $this->browser->close();
            return;
        }

        $searchSelector = 'input[data-marker="search-form/suggest/input"]';
        
        try {
            $this->log('Waiting for search element...');
            $attempts = 10;
            $searchPresent = 0;
            while ($attempts-- > 0 && $searchPresent == 0) {
                $this->log('Attempt '.$attempts);
                $searchPresent = $this->browser->runScriptOnPage($this->page, 'check_element_presence', [
                    'selector' => $searchSelector, 'xpath' => false], 10);
                $this->log($searchPresent);
                sleep(2);
            }

            if ($attempts == 0) {
                $this->browser->close();
            }
        }
        catch (\Exception $ex)
        {
            $this->log('Error awaiting search element: '.$ex->getMessage());
            return;
        }

        $this->page->mouse()->find($searchSelector)->click();

        $this->emulateHumanType($this->page, $query, 3);
        sleep(random_int(1, 3));

        $this->page->keyboard()->typeRawKey("\r");

        if (!$this->handleCaptcha($this->page)) {
            $this->log('Cannot solve captcha');
            $this->browser->close();
            return;
        }
    }

    private function check404() : bool
    {
        $is404 = $this->browser->runScriptOnPage($this->page, 'avito/check_404');
        return intval($is404) > 0;
    }

    private function getPrice() : ?float
    {
        $selector = '//span[@data-marker="item-view/item-price"]';
        try {
            $price = $this->browser->runScriptOnPage($this->page, 'get_element_content', ['elementSelector' => $selector, 'Xpath' => true], 10);
        }
        catch (\Exception $ex)
        {
            $this->log('Error running script getPrice: '.$ex->getMessage());
            $price = 0;
        }
        return $price ? floatval(preg_replace('~[^\d]~ ','', $price)) : null;
    }

    private function getViews() : array
    {
        $selectorTotalViews = '//span[@data-marker="item-view/total-views"]';
        $selectorTodayViews = '//span[@data-marker="item-view/today-views"]';

        try {
            $todayViews = $this->browser->runScriptOnPage($this->page, 'get_element_content', [
                'elementSelector' => $selectorTodayViews, 'Xpath' => true
            ], 10);
        }
        catch (\Exception $ex)
        {
            $this->log('Error running script for todayViews: '.$ex->getMessage());
        }

        try {
            $totalViews = $this->browser->runScriptOnPage($this->page, 'get_element_content', [
                'elementSelector' => $selectorTotalViews, 'Xpath' => true
            ]);
        }
        catch (\Exception $ex) {
            $this->log('Error running script for totalViews: '.$ex->getMessage());
        }

        return [ intval(preg_replace('~\D~', '',  $todayViews ?? 0)) ?? null,  intval($totalViews) ?? null];
    }

    private function getTotalPages() : ?int
    {
        $selector = '//ul[@data-marker="pagination-button"]/li[position()=last()-1]';
        $this->page = $this->browser->getTab(0);
        try {
            $this->page->waitUntilContainsElement(new XPathSelector($selector));
        }
        catch (\Exception $ex)
        {
            $this->log('Error waiting until pages element reveals: '.$ex->getMessage());
            return null;
        }

        try {
            $count = $this->browser->runScript(0, 'get_element_content', ['elementSelector' => $selector, 'Xpath' => true], 10);
        }
        catch (\Exception $ex)
        {
            $this->log('Exception getting total pages script:'.$ex->getMessage());
            return 0;
        }
        return $count ? intval($count)  : null;
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

            if ($this->task->searchQuery->mode === 'text') {
                $newParams = array_merge(['localPriority' => 0,'p' => $page,], ['q' => $params['q']]);
            }
            else {
                $newParams = array_merge($params, ['p' => $page,]);
            }

            $newUrl = $url . '?' . http_build_query($newParams);
            $this->log("New URL:".$newUrl);
            $this->browser->navigateTab(0, $newUrl);
            sleep(5);
        }

        $pageObj = $this->browser->getTab(0);
        $this->handleCaptcha(0);

        $items = $this->browser->runScriptOnPage($pageObj, 'avito/find_ads');

        if ($items) {
            return $items;
        }
        else {
            $this->log('no items', 'warning');
            return [];
        }

    }

    private function getRatings(string $title) : array
    {
        $result = ['average_rating' => 0, 'reviews' => []];

        $avgRatingsSelector = 'div.seller-info-rating>span:nth-child(1)';
        $avgRating = $this->browser->runScriptOnPage($this->page, 'get_element_content', [
            'elementSelector' => $avgRatingsSelector, 'Xpath' => false]);
        $result['average_rating'] = floatval(str_replace(',', '.', $avgRating));
        sleep(2);
        $newURL = $this->url.'#open-reviews-list';

        try {
            $this->page->navigate($newURL)->waitForNavigation(Page::DOM_CONTENT_LOADED, 5);
        }
        catch (\Exception $ex)
        {
            $this->log('Ratings: no signal about readiness to navigation', 'warning');
        }

        $reviewsModalSelector = 'div[role="dialog"]';

        try {
            $this->page->waitUntilContainsElement(new CssSelector($reviewsModalSelector), 10 * 10**3);
        }
        catch (\Exception $ex)
        {
            $this->log('Error waiting until reviews element appears: '.$ex->getMessage());
            return $result;
        }

        try {
            $totalReviews = intval($this->browser->runScriptOnPage($this->page, 'get_element_content', [
                'elementSelector' => 'a[data-marker="rating-caption/rating"]',
                'Xpath' => false
            ]), 10);
        }
        catch (\Exception $ex)
        {
            $this->log('Error executing script : '.$ex->getMessage());
            $totalReviews = 0;
        }

        $this->log('Total reviews:'.$totalReviews);

        try {
            if ($totalReviews > 10) {
                $this->log('scrolling');
                $viewport = explode(',', config('headless-chrome.viewport'));
                $centerX = floor($viewport[0]/2);
                $centerY = floor($viewport[1]/2);
                $this->log('Mouse center:'.$centerX.'-'.$centerY);
                $this->page->mouse()->move($centerX, $centerY,  ['steps' => random_int(3, 6)])->click();

                $currentScroll = 0;
                $scrollLimit = ($totalReviews)*100;

                $step = 500;
                $this->log('Scroll limit:'.$scrollLimit);

                if ($scrollLimit > 10000) {
                    $scrollLimit = 10000;
                }

                while($currentScroll < $scrollLimit) {
                    $currentScroll+=$step;                    
                    $this->browser->runScriptOnPage($this->page, 'avito/scroll_reviews', ['scroll' => $currentScroll]);
                    sleep(random_int(1,2));
                }
            }
        }
        catch (\Exception $ex) {
            $this->log('Error scrolling reviews modal: '.$ex->getMessage());
        }

        try {
            $reviews = $this->browser->runScriptOnPage($this->page, 'avito/get_reviews', [], 10);
        }
        catch (\Exception $ex)
        {
            $this->log('Error executing script: '.$ex->getMessage());
            $reviews = 0;
        }

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
                $this->log("Closing");
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
                'price'         => $price,
                'today_views'   => $today,
                'total_views'   => $total,
                'reviews'       => $filteredReviews,
                'average_rating' => $ratings['average_rating']
            ];
        }
        catch (\Exception $ex) {
            $this->log('Error getAdInfo: '.$ex->getMessage().' '.$ex->getTraceAsString(), 'warning');
            return null;
        }
    }

    public function processAdPage($ad)
    {
        $this->url = 'https://www.avito.ru'.$ad->clean_url;
        $this->page = $this->browser->openTab($this->url);

        $this->log('Process ads page');
        try {
            $attempts = 10;            
            while ($this->page->mouse()->find('#geetest_captcha') && $attempts-- > 0) {
                $this->processCaptcha($this->page);
                sleep(5);
            }

            if ($attempts == 0) {
                $this->log('Can\t solve captcha  ');
                $this->browser->close();
            }
        } catch (\Throwable $th) {
            $this->log('No captcha!');
        }

        
        $adInfo  = $this->getAdInfo($ad->clean_url, $ad->title);

        if (is_null($adInfo)) {
            $this->log('Ad info is empty: '.$ad->id, 'warning');
            $ad->status = 'error';
            $ad->save();
            return;
        }

        $this->log(print_r($adInfo, true));
        
        $ad->update([       
            'status' => 'visited',
            'title'  => $ad->title,
            'price'  => $adInfo['price'],
            'rating' => $adInfo['average_rating'],
            'last_visited_at' => now(),
            'created_at' => now(),
        ]);


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
                'comment' => $review['description'],
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

        $this->log("Closing page");
        $this->page->close();
    }

    private function processCaptcha(Page $page) {
        $this->log("Captcha detected!");
        sleep(5);
        $page->mouse()->find('button')->click();
        sleep(5);
        //Найти элемент div.geetest_bg - это подложка. Картинка в свойстве background-image
        $geeBgCoords = $this->browser->runScriptOnPage($page, 'get_element_coords', [
            'elementSelector' => 'div.geetest_bg',
            'Xpath' => false
        ]);

        $this->log("Geetest background coords");
        $this->log($geeBgCoords);

        //Найти элемент div.geetest_slice_bg - это пазл. Картинка в свойстве background-image getBoundingClientRect получит bbox
        $geeSliceCoords = $this->browser->runScriptOnPage($page, 'get_element_coords', [
            'elementSelector' => 'div.geetest_slice_bg',
            'Xpath' => false
        ]);

        $this->log("Geetest Puzzle coords:");
        $this->log($geeSliceCoords);

        //div.geetest_btn - это  ползунок. getBoundingClientRect()  - получит bbox
        $geeButtonCoords = $this->browser->runScriptOnPage($page, 'get_element_coords', [
            'elementSelector' => 'div.geetest_btn',
            'Xpath' => false
        ]);

        $this->log("Geetest Button coords");
        $this->log($geeButtonCoords);

        $geeArrowCoords =  $this->browser->runScriptOnPage($page, 'get_element_coords', params:[
            'elementSelector' => 'div.geetest_arrow',
            'Xpath' => false
        ]);

        $this->log("Geetest Arrow coords");
        $this->log($geeArrowCoords);

        if (!($geeBgCoords && $geeSliceCoords && $geeButtonCoords)) {
            throw new \Exception("Geetest elements not found!");
        }

        if (empty($geeBgCoords[0]) && empty($geeBgCoords[1])) {
            $this->log('Captcha not detected');
            sleep(2);
            return;
        }

        $puzzleRelativeTop = (int)(ceil(floatval($geeSliceCoords[1])-floatval($geeBgCoords[1])));
        $this->log("Puzzle relative Y: " . $puzzleRelativeTop);

        $cssUrlRegexp = '~url\([\"\']([^\)]+)[\"\']\)~';

        //Get puzzle image
        $imageSmall =$this->browser->runScriptOnPage($page, 'get_element_bgimage', [
            'elementSelector' => 'div.geetest_slice_bg',
            'Xpath' => false
        ]);
        //$this->log('CSS for small:'.$imageSmall);

        $smallUrl = preg_match($cssUrlRegexp, $imageSmall, $matches) ? $matches[1] : null;
        if (!$smallUrl) {
            throw new \Exception("Can't parse puzzle image URL!");
        }
        //$this->log('Small URL: ' . $smallUrl);

        //Get background image
        $imageLarge = $this->browser->runScriptOnPage($page, 'get_element_bgimage', [
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
        $uuid = Str::uuid();
        $smDestFilename = base_path('capsolver/input/small_'.$uuid.'.png');
        HelperService::downloadFile($smallUrl, $smDestFilename);

        $lgDestFilename = base_path('capsolver/input/large_'.$uuid.'.png');
        HelperService::downloadFile($largeUrl, $lgDestFilename);

        $this->log('Captcha background: '. $imageLarge);

        $solverCmd = base_path('dist/capsolver'). ' '.$uuid. ' ' . $smDestFilename . ' ' . $lgDestFilename . ' ' . $puzzleRelativeTop;
        $this->log($solverCmd);

        $output = shell_exec($solverCmd);
        $cleanedOutput = preg_replace('~\s+~', '', $output);
        if (!is_numeric($cleanedOutput)) {
            $this->log('Dirty output: '.$cleanedOutput, 'warning');
        }
 
        @unlink($lgDestFilename);
        @unlink($smDestFilename);

        //X- координата места для пазла, относительно X- координаты подложки
        $targetX = intval($cleanedOutput);
        //$absoluteTargetX =
        $this->log('X-coordinate :' .$targetX);

        $steps = random_int(4, 8);
        $initialOffsetX = random_int(7, 11);
        $initialOffsetY = random_int(9, 21);
        $startX = intval($geeButtonCoords[0])+$initialOffsetX;
        $startY = intval($geeButtonCoords[1])+$initialOffsetY ; 


        $this->log('Start X:'. $startX.' Y:'.$startY);
        sleep(random_int(2,5));
        $this->log("Pointing, steps " . $steps);
        $page->mouse()->move($startX, $startY, ['steps' => $steps])->press();
        //Test
        $distance = $targetX;
        $steps = random_int(2, 5);
        $this->log("Dragging");
        $page->mouse()->move($startX + $distance, $startY, ['steps' => $steps])->release();

    }

    public function processAds(SearchQuery $sQuery) {
        $ads = Ad::query()->select(['ads.id', 'ads.url', 'ads.clean_url', 'ads.title'])
            ->join('search_queries', 'search_queries.id', '=', 'ads.search_query_id')
            ->where('search_query_id', $sQuery->id)
            ->whereIn('status', ['new', 'visited'])
            ->orderByRaw('IF(ads.status="visited", 0, 1) ASC, ads.last_visited_at DESC, ads.created_at DESC')
            ->limit(1000)->get();

        foreach ($ads as $ad) {
            $this->log('Advertisement id: '.$ad->id);
            $this->processAdPage($ad);
        }
    }

    private function handleCaptcha(Page|int $page, int $attempts = 10) : bool
    {
        try {
            $this->log('hc 1');
            $attempts = self::CAPTCHA_WAIT_ATTEMPTS;
            $captchaFound = 0;
            $pageObj = $page instanceof Page ? $page : $this->browser->getTab($page);
            $this->log('hc 2');
            try {
                while ($attempts-- > 0 && $captchaFound == 0) {
                    $this->log('hc while 1');
                    $this->log('Waiting for captcha... Attempt '.((self::CAPTCHA_WAIT_ATTEMPTS+1)-$attempts));
                    sleep(2);
                    $captchaFound = $this->browser->runScriptOnPage($pageObj, 'check_element_presence', [
                        'selector' => '#geetest_captcha', 'xpath' => false], 10);
                    $this->log('hc while 2');
                }
            }
            catch (\Exception $ex)
            {        
                $this->log('Can\'t find captcha, message '.$ex->getMessage());
                return false;
            }

            if (!$captchaFound) {
                $this->log('No captcha');
                return true;
            }

            $captchaAttempts = 10;            
            while ($pageObj->mouse()->find('#geetest_captcha') && $captchaAttempts -- > 0) {
                $this->processCaptcha($pageObj);
                sleep(5);
            }

            $captchaPresent = $this->browser->runScriptOnPage($pageObj, 'check_element_presence', [
                    'selector' => '#geetest_captcha', 'xpath' => false], 10);

            if ($captchaAttempts == 0 && $captchaPresent) {
                $this->log('Can\t solve captcha', 'warning');
                return false;
            } 

            $this->log('Captcha solved', 'debug');
            return true;
        }
        catch (\Throwable $th)
        {
            $this->log('Error handleCaptcha: '.$th->getMessage(), 'warning');
            return true;
        }
    }

    private function handleTextTask(ParserTask $task)
    {
        $query = $task->searchQuery;
        $this->log('STEP 1 >>>>> Collecting pages');
        $this->browser->navigateTab(0, 'https://avito.ru');

        if (!$this->handleCaptcha(0)) {
            $this->log('Cannot pass captcha (');
            $this->browser->close();
        } 

        $this->search($query->query_text);

        sleep(random_int(7, 10));

        $task->stage = 'pages';
        $task->save();
        $totalPages = $this->getTotalPages();
        $this->log('Pages count: ' . $totalPages);
        
        //Для теста
        if ($totalPages > self::PAGE_LIMIT) {
            $totalPages = self::PAGE_LIMIT;
        }

        $task->stage = 'ads';
        $task->save();
    
        for ($i=1; $i < $totalPages; $i++ ) {
            $this->log('Collecting ads on page '.$i);
            $items = $this->collectAds($i);
            $this->log('Parsed '.count($items).' ads');
            try {
                $query->update(['observed_at' => now(), 'total_pages' => $totalPages,]);
                $preparedItems = Ad::prepareInsertData($items, $query->id);            
                Ad::insert($preparedItems);
            }
            catch (\Exception $ex) {
                $this->log('Error inserting ads: '.$ex->getMessage());
            }

            $this->log('Page '.($i+1).' processed');
            sleep(random_int(2, 4));
        }

        $this->log('STEP 2 >>>> Processing ads');
        $this->processAds($query);
        $this->log('>>>> Done');

    }

    private function handleUrlTask(ParserTask $task)
    {
        $query = $task->searchQuery;
        $this->log('STEP 1 >>>> Open category URL');
        $this->browser->navigateTab(0, $task->searchQuery->category_url);
        sleep(2);
        if (!$this->handleCaptcha(0)) {
            $this->log('Cannot solve captcha');
            $this->browser->close();
            return;
        }

        $task->stage = 'pages';
        $task->save();
        $totalPages = $this->getTotalPages();
        $this->log('Pages count: ' . $totalPages);

        //Для теста
        if ($totalPages > self::PAGE_LIMIT) {
            $totalPages = self::PAGE_LIMIT;
        }

        $task->stage = 'ads';
        $task->save();
    
        for ($i=1; $i < $totalPages; $i++) {
            $this->log('Collecting ads on page '.$i);
            $items = $this->collectAds($i);
            $this->log('Parsed '.count($items).' ads');
            try {
                $query->update(['observed_at' => now(), 'total_pages' => $totalPages,]);
                $preparedItems = Ad::prepareInsertData($items, $query->id);            
                Ad::insert($preparedItems);
            }
            catch (\Exception $ex) {
                $this->log('Error inserting ads: '.$ex->getMessage());
            }

            $this->log('Page '.($i+1).' processed');
            sleep(random_int(2, 4));
        }

        $this->log('STEP 2 >>>> Processing ads');
        $this->processAds($query);
        $this->log('>>>> Done');        
    }


    public function run(ParserTask $task)
    {    
        $this->task = $task;
        $this->browser = $this->initBrowser();
        if ($task->searchQuery->mode == 'text') {
            $this->handleTextTask($task);
        } else {
            $this->handleUrlTask($task);
        }

        $task->stage = 'done';
        $task->save();
        $this->browser->close();
    }
}
