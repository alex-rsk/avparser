<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HeadlessBrowser\HeadlessBrowserWrapper;
use Illuminates\Support\Facades\Log;
use HeadlessChromium\Page;
use App\Models\Ad;

class CollectPagesCommand extends Command
{
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
            $pieces[] = ['text' => mb_substr($text, $ppos, $gp), 'delay' => random_int(80, 150) ];
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

    private function collectPages(string $query) : bool {
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
            'preloadScript'         => null,
            //ID потока
            'thread_id'             => 'test-thread',
            //прокси
            //'proxy'                 => $this->account['proxy'],
        ];


        $w = HeadlessBrowserWrapper::factory($params);
        $w->navigateTab(0, 'https://avito.ru/');
        sleep(random_int(1, 3));
        $searchSelector = '#bx_search > div.index-suggest-Me4w_ > div > div > label > div > div > div > input';
        $tab = $w->getTab(0);
        sleep(random_int(1, 3));
        $tab->mouse()->find($searchSelector)->click();

        $this->emulateHumanType($tab, $query, 2);
        sleep(random_int(1, 3));
        $tab->keyboard()->typeRawKey("\r");
        sleep(random_int(4, 6));
        $itemsSelector =  '//div[@data-marker="item"]//h2/a[@itemprop="url"]';
        //$items = $tab->dom()->search($itemsSelector);
        $items = $w->runScript(0, 'avito/find_ads', ['elementSelector' => $itemsSelector, 'Xpath' => true]);

        $result = false;

        if ($items) {
            $preparedItems = Ad::prepareInsertData($items, 1);
            $result = Ad::insert($preparedItems);
        }
        else {
            Log::channel('daily')->warning('no items');
        }

        return $result;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
       $searchQueries = SearchQuery::query()->select('query')->orderBy('observed_at', 'DESC')->limit(1)->get();
       foreach ($searchQueries as $query) {
           $result = $this->collectPages($query->query);
           $query->update(['observed_at' => now()]);
       }
    }
}
