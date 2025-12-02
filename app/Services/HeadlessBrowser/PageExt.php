<?php
/**
 * @license see LICENSE
 */

namespace Components\GHC;

use \HeadlessChromium\Page;
use \Components\GHC\MouseExt;
use \Components\GHC\Keyboard;

class PageExt extends Page
{
    /**
     *
     * @var Keyboard|Null 
     */
    protected $keyboard;
    
    /**
     *
     * @var MouseExt|Null 
     */
    protected $mouse;
    
    /**
     * Объект мыши
     * 
     * @return Components\GHC\MouseExt
     */
    public function mouse()
    {
        if (!$this->mouse) {
            $this->mouse = new MouseExt($this);
        }

        return $this->mouse;
    }
   
    /**
     * Объект клавиатуры 
     * 
     * @return Components\GHC\Keyboard
     */
    public function keyboard()
    {
        if (!$this->keyboard) {
            $this->keyboard = new Keyboard($this);
        }

        return $this->keyboard;
    }

    public function getTarget()
    {
        return $this->target;
    }
}
