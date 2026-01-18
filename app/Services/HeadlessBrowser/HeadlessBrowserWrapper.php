<?php
namespace App\Services\HeadlessBrowser;

use App\Services\HeadlessBrowser\BrowserFailedException;
use Illuminate\Support\Facades\Log;
use \HeadlessChromium\Page;
use \HeadlessChromium\BrowserFactory;
//use \Components\GHC\BrowserFactoryExt;
//use \Components\GHC\PageExt;
use \HeadlessChromium\Communication\Message;
use App\Services\HeadlessBrowser\ScriptLoader;
/**
 * Класс для одного инстанса GHC
 *  Создание инстанса, управление вкладка
 *
 * @author alexrsk
 * @copyright  M1 Shop <m1-shop.ru>
 */
class HeadlessBrowserWrapper
{
    /**
     * Загрузчик скриптов
     * 
     * @var \Components\GHC\ScriptLoader
     */
    protected $scriptLoader = null;
    
    /** 
     * Номер браузера
     *
     * @var int
     */
    public $number     = 0;

    /**
     * Объект браузера
     * 
     * @var \Components\GHC\BrowserExt
     */
    public $browser    = null;

    /**
     * Статус браузера
     * 
     * @var string
     */    
    public $status     = null;

    /**
     * Номер текущей вкладки 
     * 
     * @var int 
     */
    public $currentTab  = null;

    /**
     * Вкладки 
     * 
     * @var \HeadlessChromium\Page[]
     */
    public $tabs = [];

    /**
     * Запущен инстанс браузера (true), или подключился к существующему (false)
     * @var boolean
     */
    protected $created;

    /**
     * Командная строка
     * @var string
     */
    protected $commandLine;

    /**
     *
     * @var string хост прокси
     */
    protected $proxyHost = null;

    /**
     *
     * @var string логин к прокси
     */
    protected $proxyLogin = null;

    /**
     *
     * @var string пароль к прокси
     */
    protected $proxyPassword = null;

    /**
     *
     * @var string юзерагент
     */
    protected $userAgent = null;

    /**
     *
     * @var string  файл с вебсокет-адресом браузера
     */
    protected $socketFile;

    /**
     *
     * @var string  директория профиля браузера
     */
    protected $userDirectory;


    /**
     * Процесс браузера
     * @var \HeadlessChromium\Browser\BrowserProcess
     */
    protected $process = null;
    
    const ONLOAD_EVENTS = [
        'dom' => Page::DOM_CONTENT_LOADED,
        'page' => Page::LOAD];
    /**
     * Фабрика Browser
     * 
     * @param array $params - параметры запуска браузера
     * @return \self
     * @throws \Exception
     */
    public static function factory($params)
    {            
        $config = config('headless-chrome');
        $cmd = $config['browser_bin'].' --version';        
        $res = shell_exec($cmd);
        $output = preg_match('~(Chrome|Chromium|Canary)~i', $res, $matches);
        if (empty($output))
        {
            Log::channel('browser')->debug('Браузер не установлен');
            throw new \Exception('Браузер не установлен');
        }        
        $browser = new self($params);
        return $browser;
    }

    /**
     * Найти зависший процесс с предыдущего запуска и прибить его
     * 
     * @param int $instanceNumber  номер инстанса
     * @param int $profileDir    Каталог профиля аккаунта
     * 
     * @return boolean
     */
    public static function checkHangingProcess($instanceNumber, $profileDir = '')
    {
        $config = config('headless-chrome');
        $cmd = $config['browser_bin'];

        $browserBinPath = explode(DIRECTORY_SEPARATOR, $config['browser_bin']);
        $bin = end($browserBinPath);

        $browser = preg_match('#chromium|chrome|canary#si', $bin, $matches) ?
            strtolower($matches[0]) : false;
        if (!$browser)
        {
            Log::channel('browser')->debug('Неправильное имя браузера: '.$bin);
            return false;
        }
        $browserGrep = '['.substr($browser,0,1).']'.substr($browser,1);
        $checkCommand = 'ps -eaf | grep "'.$browserGrep.'.*thread-id-'.$instanceNumber.'" | awk \'$1 ~ /./ {print $2}\'';
        Log::channel('browser')->debug('Check command ' . $checkCommand);
        $pids = [];

        exec($checkCommand, $pids);

        if (!empty($pids))
        {
            foreach ($pids as $pid)
            {
                Log::channel('browser')->debug('Пытаемся убить процесс браузера '.$instanceNumber.' PID:'.$pid);
                if (!posix_kill($pid, 9)) // 9 - сигнал SIGKILL.
                {
                    Log::channel('browser')->debug('Не смогли остановить процесс SIGKILLом'.$pid);
                    return false;
                }
            }
        }
        if (!empty($profileDir)) {
            dump($profileDir);
            $socketFile = $profileDir.DIRECTORY_SEPARATOR.'socket';
            if (file_exists($socketFile))
            {
                unlink($socketFile);
            }
        }
        return true;
    }

