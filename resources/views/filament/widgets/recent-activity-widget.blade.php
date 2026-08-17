@php
    $iconFor = fn (string $type) => match ($type) {
        'enrollment' => 'heroicon-o-user-plus',
        'session_completed' => 'heroicon-o-check-circle',
        'badge_awarded' => 'heroicon-o-trophy',
        'flag' => 'heroicon-o-shield-exclamation',
    };

    $colorClassesFor = fn (string $type) => match ($type) {
        'enrollment' => 'bg-green-500',
        'session_completed' => 'bg-blue-500',
        'badge_awarded' => 'bg-amber-500',
        'flag' => 'bg-red-500',
    };
@endphp

<x-filament-widgets::widget>
    <x-filament::section heading="Recent Activity">
        @php $events = $this->getActivity(); @endphp

        <div class="flow-root">
            @if ($events->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">No recent activity yet.</p>
            @else
                <ul role="list" class="-mb-8">
                    @foreach ($events as $event)
                        <li>
                            <div class="relative pb-8">
                                @unless ($loop->last)
                                    <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-white/10" aria-hidden="true"></span>
                                @endunless

                                <div class="relative flex space-x-3">
                                    <div>
                                        <span class="flex size-8 items-center justify-center rounded-full {{ $colorClassesFor($event['type']) }} ring-8 ring-white dark:ring-gray-900">
                                            <x-filament::icon :icon="$iconFor($event['type'])" class="size-5 text-white" />
                                        </span>
                                    </div>

                                    <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                        <div class="min-w-0">
                                            @if ($event['url'])
                                                <a href="{{ $event['url'] }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">
                                                    {{ $event['description'] }}
                                                </a>
                                            @else
                                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $event['description'] }}</p>
                                            @endif
                                        </div>
                                        <div class="whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">
                                            <time datetime="{{ $event['timestamp']->toIso8601String() }}">{{ $event['timestamp']->diffForHumans() }}</time>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
