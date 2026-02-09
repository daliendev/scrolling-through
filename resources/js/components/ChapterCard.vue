<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    chapter: {
        id: number;
        chapter_title: string | null;
        position: number;
    };
}

const props = defineProps<Props>();
const emit = defineEmits<{
    scrollToFirstChapter: [];
}>();

// Detect if this is a preface/introduction
const isPreface = computed(() => {
    const title = props.chapter.chapter_title?.toLowerCase() || '';
    return (
        title.includes('preface') ||
        title.includes('préface') ||
        title.includes('introduction') ||
        title.includes('avant-propos') ||
        title.includes('prologue') ||
        title.includes('foreword')
    );
});

const chapterLabel = computed(() => (isPreface.value ? 'Préface' : 'Chapter'));
const displayTitle = computed(() => {
    // If it's a preface, use "Préface" as title
    if (isPreface.value) {
        return 'Préface';
    }
    return props.chapter.chapter_title;
});
</script>

<template>
    <div
        class="border-b border-gray-200 bg-gradient-to-br from-indigo-50 to-purple-50 p-6 dark:border-gray-800 dark:from-indigo-950 dark:to-purple-950"
    >
        <div class="mb-2 flex items-center gap-3">
            <div class="h-1 w-12 rounded-full bg-indigo-400 dark:bg-indigo-600"></div>
            <span class="text-xs font-medium tracking-wider text-indigo-600 uppercase dark:text-indigo-400">{{ chapterLabel }}</span>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            {{ displayTitle }}
        </h2>

        <!-- Scroll to First Chapter Button (only for preface) -->
        <button
            v-if="isPreface"
            type="button"
            class="mt-4 rounded-full bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-indigo-700"
            @click="emit('scrollToFirstChapter')"
        >
            Jump to First Chapter →
        </button>
    </div>
</template>
