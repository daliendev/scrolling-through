<script setup lang="ts">
import { ref, watch } from 'vue';

interface Props {
    show: boolean;
    postId: number;
    existingNote?: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    save: [text: string];
}>();

const noteText = ref(props.existingNote || '');

watch(
    () => props.show,
    (newVal) => {
        if (newVal && props.existingNote) {
            noteText.value = props.existingNote;
        } else if (newVal) {
            noteText.value = '';
        }
    },
);

const handleSave = () => {
    if (noteText.value.trim()) {
        emit('save', noteText.value.trim());
        noteText.value = '';
    }
};

const handleClose = () => {
    emit('close');
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4" @click.self="handleClose">
                <Transition
                    enter-active-class="transition-transform duration-300"
                    enter-from-class="translate-y-full"
                    enter-to-class="translate-y-0"
                    leave-active-class="transition-transform duration-300"
                    leave-from-class="translate-y-0"
                    leave-to-class="translate-y-full"
                >
                    <div v-if="show" class="w-full max-w-2xl rounded-t-3xl bg-white shadow-2xl dark:bg-gray-900">
                        <!-- Handle Bar -->
                        <div class="flex justify-center pt-3 pb-2">
                            <div class="h-1 w-12 rounded-full bg-gray-300 dark:bg-gray-700"></div>
                        </div>

                        <!-- Header -->
                        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Add Note</h3>
                            <button
                                type="button"
                                class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                                @click="handleClose"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Content -->
                        <div class="p-6">
                            <textarea
                                v-model="noteText"
                                class="h-32 w-full resize-none rounded-2xl border border-gray-300 p-4 text-base focus:border-transparent focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                                placeholder="Write your note here..."
                                autofocus
                            ></textarea>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3 px-6 pb-6">
                            <button
                                type="button"
                                class="h-12 flex-1 rounded-full bg-gray-100 px-6 text-base font-medium text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                @click="handleClose"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                class="h-12 flex-1 rounded-full bg-indigo-600 px-6 text-base font-medium text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="!noteText.trim()"
                                @click="handleSave"
                            >
                                Save Note
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
