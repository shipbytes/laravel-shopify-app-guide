export function Stars({ rating }: { rating: number }) {
    return <s-text>{'★'.repeat(rating) + '☆'.repeat(5 - rating)}</s-text>;
}
