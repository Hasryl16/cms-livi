<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
            @if ($this->getLastUpdatedInfo())
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $this->getLastUpdatedInfo() }}
                </p>
            @else
                <span></span>
            @endif

            <x-filament::button type="submit" color="primary">
                Save Changes
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
