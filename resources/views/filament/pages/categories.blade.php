<style>
    :root {
        --custom-bg: #fff;
        --custom-header-bg: #8cbde6;
        --custom-text: #1e293b;
        --custom-header-text: #080a0c;
        --custom-border: #334155;
        --custom-primary: #60a5fa;
        --custom-hover: #93c5fd;
        --custom-border-bottom: #cbd5e1;
        --ac-header-text-color  :  #334155;
    }
    html.dark {
        --custom-bg: #0f172a;
        --custom-header-bg: #444;
        --custom-text: #f8fafc;
        --custom-header-text: #f8fafc;
        --custom-border: #ccc;
        --custom-primary: #0e51a3;
        --custom-hover: #192147;
        --custom-border-bottom: #444;
        --ac-header-text-color:  #d8b84e;
    }

    .tree-container {
        max-height: 660px;
        overflow-y: auto;
        border: 1px solid var(--custom-border);
        border-radius: 8px;
        padding: 8px;
        background-color: var(--custom-bg);
    }

    .ac-root-node {
        border: 1px solid var(--custom-border);
        border-radius: 6px;
        margin-bottom: 8px;
        overflow: hidden;
    }

    .ac-root-node-header {
        background: var(--custom-header-bg);
        padding: 10px 14px;
        font-weight: 600;
        font-size: 0.95rem;
        color: var(--custom-text);
        cursor: pointer;
        display: flex;
        justify-content: flex-start;
        align-items: center;
        gap: 8px;
        user-select: none;
        border-bottom: 1px solid var(--custom-border-bottom);
    }

    .ac-root-node-header::before {
        content: '▾';
        display: inline-block;
        transition: transform 0.2s ease;
        color: #64748b;
        font-size: 0.85rem;
        width: 14px;
        text-align: center;
    }

    .ac-root-node-header.collapsed::before {
        transform: rotate(-90deg);
    }

    .root-node-content {
        padding: 6px 8px;
    }

    .root-node-content.collapsed {
        display: none;
    }

    /* ── Accordion items (all deeper levels) ── */

    .accordion {
        background-color: var(--custom-header-bg);
        border: 1px solid var(--custom-border);
        border-radius: 5px;
        margin: 4px 0;
        overflow: hidden;
    }

    .ac-header {
        padding: 8px 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
        color: var(--ac-header-text-color);
        background: var(--custom-header-bg);
        border-bottom: 1px solid transparent;
        user-select: none;
        transition: background 0.15s ease;
    }

    .ac-header:hover {
        background: #f1f5f9;
    }

    .ac-header::before {
        content: '▾';
        display: inline-block;
        transition: transform 0.2s ease;
        color: #94a3b8;
        font-size: 0.8rem;
        width: 12px;
        text-align: center;
        flex-shrink: 0;
    }

    .ac-header.collapsed::before {
        transform: rotate(-90deg);
    }

    /* Leaf nodes (no children) — no arrow, no pointer */
    .ac-header.leaf {
        cursor: default;
    }

    .ac-header.leaf::before {
        content: '•';
        font-size: 0.7rem;
        color: #cbd5e1;
    }

    .ac-body {
        padding: 4px 6px;
        border-top: 1px solid #e2e8f0;
        background: var(--custom-bg);
    }

    .ac-body.collapsed {
        display: none;
    }

        /* ── Primary: solid action button ── */
    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 18px;
        background: #3b82f6;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.15s ease, box-shadow 0.15s ease;
    }

    .btn-primary:hover {
        background: #2563eb;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.35);
    }

    .btn-primary:active {
        background: #1d4ed8;
        box-shadow: none;
    }

    /* ── Secondary: outlined ghost button ── */
    .btn-secondary {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 18px;
        background: transparent;
        color: #475569;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }

    .btn-secondary:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        color: #1e293b;
    }

    .btn-secondary:active {
        background: #e2e8f0;
    }

    /* Indentation per level */
    .level-1 { margin-left: 25px; }
    .level-2 { margin-left: 25px; }
    .level-3 { margin-left: 25px; }
    .level-4 { margin-left: 25px; }
</style>


    <div class="tree-container" id="category-tree">
        @foreach ($tree as $rootNode)
        <div class="ac-root-node">
            <div class="ac-root-node-header">
                <div style="width: 80%"> {{ $rootNode->title }} </div>
                <div style="float:left">
                    <button data-catid="{{ $rootNode->id }}" wire:click="selectCategory({{ $rootNode->id }})" class="btn-primary">Заказ</button>
                </div>
            </div>
            <div class="root-node-content collapsed">
                @foreach ($rootNode['children'] as $child)
                    <x-tree-item :node="$child" />
                @endforeach
            </div>
        </div>
        @endforeach
    </div>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const tree = document.getElementById('category-tree');

    tree.addEventListener('click', (e) => {
        // Root-level toggle
        const rootHeader = e.target.closest('.ac-root-node-header');

        if (e.target.nodeName == 'BUTTON') {
            return false;
        }

        if (rootHeader) {
            const content = rootHeader.nextElementSibling;
            rootHeader.classList.toggle('collapsed');
            content.classList.toggle('collapsed');
            return;
        }

        // Deeper-level toggle (skip leaf nodes)
        const acHeader = e.target.closest('.ac-header');
        if (acHeader && !acHeader.classList.contains('leaf')) {
            const body = acHeader.nextElementSibling;
            acHeader.classList.toggle('collapsed');
            body.classList.toggle('collapsed');
        }
    });
});
</script>