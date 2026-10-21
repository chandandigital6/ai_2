(() => {
  const menu = document.querySelector('.g-public-menu');
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && menu?.open) {
      menu.open = false;
      menu.querySelector('summary')?.focus();
    }
  });
  document.addEventListener('click', event => {
    if (menu?.open && !menu.contains(event.target)) menu.open = false;
  });
  // A missing upload should not leave a broken image icon over the card.
  document.addEventListener('error', event => {
    const img = event.target;
    if (!(img instanceof HTMLImageElement) || img.dataset.fallback) return;
    const holder = img.closest('.g-product-image,.g-business-cover,.g-service-photo');
    if (!holder) return;
    img.dataset.fallback = '1'; img.hidden = true;
    const placeholder = document.createElement('span');
    placeholder.className = 'g-product-placeholder';
    placeholder.textContent = 'Image unavailable';
    holder.append(placeholder);
  }, true);
})();
