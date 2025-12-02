<?php
namespace Services\HeadlessBrowser;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/*
 * Класс для загрузки javascript 
 * Подставляет параметры
 * 
 * @author Alexrsk
 * @copyright  M1 Shop <m1-shop.ru>
 */
class ScriptLoader
{
    /**
     * путь к скрипту
     * 
     * @var string
     */
    private $path;
    
    /**
     * кэш
     *      
     */    
    private $cache = null;

    /**
     * Конструктор
     * 
     * @param string $path - путь к папке, где лежат javascript файлы     
     * @param bool $clearCache - очистить кэш скриптов
     * @param string $redisServer - псевдоним конфига редиса     
     */
    public function __construct($path, $clearCache = false)
    {
        $config  = config('headless-chrome');
        $this->cache = Cache::getRedis();
        if ($clearCache)
        {
            $keys = $this->cache->keys($config('cache_keys_prefix').':*');
            $self = $this;
            array_walk($keys, function ($key) use ($self) {
                $self->cache->delKey($key);
            });

        }
        $this->path =  resource_path('js/headless-scripts');
    }

    
    /**
     * Загрузка скрипта по имени и подстановка параметров
     * 
     * @param string $name - имя файла скрипта
     * @param array $params  -  Параметры для подстановки в скрипт, [имя_параметра => значение]
     * 
     * @return string
     * 
     * @throws Exception
     */
    public function load($name, $params = [])
    {           
        $config  = config('headless-chrome');
        $path = $this->path.DIRECTORY_SEPARATOR.$name.'.js';
        $cacheKey = $config('cache_keys_prefix').':'.$name;
        if (($script = $this->cache->getString($cacheKey, false)))
        {
            $scriptTemplate = $script;
        }
        else 
        {
            if (!file_exists($path)) {
                throw new \Exception('Скрипт' . $path . ' не найден');
            }        
            $scriptTemplate = file_get_contents($path);
            $this->cache->setString($cacheKey, $scriptTemplate);
        }        
        $templateKeys = array_map(function ($item) {
            return '{{' . $item. '}}';
        }, array_keys($params));        
        return str_replace($templateKeys, $params, $scriptTemplate);
    }
}
