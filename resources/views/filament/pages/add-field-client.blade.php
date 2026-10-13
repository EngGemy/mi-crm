<x-filament-panels::page>
    <form wire:submit="save" class="space-y-4">
        {{ $this->form }}

        <div class="flex flex-wrap gap-2">
            <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
                {{ ($this->data['send_welcome'] ?? true) ? 'تسجيل وإرسال الترحيب' : 'حفظ العميل' }}
            </x-filament::button>
            <x-filament::button type="button" color="gray" icon="heroicon-o-bookmark" wire:click="saveWelcomeDefault">
                حفظ النص للمرات الجاية
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
