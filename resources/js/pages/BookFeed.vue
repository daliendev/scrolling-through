<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import BookHeader from '@/components/BookHeader.vue';
import PostCard from '@/components/PostCard.vue';
import ChapterCard from '@/components/ChapterCard.vue';
import NoteModal from '@/components/NoteModal.vue';
import type { Book, Post, UserState } from '@/types/models';

interface Props {
    book: Book;
    posts: Post[];
    userState: UserState | null;
}

const props = defineProps<Props>();

// Local state
const starredPostIds = ref<number[]>(props.userState?.starred_post_ids || []);
const notes = ref<Record<number, Array<{ text: string; timestamp: string }>>>(props.userState?.notes || {});

// Note modal state
const showNoteModal = ref(false);
const selectedPostId = ref<number | null>(null);

// Computed - Filter out empty chapters and empty posts
const displayedPosts = computed(() => {
    return props.posts.filter((post, index) => {
        // Filter out posts with empty or whitespace-only text
        if (!post.text || post.text.trim().length === 0) return false;

        // For paragraphs, just check if text is not empty (already done above)
        if (post.type !== 'chapter') return true;

        // For chapters, check if there's at least one paragraph before the next chapter
        const nextPosts = props.posts.slice(index + 1);
        const nextChapterIndex = nextPosts.findIndex((p) => p.type === 'chapter');

        // Get posts between this chapter and the next chapter (or end)
        const postsUntilNext = nextChapterIndex === -1 ? nextPosts : nextPosts.slice(0, nextChapterIndex);

        // Show chapter only if there's at least one paragraph following it
        return postsUntilNext.some((p) => p.type === 'paragraph' && p.text && p.text.trim().length > 0);
    });
});

const isPostStarred = (postId: number) => starredPostIds.value.includes(postId);
const hasPostNote = (postId: number) => Boolean(notes.value[postId]?.length);

const scrollToFirstChapter = () => {
    // Find the first non-preface chapter
    const firstChapter = displayedPosts.value.find((post) => {
        if (post.type !== 'chapter') return false;
        const title = post.chapter_title?.toLowerCase() || '';
        const isPreface =
            title.includes('preface') ||
            title.includes('préface') ||
            title.includes('introduction') ||
            title.includes('avant-propos') ||
            title.includes('prologue') ||
            title.includes('foreword');
        return !isPreface;
    });

    if (firstChapter) {
        const element = document.querySelector(`[data-post-id="${firstChapter.id}"]`);
        element?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

// Actions

const toggleStar = (postId: number) => {
    // Optimistically update UI
    const index = starredPostIds.value.indexOf(postId);
    if (index > -1) {
        starredPostIds.value.splice(index, 1);
    } else {
        starredPostIds.value.push(postId);
    }

    // Send to backend
    router.post(
        `/api/books/${props.book.id}/posts/${postId}/toggle-star`,
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

const openNoteModal = (postId: number) => {
    selectedPostId.value = postId;
    showNoteModal.value = true;
};

const closeNoteModal = () => {
    showNoteModal.value = false;
    selectedPostId.value = null;
};

const saveNote = (text: string) => {
    if (!selectedPostId.value) return;

    const postId = selectedPostId.value;

    router.post(
        `/api/books/${props.book.id}/posts/${postId}/notes`,
        { note: text },
        {
            preserveScroll: true,
            preserveState: true,
            only: [],
            onSuccess: () => {
                // Update local notes
                if (!notes.value[postId]) {
                    notes.value[postId] = [];
                }
                notes.value[postId].push({ text, timestamp: new Date().toISOString() });
                closeNoteModal();
            },
        },
    );
};

const sharePost = async (postId: number) => {
    try {
        const response = await fetch(`/api/books/${props.book.id}/posts/${postId}/share`, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) throw new Error('Failed to get share text');

        const data = await response.json();
        const text = data.share_text || '';

        // Copy to clipboard
        if (navigator.clipboard) {
            await navigator.clipboard.writeText(text);
            alert('Quote copied to clipboard!');
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('Quote copied to clipboard!');
        }
    } catch (error) {
        console.error('Share error:', error);
        alert('Failed to copy quote. Please try again.');
    }
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-black">
        <Head :title="book.title" />

        <!-- Book Header -->
        <BookHeader :title="book.title" />

        <!-- Feed -->
        <main class="pb-20">
            <template v-for="post in displayedPosts" :key="post.id">
                <!-- Chapter Card -->
                <ChapterCard v-if="post.type === 'chapter'" :chapter="post" :data-post-id="post.id" @scroll-to-first-chapter="scrollToFirstChapter" />

                <!-- Post Card -->
                <PostCard
                    v-else
                    :post="post"
                    :is-starred="isPostStarred(post.id)"
                    :has-note="hasPostNote(post.id)"
                    :data-post-id="post.id"
                    @star="toggleStar(post.id)"
                    @note="openNoteModal(post.id)"
                    @share="sharePost(post.id)"
                />
            </template>
        </main>

        <!-- Note Modal -->
        <NoteModal
            :show="showNoteModal"
            :post-id="selectedPostId || 0"
            :existing-note="selectedPostId && notes[selectedPostId]?.[0]?.text"
            @close="closeNoteModal"
            @save="saveNote"
        />
    </div>
</template>