    /**
     * Убить все экземпляры GHC
     *
     * @return boolean
     */
    public static function stopAll()
    {
        $config = config('headless-chrome');
        $browserBinPath = explode(DIRECTORY_SEPARATOR, $config['browser_bin']);
        $bin = end($browserBinPath);
        $browser = preg_match('#chromium|chrome|canary#si', $bin, $matches) ?
            strtolower($matches[0]) : false;
        if (!$browser) {
            Log::channel('browser')->warning('Браузер '.$bin.' не обнаружен ');
            return false;
        }
        exec('pkill -9 '.$browser);
        return true;
    }

 /**
  * Конструктор  * 
  * @param array $params   параметры запуска браузера
  * @throws \Exception
  */
    protected function __construct($params = [])
    {        
        $config = config('headless-chrome');
        
        $this->prepareEnvironment($params);
        $options = $this->prepareFlags($params);
        $socketExists = file_exists($this->socketFile);
        dump($this->socketFile);
        $browser = false;
        if ($socketExists) {
            Log::channel('browser')->debug('Файл сокета существует, ID потока: '.$params['thread_id']);
            $socket = file_get_contents($this->socketFile);
            try {
                $browser = BrowserFactory::connectToBrowser($socket, []);
                $this->created = false;
            } catch (\HeadlessChromium\Exception\BrowserConnectionFailed $e) {
                Log::channel('browser')->warning('Ошибка подключения. ID потока: '.$params['thread_id'].' Сообщение:' .$e->getMessage());
                self::checkHangingProcess($params['thread_id'], $params['user_data_dir']);
            }
        }
        else {
            dump('Файл сокета не существует, создаем браузер');
            try {
                if (!$browser) {                
                    $factory           = new BrowserFactory($config['browser_bin']);
                    $browser           = $factory->createBrowser($options);

                    //$this->process     = $factory->getBrowserProcess();
                    file_put_contents($this->socketFile, $browser->getSocketUri());
                    //$this->commandLine = $browser->getCommandLine();
                    $this->created = true;
                }
            } catch (\Exception $ex) {
                $errorMessage = 'Ошибка запуска браузера: ' . $ex->getMessage();
                // dump($ex->getTraceAsString());
                if (!empty($params['thread_id']))
                {
                    self::checkHangingProcess($params['thread_id'], $params['user_data_dir']);
                }
                throw new BrowserFailedException($errorMessage, $ex->getCode(), 
                    $this->process, $ex);
            }
        }

        $this->browser = $browser;
        $this->afterCreation($params);
    }

    /**
     * Подготовить окружение для запуска браузера
     *
     * @param $params  параметры запуска браузера
     *
     */
    protected function prepareEnvironment($params)
    {
        $config = config('headless-chrome');
        if (empty($params['user_data_dir'])) {
            $params['user_data_dir'] = 'default';
        }
        $this->userDirectory = trim($config['profile_dir'].DIRECTORY_SEPARATOR.'userData'
            .DIRECTORY_SEPARATOR.$params['user_data_dir']);

        $this->socketFile    = $this->userDirectory.DIRECTORY_SEPARATOR. 'socket';

        if (!file_exists($this->userDirectory)) {
            if (!mkdir($this->userDirectory, 0777, true))
            {
                throw new \Exception('Не удалось создать каталог для профиля браузера '.$this->userDirectory);
            }
        }

        if (isset($params['fresh_start']) && $params['fresh_start']
            && isset($params['thread_id'])) {
            self::checkHangingProcess($params['thread_id'], $params['user_data_dir']);
            @unlink($this->socketFile);
        }

        if (!empty($params['scripts'])) {
            $this->scriptLoader = new ScriptLoader($params['scripts'],
                $params['clear_script_cache']);
        }
        $this->userAgent = $params['user_agent'];
    }

