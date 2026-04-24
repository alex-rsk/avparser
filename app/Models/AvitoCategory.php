<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvitoCategory extends Model
{
    const MAX_LEVEL = 10;
    protected $table = 'avito_categories';

    protected $guarded = [];

    protected array $childNodes = []; 

    public static function getRoots()
    {
        return self::query()->where('parent_id', 0)->get();
    }

    public static function getSubTrees() 
    {
        $roots = self::query()->where('parent_id', 0)->get();
        foreach ($roots as &$root) {
            $rootNode = AvitoCategory::find($root->id);
            $root->children = self::getSubTree($rootNode);
        }
        //dump($roots);
        return $roots;
    }

    public function getChildrenAttribute()
    {
        return self::query()->where('parent_id', $this->id)->get();
    }

    public static function getSubTree(AvitoCategory $node, int $level = 0): array
    {
        $children = $node->children;
        if ($children->count()>0) {
            $nodeData = ['node'=> $node->title, 'node_id' => $node->id, 'children' => [] ];
            foreach ($children as $child) {
                 //echo str_pad('-', $l).$child->title.PHP_EOL;
                 $nodeData['children'][] = self::getSubTree($child, $level + 1);
            }
        } else {
            return ['nodeId' => $node->id, 'node' => $node->title, 'leaf' => 1];
        }

        return $nodeData;
    }
}
