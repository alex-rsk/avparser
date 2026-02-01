<?php

namespace App\Services;

use \Exception;
use TwoCaptcha\TwoCaptcha;
use Illuminate\Support\Facades\Log;

class HelperService 
{

    public static function downloadFile($url, $destination) {
        $ch = curl_init($url);
        $fp = fopen($destination, 'wb');
        
        if (!$fp) {
            throw new Exception("Cannot open destination file: $destination");
        }

        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true, 
            CURLOPT_USERAGENT => 'curl/8.5.0',
            CURLOPT_FAILONERROR => true,
        ]);

        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        fclose($fp);

        if (!$success || $httpCode >= 400) {
            unlink($destination); // Clean up partial file
            throw new Exception("Download failed (HTTP $httpCode)");
        }

        return true;
    }


    public static function  solveWithTwoCaptcha(string $url, string $pageUrl) {
        $urlParams = parse_url($url, PHP_URL_QUERY);
        parse_str($urlParams, $params);
        $apiKey = config('services.rucaptcha.api_key');
        $solver = new TwoCaptcha($apiKey);
        $solverParams  = [
            'url' => $pageUrl,
            'challenge' => $params['challenge'],
            'captchaId' => $params['captcha_id'],
        ];
        //dump($solverParams);
        try {
            //$result = $solver->geetest_v4($solverParams);

            dump($result);
            //sleep(random_int(2, 4));
            
        } catch (\Exception $e) {
            Log::channel('browser')->error('Error solving  captcha: '.$e->getMessage());
        }        

    }
    
}