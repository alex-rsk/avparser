<?php

namespace App\Services;

use App\Services\HeadlessBrowser\HeadlessBrowserWrapper;
use Illuminate\Support\Facades\{Log, DB};
use HeadlessChromium\Page;
use App\Models\Ad;
use App\Models\AdView;
use App\Models\AdReview;
use App\Models\SearchQuery;
use HeadlessChromium\Dom\Selector\CssSelector;

class ParserService 
{
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

        $this->browser = $this->initBrowser();
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
            'user_data_dir'         => 'avito1',
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
            Log::channel('browser')->warning('No signal about readiness to navigation');
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
            Log::channel('browser')->warning('Error getAdInfo: '.$ex->getMessage().' '.$ex->getTraceAsString());            
            return null;
        }
    }

    public function processAdPage(Ad $ad)
    {  
        $this->url = 'https://www.avito.ru'.$ad->clean_url;
        $this->page = $this->browser->openTab($this->url);
        $adInfo  = $this->getAdInfo($ad->clean_url, $ad->title);

        if (is_null($adInfo)) {
            Log::channel('daily')->warning('Ad info is empty: '.$ad->id);
            $ad->status = 'error';
            $ad->save(); 
            return;
        }

        Log::channel('browser')->debug($adInfo);
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
}