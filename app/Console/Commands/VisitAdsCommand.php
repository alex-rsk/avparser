<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HeadlessBrowser\HeadlessBrowserWrapper;
use Illuminate\Support\Facades\Log;
use HeadlessChromium\Page;
use App\Models\Ad;
use App\Models\AdView;
use App\Models\SearchQuery;

class VisitAdsCommand extends Command
{
    protected $browser = null;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'browser:visit-ads {qid}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Visit ad pages by specified search query';

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
            'url'                   => 'https://ya.ru',
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
            'preload_script'         => null,//'avito/capture_ratings',
            //ID потока
            'thread_id'             => 'test-thread',
            //прокси
            //'proxy'                 => $this->account['proxy'],
        ];


        $this->browser = HeadlessBrowserWrapper::factory($params);
        $this->browser->navigateTab(0, 'https://avito.ru/');
        sleep(random_int(1, 3));
    }
    

    private function getPrice() : ?float
    {
        $selector = '//span[@data-marker="item-view/item-price"]';
        $price = $this->browser->runScript(0, 'get_element_content', ['elementSelector' => $selector, 'Xpath' => true]);
        return $price ? floatval(preg_replace('~[^\d]~ ','', $price)) : null;
    }

    private function getViews() : array
    {
        $selectorTotalViews = '//span[@data-marker="item-view/total-views"]';
        $selectorTodayViews = '//span[@data-marker="item-view/today-views"]';

        $todayViews = $this->browser->runScript(0, 'get_element_content', ['elementSelector' => $selectorTodayViews, 'Xpath' => true]);
        $totalViews = $this->browser->runScript(0, 'get_element_content', ['elementSelector' => $selectorTotalViews, 'Xpath' => true]);

        return [ intval($todayViews) ?? null,  intval($totalViews) ?? null];
    }

    private function getRatings(string $title) : array
    {
        $result = ['average_rating' => 0, 'ratings' => []];
        $avgRatingsSelector = 'div.seller-info-rating>span:nth-child(1)';
        $avgRating = $this->browser->runScript(0, 'get_element_content', ['elementSelector' => $avgRatingsSelector, 'Xpath' => false]);
        $result['average_rating'] = floatval(str_replace(',', '.', $avgRating));

        //sleep(2);
        //$ratingsSelector = 'a[data-marker="rating-caption/rating"]';
        //$this->browser->getTab(0)->mouse()->find($ratingsSelector)->click();

        return $result;
    }

    private function getAdInfo(string $adUrl, string $title)  : ?array
    {
        try {
            $url = 'https://avito.ru'.$adUrl;
            $this->browser->navigateTab(0, $url);
            sleep(3);
            $price = $this->getPrice();
            list ($today, $total) = $this->getViews();
            $ratings = $this->getRatings($title);
            dump('Price: ' . $price);
            dump('Today views: ' . $today);
            dump('Total views: ' . $total);
            dump('Rating: ' . $ratings['average_rating']);
            return [
                'price' => $price,
                'today_views' => $today,
                'total_views' => $total,
                'average_rating' => $ratings['average_rating']
            ];
        } 
        catch (\Exception $ex) { 
            Log::channel('daily')->warning('Error getAdInfo: '.$ex->getMessage().' '.$ex->getTraceAsString());
            return null;
        }

    }
   
    /**
     * Execute the console command.
     */
    public function handle()
    {       
       $sqId = $this->argument('qid');
       $ads = Ad::query()->select(['id', 'url', 'clean_url', 'title'])
        ->where('search_query_id', $sqId)->orderByRaw('last_visited_at ASC, RAND()')->limit(1)->get();
       //dump($ads->toArray());
       
       $this->initBrowser();
       foreach ($ads as $ad) {
            dump($ad->id);
            $adObj = Ad::find($ad->id);
            $adInfo =$this->getAdInfo($ad->clean_url, $ad->title);
            if (empty($adInfo)) {
                continue;
            }
            $adObj->update([
                'status' => 'visited',
                'title' => $ad->title,
                'price' => $adInfo['price'],
                'rating' => $adInfo['average_rating'],
                'last_visited_at' => now(),
                'created_at' => now(),
            ]);

            $viewsObj = AdView::create([
                'ad_id' => $adObj->id,
                'plus_views' => $adInfo['today_views'], 
                'total_views' => $adInfo['total_views'],
                 'created_at' => now()
            ]);
            $viewsObj->save();

       }
    }
}
