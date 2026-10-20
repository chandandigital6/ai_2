(() => {
  const boundRoots = new WeakSet();
  function init() {
    document.querySelectorAll('[data-ai-root]').forEach(root => {
      if (boundRoots.has(root)) return;
      boundRoots.add(root);
      let toastTimer;
      const toast = (message, error = false) => {
        const el = root.querySelector('.ai-toast');
        if (!el) return;
        clearTimeout(toastTimer); el.hidden = false; el.textContent = message; el.dataset.error = String(error);
        toastTimer = setTimeout(() => el.hidden = true, error ? 12000 : 5000);
      };
      const parse = selector => JSON.parse(root.querySelector(selector)?.textContent || '[]');
      const presets = parse('[data-ai-presets]');
      const packs = parse('[data-ai-packs]');
      const urlFor = (key, id) => root.dataset[key].replace('__ID__', String(id));
      const errorText = (response, payload) => {
        if (payload.errors) return Object.values(payload.errors).flat().join('\n');
        if (response.status === 429) return (payload.message || 'Too many requests.') + ' Retry after ' + (payload.retry_after || response.headers.get('Retry-After') || 60) + ' seconds.';
        if (response.status === 419 || response.status === 401) return 'Your session has expired. Reload the page and sign in again.';
        if (response.status === 413) return 'The image is too large for this server. Choose a smaller image.';
        return payload.message || (response.status >= 500 ? 'Server error. Please try again or contact the administrator.' : 'This action could not be completed.');
      };
      const request = async (url, options = {}) => {
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 35000);
        try {
          const r = await fetch(url, {...options, signal: controller.signal, credentials: 'same-origin', headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(options.headers || {})}});
          const body = await r.json().catch(() => ({}));
          if (!r.ok) throw new Error(errorText(r, body));
          return body.data;
        } catch (e) {
          if (e.name === 'AbortError') throw new Error('The request timed out. Check your connection. If you retry, the same request key prevents duplicate charges.');
          if (e instanceof TypeError) throw new Error('Unable to connect. Check your internet connection and try again.');
          throw e;
        } finally { clearTimeout(timer); }
      };
      const add = (parent, tag, text, className) => { const el = document.createElement(tag); el.textContent = text; if (className) el.className = className; parent.append(el); return el; };
      function render(card, data) {
        card.dataset.status = data.status;
        card.querySelector('[data-result-status]').textContent = data.status;
        card.querySelector('[data-result-error]').textContent = data.error || '';
        const out = card.querySelector('[data-result-output]');
        out.replaceChildren();
        if (data.output_json?.text) {
          add(out, 'p', data.output_json.text, 'ex-output');
          add(out, 'button', 'Copy caption', 'g-btn g-btn-secondary').setAttribute('data-copy-caption', '');
        }
        for (const media of data.media || []) {
          const url = urlFor('mediaUrl', media.id);
          const img = add(out, 'img', '', 'ai-preview'); img.alt = 'Generated creative'; img.src = url + '?inline=1';
          const link = add(out, 'a', 'Download image', 'g-btn g-btn-secondary'); link.href = url;
        }
      }
      const refreshCredits = () => {
        if (!root.dataset.statusUrl) return;
        const creditsUrl = root.dataset.statusUrl.split('/generations/')[0] + '/credits';
        request(creditsUrl).then(d => {
          const el = root.querySelector('[data-ai-balance]'); if (el) el.textContent = d.entitlements.available_credits;
          packs.splice(0, packs.length, ...d.entitlements.packs);
          root.querySelectorAll('[name=plan_request_id] option[value]').forEach(option => {
            const pack = packs.find(p => String(p.id) === option.value);
            if (pack) option.textContent = '#' + pack.id + ' ' + pack.plan_snapshot.name + ' · ' + Math.max(0, pack.plan_snapshot.credits - pack.used_credits) + ' credits';
          });
        }).catch(() => {});
      };
      const polling = new Set();
      async function poll(card) {
        const id = card.dataset.generation;
        if (polling.has(id)) return;
        polling.add(id);
        let failures = 0;
        const started = Date.now();
        async function tick() {
          if (!root.isConnected) { polling.delete(id); return; }
          try {
            const data = await request(urlFor('statusUrl', id));
            failures = 0; render(card, data);
            if (['completed', 'failed'].includes(data.status)) {
              polling.delete(id);
              toast(data.status === 'completed' ? 'Your creative is ready.' : data.error, data.status === 'failed');
              refreshCredits();
              return;
            }
          } catch (e) {
            failures++;
            if (failures === 1) toast(e.message, true);
          }
          if (Date.now() - started > 31 * 60 * 1000) {
            polling.delete(id); toast('This job is taking longer than expected. Reload to check its final status. Do not submit a duplicate request.', true); return;
          }
          setTimeout(tick, failures ? 15000 : 3500);
        }
        setTimeout(tick, 1200);
      }
      root.querySelectorAll('[data-generation]').forEach(card => { if (['queued', 'processing'].includes(card.dataset.status)) poll(card); });
      root.addEventListener('submit', async event => {
        const form = event.target.closest('[data-ai-form]'); if (!form) return;
        event.preventDefault();
        if (form.dataset.sending) return;
        const error = form.querySelector('[data-form-error]'); if (error) error.textContent = '';
        const button = form.querySelector('button[type=submit],button:not([type])');
        const original = button?.textContent;
        form.dataset.sending = '1'; if (button) { button.disabled = true; button.textContent = 'Please wait…'; }
        try {
          const body = new FormData(form);
          if (form.hasAttribute('data-generation-form')) {
            body.set('vary_design', form.elements.vary_design.checked ? '1' : '0');
            if (form.elements.reference_mode.value === 'saved') body.delete('reference_ids[]');
            else if (!body.has('reference_ids[]')) body.set('reference_ids', '');
          }
          if (form.hasAttribute('data-preset-form') && !body.has('reference_ids[]')) body.set('reference_ids', '');
          const result = await request(form.action, {method: 'POST', body});
          if (form.hasAttribute('data-generation-form')) {
            let card = root.querySelector(`[data-generation="${result.id}"]`);
            if (!card) {
              card = add(root.querySelector('[data-live-results]'), 'article', '', 'ai-result'); card.dataset.generation = String(result.id);
              add(card, 'h3', (result.generation_type === 'text' ? 'Caption' : 'Image') + ' #' + result.id);
              add(card, 'p', result.model + ' · ' + result.credits_used + ' credits', 'ex-note');
              add(card, 'span', result.status, 'ex-status').setAttribute('data-result-status', '');
              add(card, 'div', '').setAttribute('data-result-output', '');
              add(card, 'p', '', 'ai-error').setAttribute('data-result-error', '');
            }
            render(card, result); refreshCredits();
            if (['queued', 'processing'].includes(result.status)) { toast('Generation started. Your result will appear here automatically.'); poll(card); }
            else toast(result.status === 'failed' ? result.error : 'Your creative is ready.', result.status === 'failed');
            form.elements.request_key.value = crypto.randomUUID ? crypto.randomUUID() : Date.now() + '-' + Math.random().toString(36).slice(2);
            card.scrollIntoView({behavior: 'smooth', block: 'center'});
          } else {
            toast('Saved successfully.'); setTimeout(() => location.reload(), 500);
          }
        } catch (e) { if (error) error.textContent = e.message; toast(e.message, true); }
        finally { delete form.dataset.sending; if (button) { button.disabled = false; button.textContent = original; } }
      });
      root.addEventListener('click', async event => {
        const copy = event.target.closest('[data-copy-caption]');
        if (copy) {
          try { await navigator.clipboard.writeText(copy.parentElement.querySelector('.ex-output').textContent); toast('Caption copied.'); }
          catch { toast('Unable to copy automatically. Select the caption text and copy it.', true); }
        }
        const edit = event.target.closest('[data-edit-preset]');
        const form = root.querySelector('[data-preset-form]');
        if (edit && form) {
          const preset = presets.find(p => String(p.id) === edit.dataset.editPreset); if (!preset) return;
          form.action = urlFor('presetUrl', preset.id); form.elements._method.value = 'PUT';
          for (const key of ['name', 'prompt', 'negative_prompt', 'generation_type', 'size', 'language']) form.elements[key].value = preset[key] || '';
          form.querySelectorAll('[name="reference_ids[]"]').forEach(c => c.checked = (preset.reference_ids || []).map(String).includes(c.value));
          form.querySelector('[data-preset-save]').textContent = 'Update saved prompt'; form.scrollIntoView({behavior: 'smooth'});
        }
        if (event.target.closest('[data-preset-reset]') && form) { form.reset(); form.action = form.dataset.createUrl; form.elements._method.value = 'POST'; form.querySelector('[data-preset-save]').textContent = 'Save new prompt'; }
      });
      const generation = root.querySelector('[data-generation-form]');
      if (generation) {
        function packageInfo() {
          const pack = packs.find(p => String(p.id) === generation.elements.plan_request_id.value);
          const type = generation.elements.generation_type.value;
          const el = root.querySelector('[data-package-info]');
          if (!pack) { el.textContent = 'Select a package to see its model and cost.'; return; }
          const plan = pack.plan_snapshot;
          el.textContent = plan[type + '_model'] ? plan[type + '_model'] + ' · ' + plan[type + '_credits'] + ' credits per generation · ' + plan.max_references + ' references + your logo.' : 'This package does not include ' + type + ' generation.';
        }
        generation.addEventListener('change', e => {
          if (e.target.name === 'preset_id') {
            const preset = presets.find(p => String(p.id) === e.target.value);
            if (preset) generation.elements.generation_type.value = preset.generation_type;
          }
          packageInfo();
        }); packageInfo();
      }
    });
  }
  document.addEventListener('livewire:navigated', init);
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
