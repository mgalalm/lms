<x-filament-widgets::widget>
    <div class="flex items-center justify-between gap-4 rounded-xl border border-red-300 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950">
        <div class="flex items-center gap-3">
            <x-filament::icon icon="heroicon-o-shield-exclamation" class="size-8 shrink-0 text-red-600 dark:text-red-400" />
            <div>
                <p class="text-sm font-semibold text-red-800 dark:text-red-200">
                    {{ $this->getFlaggedCount() }} {{ Str::plural('message', $this->getFlaggedCount()) }} flagged and awaiting review
                </p>
                <p class="text-xs text-red-700 dark:text-red-300">
                    Hidden from other participants until an admin approves or removes them.
                </p>
            </div>
        </div>

        <a
            href="{{ $this->getFlaggedMessagesUrl() }}"
            class="shrink-0 rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700"
        >
            Review Now
        </a>
    </div>
</x-filament-widgets::widget>
