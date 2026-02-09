<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const selectedFile = ref<File | null>(null);
const error = ref<string | null>(null);

const form = useForm({
    epub: null as File | null,
});

const handleFileSelect = (event: Event) => {
    console.log('📁 File select triggered');
    const target = event.target as HTMLInputElement;
    console.log('- Input element:', target);
    console.log('- Files count:', target.files?.length);

    const file = target.files?.[0];

    if (!file) {
        console.error('❌ No file selected');
        return;
    }

    console.log('✅ File selected:', {
        name: file.name,
        size: file.size,
        type: file.type,
        lastModified: file.lastModified,
    });

    // Validate file type
    if (!file.name.endsWith('.epub')) {
        console.warn('⚠️ File validation failed: not .epub');
        error.value = 'Please select a valid EPUB file';
        selectedFile.value = null;
        form.epub = null;
        return;
    }

    console.log('✅ File validated, storing in form');
    error.value = null;
    selectedFile.value = file;
    form.epub = file;
};

const handleUpload = () => {
    if (!form.epub || form.processing) {
        return;
    }

    error.value = null;

    form.post('/upload', {
        forceFormData: true,
        onSuccess: (response: any) => {
            console.log('✅ Upload success:', response);
        },
        onError: (errors: any) => {
            console.error('❌ Upload error:', errors);
            error.value = errors.epub || Object.values(errors)[0] || 'Upload failed';
        },
    });
};

const triggerFileInput = () => {
    document.getElementById('epub-input')?.click();
};
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-50 p-4 dark:bg-black">
        <Head title="Upload EPUB" />

        <div class="w-full max-w-md">
            <!-- Header -->
            <div class="mb-8 text-center">
                <h1 class="mb-2 text-3xl font-bold text-gray-900 dark:text-gray-100">Upload Your Book</h1>
                <p class="text-gray-600 dark:text-gray-400">Select an EPUB file to start reading</p>
            </div>

            <!-- Upload Card -->
            <div class="rounded-3xl bg-white p-8 shadow-lg dark:bg-gray-900">
                <!-- File Input (Hidden) -->
                <input id="epub-input" type="file" accept=".epub" class="hidden" @change="handleFileSelect" />

                <!-- Upload Area -->
                <button
                    type="button"
                    class="w-full rounded-2xl border-2 border-dashed border-gray-300 p-12 transition-colors hover:border-indigo-500 focus:border-transparent focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:border-gray-700 dark:hover:border-indigo-500"
                    :disabled="form.processing"
                    @click="triggerFileInput"
                >
                    <div class="flex flex-col items-center gap-4">
                        <!-- Upload Icon -->
                        <svg
                            class="h-16 w-16 text-gray-400 dark:text-gray-600"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                            />
                        </svg>

                        <div class="text-center">
                            <p class="mb-1 text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ selectedFile ? selectedFile.name : 'Choose an EPUB file' }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-500">Tap to browse your files</p>
                        </div>
                    </div>
                </button>

                <!-- Error Message -->
                <div v-if="error" class="mt-4 rounded-2xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                    <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
                </div>

                <!-- Upload Button -->
                <button
                    v-if="selectedFile"
                    type="button"
                    class="mt-6 h-14 w-full rounded-full bg-indigo-600 px-6 text-base font-medium text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="form.processing || !selectedFile"
                    @click="handleUpload"
                >
                    <span v-if="form.processing" class="flex items-center justify-center gap-2">
                        <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        Uploading...
                    </span>
                    <span v-else>Start Reading</span>
                </button>

                <!-- Info Text -->
                <p class="mt-6 text-center text-xs text-gray-500 dark:text-gray-500">
                    Only EPUB files are supported. Your book will be parsed and displayed as a scrollable feed.
                </p>
            </div>
        </div>
    </div>
</template>
