<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}
 
        <div style="height:20px"></div>
        <div class="flex justify-end">
            <x-filament::button
                wire:click="generateReport"
                wire:loading.attr="disabled"
                icon="heroicon-o-arrow-down-tray"
                size="lg"
            >
                <span wire:loading.remove wire:target="generateReport">
                    Report
                </span>
                <span wire:loading wire:target="generateReport">
                    Generating…
                </span>
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
 
