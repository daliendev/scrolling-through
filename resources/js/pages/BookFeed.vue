<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import NoteModal from '@/components/NoteModal.vue';
import type { Book, Post, UserState } from '@/types/models';

interface Props {
    book: Book;
    posts: Post[];
    userState: UserState | null;
}

const props = defineProps<Props>();

// Current post index (TikTok-like: one post at a time)
const currentIndex = ref(0);

// Local state
const starredPostIds = ref<number[]>(props.userState?.starred_post_ids || []);
const notes = ref<Record<number, Array<{ text: string; timestamp: string }>>>(props.userState?.notes || {});

// Note modal state
const showNoteModal = ref(false);

// Touch handling for swipe
const touchStartY = ref(0);
const touchEndY = ref(0);

// Computed - Filter out empty posts
const displayedPosts = computed(() => {
    return props.posts.filter((post) => {
        if (!post.text || post.text.trim().length === 0) return false;
        if (post.type !== 'chapter') return true;

        const index = props.posts.indexOf(post);
        const nextPosts = props.posts.slice(index + 1);
        const nextChapterIndex = nextPosts.findIndex((p) => p.type === 'chapter');
        const postsUntilNext = nextChapterIndex === -1 ? nextPosts : nextPosts.slice(0, nextChapterIndex);

        return postsUntilNext.some((p) => p.type === 'paragraph' && p.text && p.text.trim().length > 0);
    });
});

const currentPost = computed(() => displayedPosts.value[currentIndex.value]);
const isPostStarred = computed(() => (currentPost.value ? starredPostIds.value.includes(currentPost.value.id) : false));
const hasPostNote = computed(() => (currentPost.value ? Boolean(notes.value[currentPost.value.id]?.length) : false));
const progress = computed(() => Math.round(((currentIndex.value + 1) / displayedPosts.value.length) * 100));

// Navigation
const goNext = () => {
    if (currentIndex.value < displayedPosts.value.length - 1) {
        currentIndex.value++;
        updateProgress();
    }
};

const goPrevious = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--;
    }
};

const updateProgress = () => {
    if (!currentPost.value) return;

    router.post(
        `/books/${props.book.id}/progress`,
        {
            current_post_id: currentPost.value.id,
            posts_read: currentIndex.value + 1,
        },
        {
            preserveScroll: true,
            preserveState: true,
            only: [],
        },
    );
};

// Touch/Scroll handlers
const handleTouchStart = (e: TouchEvent) => {
    touchStartY.value = e.touches[0].clientY;
};

const handleTouchEnd = (e: TouchEvent) => {
    touchEndY.value = e.changedTouches[0].clientY;
    const diff = touchStartY.value - touchEndY.value;

    // Swipe up = next, swipe down = previous
    if (Math.abs(diff) > 50) {
        if (diff > 0) {
            goNext();
        } else {
            goPrevious();
        }
    }
};

const handleWheel = (e: WheelEvent) => {
    e.preventDefault();
    if (e.deltaY > 0) {
        goNext();
    } else if (e.deltaY < 0) {
        goPrevious();
    }
};

// Actions
const toggleStar = () => {
    if (!currentPost.value) return;

    const postId = currentPost.value.id;
    const index = starredPostIds.value.indexOf(postId);

    if (index > -1) {
        starredPostIds.value.splice(index, 1);
    } else {
        starredPostIds.value.push(postId);
    }

    router.post(
        `/books/${props.book.id}/posts/${postId}/toggle-star`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            only: [],
            onError: () => {
                // Revert on error
                const idx = starredPostIds.value.indexOf(postId);
                if (idx > -1) {
                    starredPostIds.value.splice(idx, 1);
                } else {
                    starredPostIds.value.push(postId);
                }
            },
        },
    );
};

const openNoteModal = () => {
    showNoteModal.value = true;
};

const closeNoteModal = () => {
    showNoteModal.value = false;
};