    /**
     * Подготовить ключи командной строки браузера
     *
     * @param $params
     *
     * @return array
     */
    protected function prepareFlags($params)
    {
        $config = config('headless-chrome');
        $debugPort       = $params['debug_port'] ?? config('headless-chrome.default_debug_port');
        //Описание опций запуска: https://peter.sh/experiments/chromium-command-line-switches/
        $customFlags = [
            '--remote-debugging-port=' . $debugPort,
            '--user-agent=' . $params['user_agent'],
            // '--disable-gl-drawing-for-tests',  //TODO раскомментировать, когда не нужны будут тестовые скриншоты */
            '--crash-on-hang-threads=UI:120,IO:120',
            '--homedir='.$this->userDirectory,
            '--disable-features=TranslateUI, InfiniteSessionRestore',
            '--disable-restore-session-state',
            '--noerrdialogs',
            //'--blink-settings=imagesEnabled=false', //отключение картинок
            '--window-size='.$config['viewport'],
        ];
        if (isset($params['disable_notifications'])) {
            $customFlags[]= '--disable-notifications';
        }
        if (isset($params['no_sandbox'])) {
            $customFlags[]='--no-sandbox';
        }
        if (isset($params['thread_id'])) {
            $customFlags[]='--thread-id-'.$params['thread_id'];
        }
        if (isset($params['proxy'])) {
            $proxyParts = explode(':', $params['proxy']);
            if (isset($proxyParts[2]) && isset($proxyParts[3]))
            {
                $this->proxyLogin = $proxyParts[2];
                $this->proxyPassword = $proxyParts[3];
            }
            $this->proxyHost = $proxyParts[0].':'.$proxyParts[1];
            $customFlags[] = '--proxy-server= ' . $this->proxyHost;
        }
        $options = [
            'headless'    => $params['headless'] ?? true,
            'userDataDir' => $this->userDirectory,
            'keepAlive'   => true,
            'customFlags' => $customFlags
        ];

        if (isset($params['debug_output'])) {
            // php://stdout для вывода в консоль
            $options['debugLogger'] = $params['debug_output'];
        }
        return $options;
    }

    /**
     * Процедуры после создания экземпляра браузера: переход на вкладку, авторизация прокси,
     * перехват HTTP запросов, подстановка куков.
     *
     * @throws \Components\GHC\BrowserFailedException
     */
    protected function afterCreation($params) {
        if (!empty($params['preload_script']))
        {
            $this->preloadScript($params['preload_script']);
        }

        $config = config('headless-chrome');

        $supressFilter = $params['supressFilter'] ?? null;
        try {
            $onLoadEvent = isset($params['onload_event'])
            && !empty($params['onload_event']) ?
                self::ONLOAD_EVENTS[$params['onload_event']] : self::ONLOAD_EVENTS['page'];
            $this->startAuthIntercept();
            if ($this->created) {
                $cookies = [];
                if (isset($params['cookies']))
                {
                    $cookies = json_decode($params['cookies'], true);
                    if (json_last_error() !== \JSON_ERROR_NONE) {
                        Log::channel('browser')->warning('Ошибка в cookies : '.print_r($params['cookies'], true));
                    }
                }
                $clearCookies = isset($params['clear_cookies']) && $params['clear_cookies'];
                $clearCache = isset($params['clear_cache']) && $params['clear_cache'];

                $this->currentTab = $this->openTab($params['url'], $onLoadEvent,
                    $config['first_load_timeout_ms'], $cookies, $clearCookies, $clearCache);
            }
            else {
                $cookies = [];
                //$this->currentTab = $this->attachTab(0, $params['url'], $onLoadEvent,  $config['load_timeout_ms']);
                $this->currentTab = $this->openTab($params['url'], $onLoadEvent, $config['first_load_timeout_ms'], $cookies );
            }

            $this->stopRequestIntercept();
            if (!empty($supressFilter))
            {
                $this->startRequestIntercept($supressFilter);
            }
        }
        catch (\Exception $ex)
        {
            Log::channel('browser')->error('Ошибка запуска браузера: '.$ex->getMessage().' '.$ex->getTraceAsString());
            //throw new BrowserFailedException($ex->getMessage(), $ex->getCode()                $ex);
        }
    }

