<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { EMOJI_CATEGORIES } from '@/lib/emoji';
import { Smile } from 'lucide-vue-next';

const emit = defineEmits<{ select: [emoji: string] }>();

function pick(emoji: string): void {
    emit('select', emoji);
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="size-7" aria-label="Insert emoji">
                <Smile class="size-3.5" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start" class="w-72 p-2">
            <div class="max-h-64 space-y-3 overflow-y-auto pr-1">
                <div v-for="category in EMOJI_CATEGORIES" :key="category.label">
                    <p class="mb-1 px-1 text-xs font-medium text-muted-foreground">{{ category.label }}</p>
                    <div class="grid grid-cols-8 gap-0.5">
                        <button
                            v-for="emoji in category.emoji"
                            :key="emoji"
                            type="button"
                            class="flex size-7 items-center justify-center rounded text-lg leading-none hover:bg-accent"
                            @click="pick(emoji)"
                        >
                            {{ emoji }}
                        </button>
                    </div>
                </div>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
