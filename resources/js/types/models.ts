export interface Book {
    id: number;
    title: string;
    file_path: string;
    total_posts: number;
    created_at: string;
    updated_at: string;
}

export interface Post {
    id: number;
    book_id: number;
    text: string;
    type: 'chapter' | 'paragraph';
    chapter_title: string | null;
    position: number;
    created_at: string;
    updated_at: string;
}

export interface UserState {
    id: number;
    user_id: number;
    book_id: number;
    current_post_id: number | null;
    starred_post_ids: number[];
    notes: Record<number, Note[]>;
    posts_read: number;
    created_at: string;
    updated_at: string;
}

export interface Note {
    text: string;
    timestamp: string;
}
