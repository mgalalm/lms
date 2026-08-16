@php
    $iconFor = fn (string $type) => match ($type) {
        'enrollment' => 'heroicon-o-user-plus',
        'session_completed' => 'heroicon-o-check-circle',
        'badge_awarded' => 'heroicon-o-trophy',
        'flag' => 'heroicon-o-shield-exclamation',
    };

    $colorClassesFor = fn (string $type) => match ($type) {
        'enrollment' => 'bg-green-100 text-green-600 dark:bg-green-950 dark:text-green-400',
        'session_completed' => 'bg-blue-100 text-blue-600 dark:bg-blue-950 dark:text-blue-400',
        'badge_awarded' => 'bg-amber-100 text-amber-600 dark:bg-amber-950 dark:text-amber-400',
        'flag' => 'bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-400',
    };
@endphp

<x-filament-widgets::widget>
    <x-filament::section heading="Recent Activity">
        @php $events = $this->getActivity(); @endphp

        <div class="flow-root">
            @forelse ($events as $event)
                <div @class([
                    'flex items-start gap-3 py-2',
                    'border-b border-gray-100 dark:border-white/5' => ! $loop->last,
                ])>
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full {{ $colorClassesFor($event['type']) }}">
                        <x-filament::icon :icon="$iconFor($event['type'])" class="size-4" />
                    </div>

                    <div class="min-w-0 flex-1">
                        @if ($event['url'])
                            <a href="{{ $event['url'] }}" class="text-sm text-gray-950 hover:underline dark:text-white">
                                {{ $event['description'] }}
                            </a>
                        @else
                            <p class="text-sm text-gray-950 dark:text-white">{{ $event['description'] }}</p>
                        @endif
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $event['timestamp']->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No recent activity yet.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
