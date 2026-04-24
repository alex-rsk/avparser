<style>
    .level-1 {
        margin-left: 50px;        
    }
    .level-2 {
        margin-left: 25px;        
    }
    .level-3 {
        margin-left: 25px;
    }
</style>
<x-filament-panels::page>
    {{-- Page content --}}
    
    @foreach ($tree as $rootNode)
    <div class="ac-root-node">
        <div class="ac-root-node-header">
            {{ $rootNode['title'] }}
        </div>
        <div class="root-node-content">
            @foreach ($rootNode['children'] as $child)
                <x-tree-item :node="$child" />        
            @endforeach
        </div>
    </div>
    @endforeach 
</x-filament-panels::page>
