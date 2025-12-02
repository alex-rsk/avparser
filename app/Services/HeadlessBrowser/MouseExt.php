<?php
/**
 *  Расширенный класс для работы с мышью
 * 
 *  @author Alex Ryassky
 * 
 * 
 */
namespace Components\GHC;


use HeadlessChromium\Input\Mouse;
use HeadlessChromium\Communication\Message;

class MouseExt extends Mouse
{
    const TIMEOUT = 10000;
    
    public function __construct(PageExt $page)
    {
        $this->page = $page;
    }
      
    /**
     * @param int $x
     * @param int $y
     * @param array|null $options
     * @return $this
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function move(int $x, int $y, ?array $options = null)
    {
        $this->page->assertNotClosed();

        // get origin of the move
        $originX = $this->x;
        $originY = $this->y;

        // set new position after move
        $this->x = $x;
        $this->y = $y;

        // number of steps to achieve the move
        $steps = $options['steps'] ?? 1;
        if ($steps <= 0) {
            throw new \InvalidArgumentException('options "steps" for mouse move must be a positive integer');
        }

        // move
        for ($i = 1; $i <= $steps; $i++) {
            $this->page->getSession()->sendMessageSync(new Message('Input.dispatchMouseEvent', [
                'x' => $originX + ($this->x - $originX) * ($i / $steps),
                'y' => $originY + ($this->y - $originY) * ($i / $steps),
                'type' => 'mouseMoved'
            ]), $options['timeout'] ?? null);
        }

        return $this;
    }
       
    
    public function scrollY(int $distance, array $options = null)
    {
        $this->page->assertNotClosed();

        // get origin of the move
        $originX = $this->x;
        $originY = $this->y;

        // set new position after move
        $this->y = $this->y+$distance;

        // scroll
        $this->page->getSession()->sendMessageSync(new Message('Input.dispatchMouseEvent', [
            'type'              => 'mouseWheel',
            'x'                 => $originX,
            'y'                 => $originY,
            'deltaX'            => 0,
            'deltaY'            => $distance
            ]
        ), $options['timeout'] ?? null);
        return $this;
    }

}