    /**
     * Открывает вкладку и переходит на URL
     *
     * @param string $url URL куда переходим
     * @param string $waitFor событие окончания загрузки
     * @param int $timeout таймаут ожидания загрузки
     * @param array $cookies  куки
     * @param bool $clearCookies очистить куки на вкладке
     * @param bool $clearCache очистить кэш на вкладке
     * 
     * @return int ID вкладки
     */
    public function openTab($url, $waitFor = Page::DOM_CONTENT_LOADED,
        $timeout = 15000, $cookies = [], $clearCookies = false,
        $clearCache = false)
    {

        $config = config('headless-chrome');
        //dump($config);
        $this->status = 'busy';
        $page         = $this->browser->createPage();
        $viewPort = explode(',', $config['viewport']);
        $page->setViewport($viewPort[0], $viewPort[1]);
        $pageNumber   = count($this->tabs);
        $this->tabs[] = $page;

        if ($clearCache) {
            $this->clearCache($page);
        }

        if ($clearCookies) {
            $this->clearCookies($page);
        }

        if (!empty($cookies))
        {
            $this->setCookies($cookies);
        }

        if (!empty($url))
        {
            $this->navigateTab($pageNumber, $url, $timeout * 1000, $waitFor);
        }
        return $pageNumber;
    }

     /**
     * Подсоединяется к вкладке и переходит на URL
     * 
     * @param int $tabNumber номер вкладки, к которой подключаемся
     * @param string $url URL куда переходим
     * @param string $waitFor событие окончания загрузки
     * @param int $timeout таймаут ожидания загрузки
     * 
     * @return int|bool
     */

    /*
    public function attachTab($tabNumber, $url = null, $waitFor = Page::DOM_CONTENT_LOADED, 
        $timeout = GHC_PAGE_LOAD_TIMEOUT)
    {

        $result = $this->tabs[$tabNumber] = $this->browser->attachToPage($tabNumber);

        if (!$result)
        {
            Loader::log('Не удалось подключиться к вкладке '.$tabNumber, 'ghc_browser_errors',
                'GHC_DEBUG', GHC_DEBUG_LEVEL);
            return false;
        }
        $this->currentTab = $tabNumber;
        if (!empty($url))
        {
            $this->navigateTab($tabNumber, $url, $timeout * 1000, $waitFor);
        }
        return $this->currentTab;
    }
    */

    /**
     * Закрывает вкладку по номеру
     * 
     * @param int $number
     * @return boolean
     */
    public function closeTab($number)
    {
        if (!isset($this->tabs[$number])) {
            return false;
        }

        $this->tabs[$number]->close();
        unset($this->tabs[$number]);

        if (count($this->tabs) == 0) {
            $this->status = 'free';
        }
    }

    
    /**
     * Переключиться на вкладку
     * 
     * param int $number
     * return boolean
     */
    /*
    public function switchTab($number)
    {
        if (!isset($this->tabs[$number])) {
            return false;
        }
        $target = $this->tabs[$number]->getTarget();
        $targetId = $target->getTargetInfo('targetId');
        $connection = $this->browser->getConnection();
        $connection->sendMessageSync(new Message('Target.activateTarget', ['targetId' => $targetId]));
    }
    */


    /**
     *  Получить вкладку
     * 
     * @param int $number
     * 
    */
    public function getTab($number) : ?Page 
    {
        if (!isset($this->tabs[$number])) {
            return null;
        }
        return $this->tabs[$number];
    }

    /**
     * Переход на страницу
     * 
     * @param int $number номер вкладки
     * @param string $url URL
     * @param int $timeout таймаут, сек.
     * @return boolean
     */
    public function navigateTab($number, $url, $timeout = null, $waitFor = Page::DOM_CONTENT_LOADED)
    {
        if (!isset($this->tabs[$number])) {
            return false;
        }
        
        $this->tabs[$number]->navigate($url)->waitForNavigation($waitFor, $timeout);
    }

