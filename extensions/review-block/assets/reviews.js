// Runs on the product page. Talks to the app only through the app proxy
// (/apps/reviews/...), which Shopify forwards to the app with a signature.
(function () {
  document.querySelectorAll('[data-reviewmini]').forEach(init);

  function init(root) {
    const endpoint = `${root.dataset.endpoint}/products/${root.dataset.productId}`;
    const list = root.querySelector('[data-reviewmini-list]');
    const form = root.querySelector('[data-reviewmini-form]');
    const status = root.querySelector('[data-reviewmini-status]');

    load();

    form?.addEventListener('submit', async (event) => {
      event.preventDefault();
      status.textContent = '';
      const data = Object.fromEntries(new FormData(form));
      data.rating = Number(data.rating);
      data.product_title = root.dataset.productTitle;

      const response = await fetch(`${endpoint}/reviews`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(data),
      });

      if (!response.ok) {
        status.textContent = form.dataset.error || 'Sorry, that did not go through. Check the fields and try again.';
        return;
      }

      const review = await response.json();
      status.textContent = review.status === 'approved'
        ? 'Thanks! Your review is published.'
        : 'Thanks! Your review will appear once the shop approves it.';
      form.reset();
      if (review.status === 'approved') load();
    });

    async function load() {
      const response = await fetch(endpoint, { headers: { Accept: 'application/json' } });
      if (!response.ok) return;
      const { reviews } = await response.json();
      list.replaceChildren(...reviews.map(render));
    }

    function render(review) {
      const item = document.createElement('li');
      item.className = 'reviewmini__review';
      item.innerHTML = `
        <span class="reviewmini__stars">${'★'.repeat(review.rating)}${'☆'.repeat(5 - review.rating)}</span>
        ${review.title ? `<h4></h4>` : ''}
        <p></p>
        <p class="reviewmini__meta"></p>`;
      if (review.title) item.querySelector('h4').textContent = review.title;
      item.querySelector('p').textContent = review.body;
      item.querySelector('.reviewmini__meta').textContent = `${review.author_name} · ${review.created_at}`;
      return item;
    }
  }
})();
