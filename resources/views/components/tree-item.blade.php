@props(['node', 'level' => 1])

@php $hasChildren = $node->children && $node->children->count() > 0; @endphp

<div class="accordion">
    <div
        class="ac-header collapsed level-{{ $level }} {{ !$hasChildren ? 'leaf' : '' }}"
        id="node-{{ $node->id }}"
    >
        <div style="width: 80%"> {{ $node->title }} </div>
        <div style="float:left">
            <button data-catid="{{ $node->id }}" class="btn-primary" wire:click="selectCategory({{ $node->id }})">Заказ</button>
        </div>

    </div>

    @if ($hasChildren)
    <div class="ac-body collapsed">
        @foreach ($node->children as $child)
            <x-tree-item :node="$child" :level="$level + 1" />
        @endforeach
    </div>
    @endif
</div>