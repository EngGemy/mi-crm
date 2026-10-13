<x-filament-panels::page>
    <form wire:submit="save" class="space-y-4">
        {{ $this->form }}

        <x-filament::button type="submit" icon="heroicon-o-check">
            حفظ العميل
        </x-filament::button>
    </form>
</x-filament-panels::page>
