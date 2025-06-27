<script setup lang="ts">
import { useStream } from '@laravel/stream-vue';
import '@mdi/font/css/materialdesignicons.css'

const props = defineProps<{
    id: string;
}>();
const { isFetching, isStreaming } = useStream('chat', { id: props.id });
</script>

<template>
    <div v-if="isStreaming" class="size-2 animate-pulse rounded-full bg-green-500"></div>
    <div v-else-if="isFetching" class="size-2 animate-pulse rounded-full bg-yellow-500"></div>
</template>
