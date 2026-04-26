<?php

namespace App\Livewire;

use Livewire\Component;
use Ramsey\Uuid\Uuid;
use Filament\Support\Icons\Heroicon;

class AccordionComponent extends Component
{
    const MAX_DEPTH = 5;

    public $record;
    public $header = 'Accordion Title';        
    public $slot = '';

    protected $listeners = ['updateValue'];

    public function render()
    {
        return view('livewire.components.accordion-level');
    }
    

    public function hydrate()
    {
        
    }


    public function renderAsHtml(array $value, int $level = 0, array $path = [])
    {       
    }

    public function submit()
    {
        \Log::channel('daily')->debug('SUBMIT CALLED');
    
    }

    public function updatedValueInput($value)
    {
    
    }

}
