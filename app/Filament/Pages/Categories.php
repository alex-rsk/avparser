<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\AvitoCategory;

class Categories extends Page
{
    protected string $view = 'filament.pages.categories';

    protected static  ?string  $navigationLabel = 'Категории';

    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Категории';    
    public $tree;


    public function mount() : void
    {   
        $this->tree = AvitoCategory::getSubTrees();
    }
}
