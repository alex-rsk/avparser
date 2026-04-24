<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\AvitoCategory;

class Categories extends Page
{
    protected string $view = 'filament.pages.categories';

    public $tree;

    public function mount() : void
    {   
        $this->tree = AvitoCategory::getSubTrees();
        dump($this->tree);
    }
}
