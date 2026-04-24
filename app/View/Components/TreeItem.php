<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Models\AvitoCategory;

class TreeItem extends Component
{

    public $node;

    public $level = 1;
    /**
     * Create a new component instance.
     */
    public function __construct($node, $level = 1)
    {
        $this->node = $node;
        $this->level = $level+1;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {

        return view('components.tree-item');
    }
}