const saveNote = (text: string) => {
    if (!currentPost.value) return;

    const postId = currentPost.value.id;

    router.post(
        `/books/${props.book.id}/posts/${postId}/notes`,
        { text },
        {
            preserveScroll: true,
            preserveState: true,
            only: [],
            onSuccess: () => {
                if (!notes.value[postId]) {
                    notes.value[postId] = [];
                }
                notes.value[postId].push({ text, timestamp: new Date().toISOString() });
                closeNoteModal();
            },
        },
    );
};

const sharePost = () => {
    if (!currentPost.value) return;

    const shareText = `"${currentPost.value.text}" — ${props.book.title}`;

    if (navigator.clipboard) {
        navigator.clipboard
            .writeText(shareText)
            .then(() => {
                alert('Quote copied to clipboard!');
            })
            .catch(() => {
                alert('Failed to copy quote. Please try again.');
            });
    } else {
        const textArea = document.createElement('textarea');
        textArea.value = shareText;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('Quote copied to clipboard!');
    }
};

// Keyboard navigation
const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === 'ArrowDown' || e.key === ' ') {
        e.preventDefault();
        goNext();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        goPrevious();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <div class="fixed inset-0 overflow-hidden bg-black" @touchstart="handleTouchStart" @touchend="handleTouchEnd" @wheel.passive="handleWheel">
        <Head :title="book.title" />

        <!-- Current Post (Fullscreen) -->
        <div v-if="currentPost" class="relative flex h-full w-full items-center justify-center p-6">
            <!-- Chapter Title (if chapter) -->
            <div v-if="currentPost.type === 'chapter'" class="text-center">
                <h1 class="mb-4 text-4xl font-bold text-white md:text-5xl">
                    {{ currentPost.chapter_title }}
                </h1>
                <p class="text-xl text-gray-400">Swipe up to continue</p>
            </div>

            <!-- Paragraph Text -->
            <div v-else class="max-w-2xl">
                <p class="text-xl leading-relaxed text-white md:text-2xl">
                    {{ currentPost.text }}
                </p>
            </div>

            <!-- Progress Bar -->
            <div class="absolute top-0 right-0 left-0 h-1 bg-gray-800">
                <div class="h-full bg-indigo-600 transition-all duration-300" :style="{ width: `${progress}%` }"></div>
            </div>

            <!-- Action Buttons (Right Side) -->
            <div class="absolute right-4 bottom-20 flex flex-col gap-6">
                <!-- Star -->
                <button
                    @click="toggleStar"
                    class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-900/80 backdrop-blur transition-colors hover:bg-gray-800"
                    :class="isPostStarred ? 'text-yellow-400' : 'text-white'"
                >
                    <svg class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                </button>

                <!-- Note -->
                <button
                    @click="openNoteModal"
                    class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-900/80 backdrop-blur transition-colors hover:bg-gray-800"
                    :class="hasPostNote ? 'text-blue-400' : 'text-white'"
                >
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        />
                    </svg>
                </button>

                <!-- Share -->
                <button
                    @click="sharePost"
                    class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-900/80 text-white backdrop-blur transition-colors hover:bg-gray-800"
                >
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"
                        />
                    </svg>
                </button>
            </div>

            <!-- Book Title & Progress (Bottom) -->
            <div class="absolute right-24 bottom-6 left-6">
                <h2 class="text-sm font-medium text-gray-400">{{ book.title }}</h2>
                <p class="mt-1 text-xs text-gray-500">{{ currentIndex + 1 }} / {{ displayedPosts.length }}</p>
            </div>

            <!-- Navigation Hints -->
            <div
                v-if="currentIndex < displayedPosts.length - 1"
                class="absolute bottom-6 left-1/2 flex -translate-x-1/2 flex-col items-center text-gray-500"
            >
                <svg class="h-6 w-6 animate-bounce" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
            </div>
        </div>

        <!-- Note Modal -->
        <NoteModal
            :show="showNoteModal"
            :post-id="currentPost?.id || 0"
            :existing-note="currentPost && notes[currentPost.id]?.[0]?.text"
            @close="closeNoteModal"
            @save="saveNote"
        />
    </div>
</template>
