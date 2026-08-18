<script setup lang="ts">
import icon from '@/assets/branding/gen-academy-icon.svg';
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img :src="icon" alt="" :class="className" v-bind="$attrs" />
</template>
