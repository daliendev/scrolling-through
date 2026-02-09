<script setup lang="ts">
interface Props {
    title: string;
    coverUrl?: string | null;
}

defineProps<Props>();
</script>

<template>
    <div class="relative overflow-hidden border-b border-gray-200 bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 dark:border-gray-800">
        <!-- Optional Cover Image Background -->
        <div v-if="coverUrl" class="absolute inset-0 opacity-20">
            <img :src="coverUrl" alt="" class="h-full w-full object-cover blur-2xl" />
        </div>

        <!-- Content -->
        <div class="relative px-6 py-12">
            <!-- Cover Image (if available) -->
            <div v-if="coverUrl" class="mb-6 flex justify-center">
                <img :src="coverUrl" alt="Book cover" class="h-48 w-32 rounded-lg object-cover shadow-2xl" />
            </div>

            <!-- Book Title -->
            <div class="text-center">
                <h1 class="mb-2 text-4xl font-bold text-white drop-shadow-lg md:text-5xl">
                    {{ title }}
                </h1>
                <div class="mx-auto mt-4 h-1 w-24 rounded-full bg-white/50"></div>
            </div>
        </div>
    </div>
</template>
