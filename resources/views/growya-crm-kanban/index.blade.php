<x-layouts::app :title="'CRM Kanban Board'">
    <div class="mx-auto max-w-[1600px] px-4 py-6 space-y-5">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">CRM Sales Pipeline</h1>
                <p class="text-sm text-zinc-500">{{ $business->name }} · Drag a lead card into another stage (on
                    phone/tablet use Quick update). Showing up to 60 per stage.</p>
            </div>
            <a class="rounded-lg border px-4 py-2 text-sm"
                href="{{ route('growya.crm.staff.dashboard', ['business' => $business->id]) }}">Staff CRM Dashboard</a>
        </div>

        @if (session('success'))
            <div class="rounded-lg bg-emerald-100 p-3 text-emerald-900">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg bg-red-100 p-3 text-red-900">{{ $errors->first() }}</div>
        @endif

        <form method="GET" class="grid gap-3 rounded-xl border p-4 sm:grid-cols-4 dark:border-zinc-700">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Name, email, phone"
                maxlength="120" class="rounded-lg border bg-white p-2 text-zinc-900" />
            <select name="priority" class="rounded-lg border bg-white p-2 text-zinc-900">
                <option value="">All priorities</option>
                @foreach (['high', 'medium', 'low'] as $priority)
                    <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>
                @endforeach
            </select>
            @if ($manager)
                <select name="staff_id" class="rounded-lg border bg-white p-2 text-zinc-900">
                    <option value="">All staff</option>
                    @foreach ($staff as $person)
                        <option value="{{ $person->id }}" @selected((string) request('staff_id') === (string) $person->id)>{{ $person->name }}</option>
                    @endforeach
                </select>
            @else
                <div class="self-center text-sm text-zinc-500">Assigned leads only</div>
            @endif
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-1 text-sm">
                    <input type="checkbox" name="overdue" value="1" @checked(request('overdue') === '1') /> Overdue
                </label>
                <button class="rounded-lg bg-emerald-700 px-3 py-2 text-white">Filter</button>
                <a href="{{ route('growya.crm.kanban.index', ['business' => $business->id]) }}"
                    class="text-sm underline">Clear</a>
            </div>
        </form>

        <p id="kanban-message" role="status" aria-live="polite" class="min-h-5 text-sm text-zinc-500"></p>

        @php $stages = ['new' => 'New', 'contacted' => 'Contacted', 'interested' => 'Interested', 'converted' => 'Converted', 'closed' => 'Closed']; @endphp

        {{-- Columns scroll sideways on small screens instead of stacking, so cards can be dragged between them --}}
        <div class="flex gap-4 overflow-x-auto pb-3" id="kanban-board">
            @foreach ($stages as $key => $label)
                {{-- The WHOLE column is the drop target (data-stage); the zone grows to fill it (flex-1) --}}
                <section
                    class="flex min-w-[17rem] flex-1 flex-col rounded-xl border bg-zinc-50 transition dark:border-zinc-700 dark:bg-zinc-900"
                    data-stage="{{ $key }}">
                    <header class="flex items-center justify-between border-b p-3 dark:border-zinc-700">
                        <h2 class="font-semibold">{{ $label }}</h2>
                        <span class="text-sm tabular-nums text-zinc-500"
                            data-count="{{ $key }}">{{ $counts[$key] ?? 0 }}</span>
                    </header>
                    <div class="min-h-40 flex-1 space-y-3 p-2" data-dropzone="{{ $key }}">
                        @foreach ($columns[$key] as $lead)
                            @php
                                $overdue =
                                    $lead->next_follow_up_at &&
                                    $lead->next_follow_up_at->isPast() &&
                                    !in_array($lead->status, ['converted', 'closed'], true);
                            @endphp
                            <article draggable="true" data-lead="{{ $lead->id }}"
                                data-status="{{ $key }}"
                                class="cursor-grab space-y-2 rounded-xl border bg-white p-3 shadow-sm active:cursor-grabbing dark:border-zinc-700 dark:bg-zinc-800">
                                {{-- draggable="false" so dragging from the name drags the whole card, not just the link --}}
                                <a draggable="false"
                                    href="{{ route('growya.crm.staff.show', ['business' => $business->id, 'contact' => $lead->id]) }}"
                                    class="text-sm font-semibold text-emerald-700 hover:underline dark:text-emerald-400">{{ $lead->name }}</a>
                                <p class="text-xs text-zinc-500">{{ $lead->assignedUser?->name ?? 'Unassigned' }}</p>
                                <div class="flex flex-wrap gap-2 text-xs">
                                    <span
                                        class="rounded bg-zinc-100 px-2 py-1 text-zinc-800 dark:bg-zinc-700 dark:text-white">{{ ucfirst($lead->priority ?? 'medium') }}</span>
                                    <span
                                        class="rounded bg-zinc-100 px-2 py-1 text-zinc-800 dark:bg-zinc-700 dark:text-white">{{ $lead->created_at->diffInDays(now()) }}d
                                        old</span>
                                </div>
                                @if ($lead->next_follow_up_at)
                                    <p class="text-xs {{ $overdue ? 'font-bold text-red-600' : 'text-zinc-500' }}">
                                        {{ $overdue ? 'Overdue: ' : 'Follow-up: ' }}{{ $lead->next_follow_up_at->format('d M Y H:i') }}
                                    </p>
                                @endif
                                <details class="text-xs">
                                    <summary class="cursor-pointer text-emerald-700 dark:text-emerald-400">Quick update
                                    </summary>
                                    <form method="POST"
                                        action="{{ route('growya.crm.kanban.update', ['business' => $business->id, 'contact' => $lead->id]) }}"
                                        class="space-y-2 pt-2">
                                        @csrf @method('PATCH')
                                        <label class="block">Stage
                                            <select name="status"
                                                class="w-full rounded border bg-white p-2 text-zinc-900">
                                                @foreach ($stages as $s => $title)
                                                    <option value="{{ $s }}" @selected($s === $key)>
                                                        {{ $title }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label class="block">Priority
                                            <select name="priority"
                                                class="w-full rounded border bg-white p-2 text-zinc-900">
                                                @foreach (['high', 'medium', 'low'] as $p)
                                                    <option value="{{ $p }}" @selected(($lead->priority ?? 'medium') === $p)>
                                                        {{ ucfirst($p) }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label class="block">Follow-up (blank to clear)
                                            <input type="datetime-local" name="next_follow_up_at"
                                                value="{{ $lead->next_follow_up_at?->format('Y-m-d\TH:i') }}"
                                                class="w-full rounded border bg-white p-2 text-zinc-900" />
                                        </label>
                                        <label class="block">Note
                                            <textarea name="note" maxlength="3000" rows="2" class="w-full rounded border bg-white p-2 text-zinc-900"></textarea>
                                        </label>
                                        <button type="submit"
                                            class="rounded bg-emerald-700 px-3 py-2 text-white">Save</button>
                                    </form>
                                </details>
                            </article>
                        @endforeach
                        @if (($counts[$key] ?? 0) > 60)
                            <p class="text-xs text-amber-700">{{ ($counts[$key] ?? 0) - 60 }} more leads; narrow your
                                filters.</p>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    @php $kanbanUpdateTemplate = route('growya.crm.kanban.update', ['business' => $business->id, 'contact' => '__CONTACT__']); @endphp
    <script>
        (() => {
            const board = document.getElementById('kanban-board');
            if (!board || board.dataset.bound) return;
            board.dataset.bound = '1';

            const message = document.getElementById('kanban-message');
            const updateUrl = id => @json($kanbanUpdateTemplate).replace('__CONTACT__', id);
            const HOVER = ['ring-2', 'ring-emerald-500'];
            // Some browsers report a text node as the event target, so always work from an Element.
            const toEl = t => (t instanceof Element ? t : t && t.parentElement);

            let active = null;
            let hover = null;
            let busy = false;

            const clearHover = () => {
                if (hover) hover.classList.remove(...HOVER);
                hover = null;
            };
            const bump = (stage, delta) => {
                const el = board.querySelector('[data-count="' + stage + '"]');
                if (el) el.textContent = Math.max(0, (parseInt(el.textContent, 10) || 0) + delta);
            };

            // A draggable card breaks typing/selecting inside its form (esp. Firefox),
            // so switch dragging off while the pointer is on a form control.
            board.addEventListener('pointerdown', e => {
                const t = toEl(e.target);
                const card = t && t.closest('[data-lead]');
                if (!card) return;
                card.draggable = !t.closest('summary, details form, input, textarea, select, button, label');
            });

            board.addEventListener('dragstart', e => {
                const card = toEl(e.target)?.closest('[data-lead]');
                if (!card || busy) {
                    e.preventDefault();
                    return;
                }
                active = card;
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', card.dataset.lead);
                requestAnimationFrame(() => card.classList.add('opacity-50'));
            });

            board.addEventListener('dragover', e => {
                if (!active) return;
                const section = toEl(e.target)?.closest('[data-stage]');
                if (!section) return;
                e.preventDefault(); // required, otherwise the browser never fires "drop"
                e.dataTransfer.dropEffect = 'move';
                if (hover !== section) {
                    clearHover();
                    section.classList.add(...HOVER);
                    hover = section;
                }
            });

            board.addEventListener('dragleave', e => {
                if (hover && !hover.contains(e.relatedTarget)) clearHover();
            });

            board.addEventListener('drop', async e => {
                const section = toEl(e.target)?.closest('[data-stage]');
                if (!section || !active || busy) return;
                e.preventDefault();
                clearHover();

                const card = active;
                active = null;
                card.classList.remove('opacity-50');

                const stage = section.dataset.stage;
                const from = card.dataset.status;
                if (stage === from) return;

                const zone = section.querySelector('[data-dropzone]');
                const label = section.querySelector('h2')?.textContent.trim() || stage;
                const parent = card.parentElement;
                const next = card.nextElementSibling;

                busy = true;
                message.textContent = 'Saving…';
                zone.prepend(card); // optimistic move; rolled back below if the save fails

                try {
                    const response = await fetch(updateUrl(card.dataset.lead), {
                        method: 'PATCH',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.content ||
                                @json(csrf_token())
                        },
                        body: JSON.stringify({
                            status: stage
                        })
                    });
                    if (!response.ok) {
                        let text = 'Could not save (HTTP ' + response.status + ').';
                        try {
                            const body = await response.json();
                            if (body && body.message) text = body.message;
                        } catch (_) {}
                        throw new Error(text);
                    }
                    card.dataset.status = stage;
                    const select = card.querySelector('select[name="status"]');
                    if (select) select.value = stage;
                    bump(from, -1);
                    bump(stage, 1);
                    message.textContent = 'Moved to ' + label + '.';
                } catch (err) {
                    parent.insertBefore(card, next);
                    message.textContent = err.message || 'Could not save. Please try again.';
                } finally {
                    busy = false;
                }
            });

            board.addEventListener('dragend', e => {
                const card = toEl(e.target)?.closest('[data-lead]');
                if (card) card.classList.remove('opacity-50');
                active = null;
                clearHover();
            });
        })();
    </script>
</x-layouts::app>