    /**
     * Выполнить скрипт по имени
     * и вернуть результат выполнения, или выполнить скрипт и дождаться загрузки
     * страницы
     *
     * @param string $script  название скрипта, должно совпадать с именем файла
     * без js
     * @param string $params  параметры скрипта
     * @param int $number  номер табы
     * @param int $timeout таймаут, сек.
     * @param bool $waitForReload  ждать до перезагрузки страницы
     * 
     * @return mixed
     */
    public function runScript($number = 0, $script, $params = [], 
        $timeout = 5, $waitForReload = false)
    {
        Log::channel('browser')->debug('Запуск скрипта '.$script);
        $number = $number ?? $this->currentTab;
        $scriptSource = $this->scriptLoader->load($script, $params);

        if (empty($scriptSource))
        {
            throw new \Exception('Скрипт '.$script.' не найден');
        }
        if (!isset($this->tabs[$number])) {
            Log::channel('browser')->info('Вкладка '.$number.' закрыта или не существует');
            return false;
        }

        $result = $this->tabs[$number]
            ->evaluate($scriptSource)
            ->getReturnValue($timeout ? $timeout * 1000 : null);
        if ($waitForReload)
        {
            $this->tabs[$number]->waitForReload();
        }
        return $result;
    }

    /**
     * Предзагрузка скрипта для всего браузера или вкладки
     * 
     * @param string $name название скрипта
     * @param int $number - номер вкладки. если не указана, для всего браузера.
     */
    public function preloadScript($name, $number = false)
    {       
       $script = $this->scriptLoader->load($name);
       if (!$number)
       {
            $this->browser->setPagePreScript($script);
       }
       else 
       {
            $this->tabs[$number]->addPreScript($script, ['onLoad' => true]);
       }
    }

    /**
     * 
     * Печать текста в поле ввода
     * 
     * @param int $number - номер вкладки
     * @param int $x - координата X поля ввода
     * @param int $y - координата Y поля ввода
     * @param string $text - текст
     */
    public function inputText($number = null, $x = 0, $y = 0, $text= ' ')
    {
        $number = $number ?? $this->currentTab;
        $this->tabs[$number]->mouse()->move($x,$y);
        usleep(50);
        try {
            $this->tabs[$number]->mouse()->click();
        }
        catch (\Exception $e)
        {
            Log::channel('browser')->warning('Произошло исключение при клике '.$e->getMessage());
        }
        /*
        $chars = preg_split('//u', $text, null, PREG_SPLIT_NO_EMPTY);
        foreach ($chars as $char)
        {            
            $this->tabs[$number]->keyboard()->press('keypress', $char, ord($char));
        }
        $this->tabs[$number]->keyboard()->enter();
        */
        $this->tabs[$number]->keyboard()->type($text);
    }
    
     /**
     * Сделать скриншот
     * 
     * @param int $number номер вкладки
     * @param string $fileName имя файла для скриншота
     */
    public function screenshot($number = null, $fileName = null)
    {
        $number = $number ?? $this->currentTab;
        $fileName = $fileName ?? 'screen.png';
        $imagePath = storage_path('browser_screenshots');
        if (!file_exists($imagePath))
        {
            mkdir($imagePath, 0777);
        }
        $result =  $this->tabs[$number]->screenshot()
            ->saveToFile($imagePath.DIRECTORY_SEPARATOR.$fileName);
    }

    /**
     * Скролл страницы
     * 
     * @param int $number   номер вкладки браузера
     * @param int $times   сколько скроллов
     * @param int $timeout  таймаут, секунды
     * @param int $delayFrom  ожидание между скроллами (от, мксек)
     * @param int $delayTo  ожидание между скроллами (до, мксек)
     * 
     */
    public function scrollPage($number = null, $times = 60, $timeout = null,
        $delayFrom = 50, $delayTo = 100)
    {
        $config = config('headless-chrome');
        $number = $number ?? $this->currentTab;

        list($windowWidth, $windowHeight) = explode(',',$config['viewport']);
        list($sweepFrom, $sweepTo) = explode(',', $config['scroll_sweep']);

        $newX = floor($windowWidth / 3) + rand(10, 20);
        $newY = floor($windowHeight / 3) + rand(10, 20);
        $timeoutParam = $timeout ? ['timeout' => $timeout * 1000] : null;
        $this->tabs[$number]
            ->mouse()
            ->move($newX, $newY, $timeoutParam);

        for ($i = 0; $i < $times; $i++) {
            $this->tabs[$number]
                ->mouse()->scrollDown(rand($sweepFrom, $sweepTo));
            usleep(rand($delayFrom, $delayTo));
        }
    }

