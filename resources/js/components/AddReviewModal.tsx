import { useRef, useState } from 'react';
import { ApiError, get, post } from '../api';
import type { Product, Review, ReviewStatus } from '../types';

interface Props {
    onCreated: (review: Review) => void;
}

interface Form {
    author_name: string;
    author_email: string;
    rating: string;
    title: string;
    body: string;
    status: ReviewStatus;
}

const empty: Form = { author_name: '', author_email: '', rating: '5', title: '', body: '', status: 'approved' };

/** Polaris fields hand back a DOM event; the value lives on the element that fired it. */
type FieldEvent = { currentTarget: { value: string } };

/**
 * A merchant-entered review, for imports and for trying the app before the storefront
 * form exists. Product search goes through the app's API, which asks the Admin API.
 */
export function AddReviewModal({ onCreated }: Props) {
    const modal = useRef<HTMLElementTagNameMap['s-modal']>(null);
    const [search, setSearch] = useState('');
    const [products, setProducts] = useState<Product[]>([]);
    const [product, setProduct] = useState<Product | null>(null);
    const [form, setForm] = useState<Form>(empty);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [saving, setSaving] = useState(false);

    const set = (name: keyof Form) => (e: FieldEvent) => setForm({ ...form, [name]: e.currentTarget.value });
    const error = (name: string) => errors[name]?.[0];

    async function searchProducts(query: string) {
        setSearch(query);
        setProducts(await get<Product[]>(`/products?query=${encodeURIComponent(query)}`));
    }

    async function save() {
        setSaving(true);
        setErrors({});
        try {
            const review = await post<Review>('/reviews', {
                ...form,
                rating: Number(form.rating),
                author_email: form.author_email || null,
                title: form.title || null,
                product_id: product?.id,
                product_title: product?.title,
            });
            onCreated(review);
            shopify.toast.show('Review added');
            setForm(empty);
            setProduct(null);
            modal.current?.hideOverlay();
        } catch (e) {
            if (e instanceof ApiError && e.status === 422) {
                setErrors(e.errors);
            } else {
                shopify.toast.show('Could not save the review', { isError: true });
            }
        } finally {
            setSaving(false);
        }
    }

    return (
        <s-modal id="add-review" heading="Add a review" ref={modal} onShow={() => searchProducts('')}>
            <s-stack gap="base">
                <s-search-field
                    label="Product"
                    placeholder="Search products"
                    value={search}
                    error={error('product_id')}
                    onInput={(e) => searchProducts(e.currentTarget.value)}
                />
                <s-select
                    label="Matching products"
                    value={product?.id ?? ''}
                    onChange={(e) => setProduct(products.find((p) => p.id === e.currentTarget.value) ?? null)}
                >
                    <s-option value="">Choose a product</s-option>
                    {products.map((p) => <s-option key={p.id} value={p.id}>{p.title}</s-option>)}
                </s-select>
                <s-text-field label="Reviewer name" name="author_name" value={form.author_name} error={error('author_name')} onInput={set('author_name')} />
                <s-email-field label="Reviewer email (optional)" name="author_email" value={form.author_email} error={error('author_email')} onInput={set('author_email')} />
                <s-select label="Rating" name="rating" value={form.rating} error={error('rating')} onChange={set('rating')}>
                    {[5, 4, 3, 2, 1].map((n) => <s-option key={n} value={String(n)}>{'★'.repeat(n)}</s-option>)}
                </s-select>
                <s-text-field label="Title (optional)" name="title" value={form.title} error={error('title')} onInput={set('title')} />
                <s-text-area label="Review" name="body" rows={4} value={form.body} error={error('body')} onInput={set('body')} />
                <s-select label="Status" name="status" value={form.status} onChange={set('status')}>
                    <s-option value="approved">Approved</s-option>
                    <s-option value="pending">Pending</s-option>
                    <s-option value="rejected">Rejected</s-option>
                </s-select>
            </s-stack>
            <s-button slot="primary-action" variant="primary" loading={saving} onClick={save}>Save review</s-button>
            <s-button slot="secondary-actions" commandFor="add-review" command="--hide">Cancel</s-button>
        </s-modal>
    );
}
