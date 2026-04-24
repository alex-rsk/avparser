
<div class="accordion">
    <div class="ac-header level-{{$level}}" id="{{ $node->id }}" class="header">
        {{ $node->title }}
    </div>

    <div class="ac-body">
        @foreach ($node->children as $child) 
            <x-tree-item :node="$child" :level="$level+1"/>
        @endforeach
    </div>
    
</div>
 