    /**
    * Перехватывает событие авторизации прокси
    *
    * */
    public function startAuthIntercept()
    {
        $pattern = ['urlPattern' => ""];
        $messageEnable = new Message('Fetch.enable', ['pattern' => $pattern,
            'handleAuthRequests' => true]);

        $connection    = $this->browser->getConnection();
        $connection->sendMessageSync($messageEnable);

        $connection->on('method:Fetch.requestPaused', function($request)
            use (&$connection) {
            $messageContinue = new Message('Fetch.continueRequest', 
                ['requestId' => $request['requestId']]);
            $connection->sendMessage($messageContinue);
        });

        $connection->on('method:Fetch.authRequired', function($response)
            use (&$connection) {
            $messageContinue = new Message('Fetch.continueWithAuth', 
                ['requestId' => $response['requestId'], 
                     'authChallengeResponse'=>
                        [
                            'response' => 'ProvideCredentials',
                            'username' => $this->proxyLogin, 
                            'password' => $this->proxyPassword
                        ]
                ]);
            $connection->sendMessage($messageContinue);
        });
    }

     /**

     * Перехваты событий сети
     * Отвечает за фильтрацию запросов
     * 
     * @param $supressRequestsFilter  фильтр URL запросов, которые будут отклонены для загрузки
     */
    public function startRequestIntercept($requestsFilter, ?callable $callback = null)
    {
        $connection    = $this->browser->getConnection();
        $connection->removeAllListeners('method:Fetch.requestPaused');
        $messageEnable = new Message('Fetch.enable', ['patterns' => $requestsFilter ]);
        $connection->sendMessageSync($messageEnable);

        $connection->on('method:Fetch.requestPaused', function($request)
            use (&$connection, $callback) {
                if ($callback) {
                    $callback($request);
                    $messageFail = new Message('Fetch.continueRequest', 
                    ['requestId' => $request['requestId']]);
                    $connection->sendMessage($messageFail);
                }
            });
                
    }

    /**
     *  Остановить перехват запросов
     */
    public function stopRequestIntercept()
    {
        $messageDisable = new Message('Fetch.disable');
        if ($this->browser)
        {
            try {
                $connection    = $this->browser->getConnection();
                $connection->sendMessageSync($messageDisable);
            }
            catch (\Exception $e)
            {
                Log::channel('browser')->warning('Ошибка отправки сообщения остановки перехвата' .$e->getMessage());
            }
        }
    }

    /**
     * Установить текущую вкладку
     * 
     * @param int $number
     */
    public function setCurrentTab($number)
    {
        $this->currentTab = $number;
    }
    
    /**
     * Возвращает, запущен ли инстанс, или объект браузера 
     * подключился к существующему
     * 
     * @return boolean
     */
    public function isCreated()
    {
        return $this->created;
    }
    
    /**
     *  Закрыть браузер, проверить, закрылся ли, удалить файл с адресом вебсокета
     */
    public function close()
    {
        if ($this->browser) {
            $this->browser->close();
        }
        if (file_exists($this->socketFile)) {
            unlink($this->socketFile);
        }
    }
    /**
     *  Закончить сеанс, не закрывая браузера
     *
     *  return \HeadlessChromium\Communication\
     */
    public function detach()
    {
        $result = $this->browser->getConnection()->disconnect();
        return $result;
    }
    
    /**
     *  Получить командную строку браузера
     * @return string
     */
    public function getCommandLine()
    {
        return $this->commandLine;
    }

    /**
     * Возвращает текущие настройки парсера
     * 
     * @return array
     */
    public function getParserSettings()
    {
        $osRegexp = '#(windows|linux|android|mac os x)#usi';
        $os  = (preg_match($osRegexp, $this->userAgent, $matches)) ? $matches[1] 
            : 'unknown';
        $browser = $this->userAgent;
        return [
            'userAgent'        => $this->userAgent,
            'proxyHost'        => $this->proxyHost,
            'proxyCredentials' => $this->proxyLogin . ':' . $this->proxyPassword,
            'os'               => $os,
            'browser'          => $browser
        ];
    }
  
