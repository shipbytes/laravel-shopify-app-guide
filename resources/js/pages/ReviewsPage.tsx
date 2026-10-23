import { useCallback, useEffect, useState } from 'react';
import { destroy, get, patch } from '../api';
import type { Paginated, Review, ReviewStatus } from '../types';
import { AddReviewModal } from '../components/AddReviewModal';
import { Stars } from '../components/Stars';

const tones: Record<ReviewStatus, 'success' | 'warning' | 'critical'> = {
    approved: 'success',
    pending: 'warning',
    rejected: 'critical',
};

export function ReviewsPage({ onOpenSettings }: { onOpenSettings: () => void }) {
    const [status, setStatus] = useState<ReviewStatus | ''>('');
    const [page, setPage] = useState(1);
    const [reviews, setReviews] = useState<Paginated<Review> | null>(null);
    const [loading, setLoading] = useState(true);

    const load = useCallback(async () => {
        setLoading(true);
        const params = new URLSearchParams({ page: String(page), ...(status ? { status } : {}) });
        setReviews(await get<Paginated<Review>>(`/reviews?${params}`));
        setLoading(false);
    }, [page, status]);

    useEffect(() => { load(); }, [load]);

    async function moderate(review: Review, next: ReviewStatus) {
        await patch(`/reviews/${review.id}`, { status: next });
        shopify.toast.show(`Review ${next}`);
        load();
    }

    async function remove(review: Review) {
        await destroy(`/reviews/${review.id}`);
        shopify.toast.show('Review deleted');
        load();
    }

    return (
        <s-page heading="Reviews">
            <s-button slot="primary-action" variant="primary" commandFor="add-review" command="--show">Add review</s-button>
            <s-button slot="secondary-actions" onClick={onOpenSettings}>Settings</s-button>

            <s-section>
                <s-table
                    loading={loading}
                    paginate
                    hasPreviousPage={page > 1}
                    hasNextPage={reviews !== null && page < reviews.last_page}
                    onPreviousPage={() => setPage(page - 1)}
                    onNextPage={() => setPage(page + 1)}
                >
                    <s-select slot="filters" label="Status" labelAccessibilityVisibility="exclusive" value={status} onChange={(e) => { setStatus(e.currentTarget.value as ReviewStatus | ''); setPage(1); }}>
                        <s-option value="">All statuses</s-option>
                        <s-option value="pending">Pending</s-option>
                        <s-option value="approved">Approved</s-option>
                        <s-option value="rejected">Rejected</s-option>
                    </s-select>
                    <s-table-header-row>
                        <s-table-header listSlot="primary">Product</s-table-header>
                        <s-table-header listSlot="secondary">Review</s-table-header>
                        <s-table-header>Rating</s-table-header>
                        <s-table-header listSlot="kicker">Status</s-table-header>
                        <s-table-header listSlot="inline">Actions</s-table-header>
                    </s-table-header-row>
                    <s-table-body>
                        {reviews?.data.map((review) => (
                            <s-table-row key={review.id}>
                                <s-table-cell>{review.product_title}</s-table-cell>
                                <s-table-cell>
                                    <s-stack gap="small-300">
                                        <s-text type="strong">{review.title ?? `${review.author_name} wrote`}</s-text>
                                        <s-text>{review.body}</s-text>
                                        <s-text color="subdued">{review.author_name}</s-text>
                                    </s-stack>
                                </s-table-cell>
                                <s-table-cell><Stars rating={review.rating} /></s-table-cell>
                                <s-table-cell><s-badge tone={tones[review.status]}>{review.status}</s-badge></s-table-cell>
                                <s-table-cell>
                                    <s-stack direction="inline" gap="small">
                                        {review.status !== 'approved' && <s-button onClick={() => moderate(review, 'approved')}>Approve</s-button>}
                                        {review.status !== 'rejected' && <s-button onClick={() => moderate(review, 'rejected')}>Reject</s-button>}
                                        <s-button tone="critical" variant="tertiary" onClick={() => remove(review)}>Delete</s-button>
                                    </s-stack>
                                </s-table-cell>
                            </s-table-row>
                        ))}
                    </s-table-body>
                </s-table>
                {reviews !== null && reviews.total === 0 && !loading && (
                    <s-paragraph>No reviews yet. Add one above, or wait for the storefront form in part 6.</s-paragraph>
                )}
            </s-section>

            <AddReviewModal onCreated={() => { setPage(1); load(); }} />
        </s-page>
    );
}
