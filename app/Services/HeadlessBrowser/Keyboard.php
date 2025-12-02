<?php
namespace Components\GHC;

use HeadlessChromium\Communication\Message;
use Components\GHC\PageExt;


/**
 * @author Alex Ryassky
 * @copyright M1
 * 
 * Класс для работы с клавой
 */
class Keyboard
{

    protected $page;

    
    public function __construct(PageExt $page)
    {
        $this->page = $page;
    }

    /**
     * Отправка сообщения клавиатуры в браузер
     * 
     * @param string $type тип сообщения
     * @param string $char отправляемый символ
     * @param int $keyCode код клавиши (используется для Enter, Esc итд)
     * @param type $ctrl - признак нажатия Ctrl
     * @param type $shift - признак нажатия Shift
     * @param type $alt - признак нажатия Alt
     * 
     * @return HeadlessChromium\Input\Keyboard $this 
     * @throws \Exception
     */
    public function press(string $type, string $char = '', 
        int $keyCode = 0, $ctrl = false, $shift = false, $alt= false)
    {
        $text = null;
        $this->page->assertNotClosed();
        switch ($type) {
            case 'keyup':
                $type = 'keyUp';
                break;
            case 'keydown':
                $type = 'rawKeyDown';
                break;
            case 'keypress':
                $type = 'char';
                $text = (empty($char)) ? chr($keyCode) : $char;
                break;
            default:
                throw new \Exception("Unknown type of event.");
                break;
        }
        $modifiers = 0;
        if ($shift) {
            $modifiers += 8;
        }
        if ($alt) {
            $modifiers += 1;
        }
        if ($ctrl) {
            $modifiers += 2;
        }
        $message = new Message('Input.dispatchKeyEvent', [
            'type' => $type,
            'windowsVirtualKeyCode' => $keyCode,
            'modifiers' => $modifiers,
            'text' => $text
        ]);
        $response = $this->page->getSession()->sendMessageSync($message, 5000);
        
        return $this;
    }

    /**
     *  Нажатие Enter
     */
    public function enter()
    {
        $params = [
            "type" => "rawKeyDown",
            "windowsVirtualKeyCode" => 13,
            "unmodifiedText" => "\r",
            "text" => "\r"
        ];
        $message        = new Message('Input.dispatchKeyEvent', $params);
        $response       = $this->page->getSession()->sendMessageSync($message, 5000);
        $params['type'] = 'char';
        $message        = new Message('Input.dispatchKeyEvent', $params);
        $response       = $this->page->getSession()->sendMessageSync($message, 5000);
        $params['type'] = 'keyUp';
        $message        = new Message('Input.dispatchKeyEvent', $params);
        $response       = $this->page->getSession()->sendMessageSync($message, 5000);
    }
}
