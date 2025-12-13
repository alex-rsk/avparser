<?php
namespace App\Services\HeadlessBrowser;


class BrowserFailedException extends \Exception
{
    protected $processHandle;
        
    public function __construct(string $message = "", $code=0, $processHandle = null, ?\Throwable $previous = null)
    {        
        $this->processHandle = $processHandle;
        parent::__construct($message, $code, $previous);
    }
    
    
    public function getProcessHandle()
    {
        return $this->processHandle;
    }
}
