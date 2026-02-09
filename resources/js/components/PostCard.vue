<script setup lang="ts">
import { ref, computed } from 'vue';

interface Props {
    post: {
        id: number;
        text: string;
        position: number;
    };
    isStarred: boolean;
    hasNote: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    star: [];
    note: [];
    share: [];
}>();

const isVisible = ref(false);
const cardRef = ref<HTMLElement | null>(null);
const showActions = ref(false);

// Detect if post is dialogue
const isDialogue = computed(() => {
    const text = props.post.text.trim();
    // Check for common dialogue markers: quotes, dashes
    return (
        text.startsWith('«') || text.startsWith('"') || text.startsWith('"') || text.startsWith('—') || text.startsWith('–') || text.startsWith('- ')
    );
});

// Toggle actions visibility
const toggleActions = () => {
    showActions.value = !showActions.value;
};

// Intersection observer for progress tracking
const observer = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            isVisible.value = entry.isIntersecting && entry.intersectionRatio >= 0.5;
        });
    },
    { threshold: [0, 0.5, 1] },
);

// Setup observer when card is mounted
const setupObserver = (el: HTMLElement | null) => {
    if (el) {
        cardRef.value = el;
        observer.observe(el);
    }
};

// Emit visibility when it changes
const onVisibilityChange = () => {
    if (isVisible.value) {
        // Emit event for parent to track progress
        emit('star'); // placeholder, will be handled by parent
    }
};
</script>

<template>
    <article
        :ref="setupObserver"
        class="border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900"
        :class="[
            isDialogue ? 'ml-6 border-l-4 border-l-indigo-300 py-4 pr-4 pl-4 dark:border-l-indigo-700' : 'p-4',
            showActions ? 'bg-gray-50 dark:bg-gray-800/50' : '',
        ]"
    >
        <!-- Post Content -->
        <div class="mb-4 cursor-pointer text-base leading-relaxed text-gray-900 dark:text-gray-100" @click="toggleActions">
            {{ post.text }}
        </div>

        <!-- Action Buttons -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-show="showActions" class="flex items-center gap-4">
                <!-- Star Button -->
                <button
                    type="button"
                    class="flex h-11 w-11 items-center justify-center rounded-full transition-colors"
                    :class="[
                        isStarred
                            ? 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700',
                    ]"
                    @click="emit('star')"
                >
                    <svg
                        class="h-6 w-6"
                        :class="{ 'fill-current': isStarred }"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                        />
                    </svg>
                </button>

                <!-- Note Button -->
                <button
                    type="button"
                    class="flex h-11 w-11 items-center justify-center rounded-full transition-colors"
                    :class="[
                        hasNote
                            ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700',
                    ]"
                    @click="emit('note')"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        />
                    </svg>
                </button>

                <!-- Share Button -->
                <button
                    type="button"
                    class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-600 transition-colors hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700"
                    @click="emit('share')"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"
                        />
                    </svg>
                </button>
            </div>
        </Transition>
    </article>
</template>
