<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AvitoCategory;

class ParseCategoriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avito:parse-categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $treeFilename = base_path('tree.json');

        if (file_exists($treeFilename)) {
            $tree = file_get_contents($treeFilename);
        }

        $treeObj =  json_decode($tree, true, 512, \JSON_UNESCAPED_UNICODE);
        if (!isset($treeObj['items'])) {
            throw new \Exception("Wrong tree format");
        }

        \DB::statement('TRUNCATE avito_categories');

        $items = array_filter($treeObj['items'], fn($el) => $el['nodeType'] === 'node');
        $rows = [];
        foreach ($items as $item) {
            $rows[]=  [
                'id' => $item['id'],
                'title' => $item['name'],
                'parent_id' => $item['parentId'] ?? 0,
                'category_id' => $item['categoryId'] ?? 0,
                'url' => $item['url'] ?? null,  
                'type' => isset($item['url']) ? 1 : 0
            ];            
        }

        $chunks = array_chunk($rows, 100);
        foreach ($chunks as $chunk) {
            AvitoCategory::insert($chunk);
        }

        
    }
}
