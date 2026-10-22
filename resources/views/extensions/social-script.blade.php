<script>
    (() => {
        const root = document.querySelector('[data-social-workspace]');
        if (!root) return;
        const form = root.querySelector('[data-social-compose]'),
            toast = root.querySelector('[data-social-toast]');
        let toastTimer, imageUrl;
        const showError = message => {
            toast.textContent = message;
            toast.hidden = false;
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => toast.hidden = true, 7000)
        };
        if (root.dataset.serverError) showError(root.dataset.serverError);
        if (form) {
            const caption = form.elements.caption,
                image = form.elements.image,
                mode = form.elements.publish_mode,
                scheduled = form.elements.scheduled_at;
            const selected = () => [...form.querySelectorAll('[name="account_ids[]"]:checked')];
            const update = () => {
                root.querySelector('[data-preview-caption]').textContent = caption.value ||
                    'Your caption preview will appear here.';
                root.querySelector('[data-caption-count]').textContent = caption.value.length;
                root.querySelector('[data-preview-accounts]').textContent = selected().map(i => i.closest(
                    'label').querySelector('strong').textContent).join(' · ') || 'No accounts selected';
                const isScheduled = mode.value === 'scheduled';
                root.querySelector('[data-scheduled-field]').hidden = !isScheduled;
                scheduled.required = isScheduled;
                scheduled.disabled = !isScheduled || form.querySelector('fieldset').disabled;
                root.querySelector('[data-compose-submit]').textContent = ({
                    draft: 'Save draft',
                    now: 'Publish now',
                    scheduled: 'Schedule post'
                })[mode.value]
            };
            form.addEventListener('input', update);
            form.addEventListener('change', update);
            root.querySelector('[data-social-festival]')?.addEventListener('change', event => {
                if (event.target.value) caption.value = event.target.value;
                update()
            });
            image.addEventListener('change', () => {
                if (imageUrl) URL.revokeObjectURL(imageUrl);
                const file = image.files[0],
                    preview = root.querySelector('[data-preview-image]');
                preview.hidden = true;
                preview.removeAttribute('src');
                root.querySelector('[data-preview-placeholder]').hidden = false;
                if (file && ['image/jpeg', 'image/png'].includes(file.type) && file.size <= 8 * 1024 *
                    1024) {
                    imageUrl = URL.createObjectURL(file);
                    preview.src = imageUrl;
                    preview.hidden = false;
                    root.querySelector('[data-preview-placeholder]').hidden = true
                } else if (file) {
                    showError('Choose a JPEG or PNG image up to 8 MB.');
                    image.value = ''
                }
            });
            form.addEventListener('submit', event => {
                let message = '';
                const instagram = selected().some(i => i.dataset.platform === 'instagram');
                if (!selected().length) message = 'Choose at least one connected account.';
                else if (instagram && (!image.files[0] || image.files[0].type !== 'image/jpeg')) message =
                    'Instagram needs a JPEG image. Please choose a JPG/JPEG file.';
                else if (mode.value === 'scheduled' && (!scheduled.value || new Date(scheduled.value) <=
                        new Date())) message = 'Choose a future publishing time.';
                const error = root.querySelector('[data-compose-error]');
                error.hidden = !message;
                error.textContent = message;
                if (message) {
                    event.preventDefault();
                    showError(message)
                } else {
                    const button = root.querySelector('[data-compose-submit]');
                    button.disabled = true;
                    button.textContent = 'Saving…'
                }
            });
            update();
        }
        const recurring = root.querySelector('[data-social-recurring]');
        if (recurring) {
            const updateDays = () => {
                const weekly = recurring.elements.frequency.value === 'weekly';
                recurring.querySelector('[data-weekdays]').hidden = !weekly;
                recurring.querySelectorAll('[name="days_of_week[]"]').forEach(i => i.disabled = !weekly ||
                    recurring.querySelector('fieldset').disabled)
            };
            recurring.elements.frequency.addEventListener('change', updateDays);
            updateDays();
            recurring.addEventListener('submit', event => {
                if (recurring.elements.frequency.value === 'weekly' && !recurring.querySelector(
                        '[name="days_of_week[]"]:checked')) {
                    event.preventDefault();
                    showError('Choose at least one weekday.')
                }
            })
        }
        // Only history refreshes: unsaved composer input stays in place.
        let busy = false;
        const timer = setInterval(async () => {
            const history = root.querySelector('#social-post-history');
            if (!root.isConnected) {
                clearInterval(timer);
                return
            }
            if (document.hidden || busy || history?.dataset.pending !== 'true') return;
            busy = true;
            const controller = new AbortController(),
                timeout = setTimeout(() => controller.abort(), 12000);
            try {
                const response = await fetch(window.location.href, {
                    credentials: 'same-origin',
                    signal: controller.signal,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!response.ok || response.redirected) throw new Error('Refresh unavailable');
                const html = new DOMParser().parseFromString(await response.text(), 'text/html'),
                    next = html.querySelector('#social-post-history');
                if (!next) throw new Error('Session expired');
                history.replaceWith(next)
            } catch {
                const message = history?.querySelector('[data-poll-status]');
                if (message) message.textContent =
                    'Could not refresh status. Use Refresh status to try again.'
            } finally {
                clearTimeout(timeout);
                busy = false
            }
        }, 15000);
    })();
</script>