    /**
     * Получить cookies на указанные домены
     * 
     * @param array $domains  домены
     * @param int $tabNumber  номер вкладки
     */
    public function getCookies(array $domains, int $tabNumber = 0)
    {
        $allCookies = [];
        foreach ($domains as $domain)
        {
            $messageDelete = new Message('Network.getCookies',
                ['domain' => $domain]);
            $result = $this->tabs[$tabNumber]->getSession()->sendMessageSync($messageDelete);
            $data = $result->getData();
        }
        return (isset($data['result']) && isset($data['result']['cookies'])) ?
            $data['result']['cookies'] : false;
    }

    /**
     * Установить cookies
     * Формат cookies
     * [
     *      ["name":<ИМЯ>, "value":<ЗНАЧЕНИЕ>, domain: <ДОМЕН>, path: <URL> ],
     *      ...
     * ]
     * @param array $cookies  куки
     * @param int $tabNumber  номер вкладки
     */
    public function setCookies(array $cookies, $tabNumber = 0)
    {
        $session  = $this->tabs[$tabNumber]->getSession();
        foreach ($cookies as &$cookie)
        {
            $cookie['expires'] = strtotime('+1 month');
            $messageSetCookie = new Message('Network.setCookie', [
                'name'     => $cookie['name'],
                'value'    => $cookie['value'],
                'expires'  => intval($cookie['expires']),
                'secure'   => true,
                'httpOnly' => false,
                'path'     => $cookie['path'],
                'sameSite' => 'Lax',
                'domain'   => $cookie['domain']
            ]);
            $result = $session->sendMessageSync($messageSetCookie);
        }
    }

    /**
    * Очистить куки на вкладке
    *
    * @param \Components\GHC\PageExt $page  объект вкладки
    *
    */
    public function clearCookies(\Components\GHC\PageExt $page)
    {
        $session  = $page->getSession();
        $message = new Message('Network.clearBrowserCookies');
        $result = $session->sendMessageSync($message);
    }

    /**
    * Очистить кэш на вкладке
    *
    * @param \Components\GHC\PageExt $page  объект вкладки
    *
    */
    public function clearCache(\Components\GHC\PageExt $page)
    {
        $session  = $page->getSession();
        $message  = new Message('Network.clearBrowserCache');
        $session->sendMessageSync($message);
    }

    /**
     * Кликнуть элемент на странице
     * 
     * @param string $selector  селектор элемента
     * @param bool $xPath  является ли селектор выражением XPath
     * @param int $tabNumber  номер вкладки
     * @param int $xAdd  прибавить к X-координате элемента
     * @param int $yAdd  прибавить к Y-координате элемента 
     * 
     * @return boolean
     * 
     * @throws \Exception
     */
    public function clickElement($selector, $xPath = false, $tabNumber = 0,
        $xAdd = 5, $yAdd = 5)
    {
        if (empty($selector))
        {
            return false;
        }
        $elementExist = $this->runScript('check_element_presence', 
            ['selector'=> $selector, 'xpath' => $xPath]);
        if ($elementExist)
        {
            $elementCoords = $this->runScript('get_element_coords', [
                'elementSelector' => $selector,
                'Xpath' => $xPath
            ]);
            if (!empty($elementCoords))
            {
                $this->tabs[$tabNumber]->mouse()->move($elementCoords[0]+$xAdd, 
                    $elementCoords[1]+$yAdd);
                usleep(5000);
                $this->tabs[$tabNumber]->mouse()->click();
            }
            else {
                throw new \Exception('Не могу определить координаты элемента');
            }
        }
        else {
            return false;
        }
        return true;
    }

    /**
     *  Деструктор
     */
    public function __destruct()
    {
        if ($this->browser)
        {
            $connection = $this->browser->getConnection();
            if ($connection->isConnected())
            {
                $this->stopRequestIntercept();
            }
        } else {
            @unlink($this->socketFile);
        }
         
    }
}

