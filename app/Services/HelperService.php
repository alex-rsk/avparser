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

    public static function checkProcess(int $pid)
    {
        $cmd = 'ps h -o pid -p '.$pid;
        $out = shell_exec($cmd);
        return !empty($out);
    }
    
}