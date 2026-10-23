export type ReviewStatus = 'pending' | 'approved' | 'rejected';

export interface Review {
    id: number;
    product_id: string;
    product_title: string;
    author_name: string;
    author_email: string | null;
    rating: number;
    title: string | null;
    body: string;
    status: ReviewStatus;
    created_at: string;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
}

export interface Product {
    id: string;
    title: string;
    handle: string;
    image: string | null;
}

export interface Settings {
    auto_approve: boolean;
    widget_title: string;
}

export interface ShopInfo {
    name: string;
    myshopifyDomain: string;
    currencyCode: string;
    plan: { publicDisplayName: string; partnerDevelopment: boolean };
}
