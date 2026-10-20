<x-layouts::app :title="__('Subscription plans')">
    <div class="g-page">
        <div class="g-page-heading"
            style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem;">
            <div>
                <p class="g-kicker"
                    style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin:0;">
                    Subscriptions</p>
                <h1 style="font-size:1.75rem; font-weight:700; margin:0.25rem 0;">Manage plans</h1>
                <p style="color:#64748b; margin:0;">Create, modify pricing, and activate/deactivate plans.</p>
            </div>
            <a class="g-btn g-btn-secondary" href="{{ route('growya.subscriptions.admin') }}">View requests</a>
        </div>

        @if (session('success'))
            <div class="g-alert g-tone-green"
                style="padding:12px 16px; border-radius:8px; margin-bottom:1rem; background:#dcfce7; color:#166534;">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="g-alert"
                style="padding:12px 16px; border-radius:8px; margin-bottom:1rem; background:#fee2e2; color:#991b1b;">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Create Plan Card --}}
        <section class="g-panel"
            style="padding:1.5rem; border-radius:12px; background:#fff; border:1px solid #e2e8f0; margin-bottom:2rem;">
            <h2 style="font-size:1.15rem; font-weight:700; margin-bottom:1rem;">Create new plan</h2>
            <form method="POST" action="{{ route('growya.plans.store') }}"
                style="display:grid; gap:1rem; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));">
                @csrf
                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Plan
                        name</label>
                    <input name="name" required maxlength="120" value="{{ old('name') }}"
                        placeholder="e.g. Starter, Premium"
                        style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px;">
                </div>
                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Slug</label>
                    <input name="slug" required maxlength="120" value="{{ old('slug') }}"
                        placeholder="e.g. starter-pack"
                        style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px;">
                </div>
                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Monthly price
                        (₹)</label>
                    <input type="number" step="0.01" min="0" name="monthly_price" required
                        value="{{ old('monthly_price') }}"
                        style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px;">
                </div>
                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Yearly price
                        (₹)</label>
                    <input type="number" step="0.01" min="0" name="yearly_price" required
                        value="{{ old('yearly_price') }}"
                        style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px;">
                </div>
                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Trial
                        days</label>
                    <input type="number" min="0" max="365" name="trial_days" value="0" required
                        style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px;">
                </div>
                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Sort
                        order</label>
                    <input type="number" min="0" name="sort_order" value="0"
                        style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px;">
                </div>
                <div style="display:flex; align-items:center; gap:8px; margin-top:1.5rem;">
                    <input type="checkbox" id="create_active" name="is_active" value="1" checked
                        style="width:18px; height:18px;">
                    <label for="create_active" style="font-size:0.85rem; font-weight:600;">Available for
                        requests</label>
                </div>
                <div style="margin-top:1.25rem;">
                    <button class="g-btn g-btn-primary" type="submit" style="width:100%; padding:9px;">Create
                        plan</button>
                </div>
            </form>
        </section>

        {{-- Existing Plans List --}}
        <section class="g-panel" style="padding:1.5rem; border-radius:12px; background:#fff; border:1px solid #e2e8f0;">
            <h2 style="font-size:1.15rem; font-weight:700; margin-bottom:1rem;">Configured plans</h2>
            <div style="display:flex; flex-direction:column; gap:1.25rem;">
                @forelse($plans as $plan)
                    <div style="border:1px solid #e2e8f0; border-radius:10px; padding:1.25rem; background:#f8fafc;">
                        <form method="POST" action="{{ route('growya.plans.update', $plan) }}"
                            style="display:grid; gap:1rem; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); align-items:flex-end;">
                            @csrf
                            @method('PATCH')

                            <div>
                                <small style="display:block; color:#64748b; font-weight:600;">Slug:
                                    <code>{{ $plan->slug }}</code></small>
                                <label style="display:block; font-size:0.8rem; font-weight:600; margin-top:4px;">Plan
                                    name</label>
                                <input name="name" required value="{{ $plan->name }}"
                                    style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:7px; background:#fff;">
                            </div>

                            <div>
                                <label
                                    style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Monthly
                                    (₹)</label>
                                <input type="number" step="0.01" min="0" name="monthly_price" required
                                    value="{{ $plan->monthly_price }}"
                                    style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:7px; background:#fff;">
                            </div>

                            <div>
                                <label
                                    style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Yearly
                                    (₹)</label>
                                <input type="number" step="0.01" min="0" name="yearly_price" required
                                    value="{{ $plan->yearly_price }}"
                                    style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:7px; background:#fff;">
                            </div>

                            <div>
                                <label
                                    style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Trial
                                    days</label>
                                <input type="number" min="0" max="365" name="trial_days" required
                                    value="{{ $plan->trial_days }}"
                                    style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:7px; background:#fff;">
                            </div>

                            <div>
                                <label
                                    style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Sort</label>
                                <input type="number" min="0" name="sort_order"
                                    value="{{ $plan->sort_order }}"
                                    style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:7px; background:#fff;">
                            </div>

                            <div style="display:flex; align-items:center; gap:8px; padding-bottom:10px;">
                                <input type="checkbox" id="plan_active_{{ $plan->id }}" name="is_active"
                                    value="1" @checked($plan->is_active) style="width:18px; height:18px;">
                                <label for="plan_active_{{ $plan->id }}"
                                    style="font-size:0.85rem; font-weight:600;">Active</label>
                            </div>

                            <div>
                                <button type="submit" class="g-btn g-btn-secondary"
                                    style="width:100%; padding:8px;">Save changes</button>

                            </div>

                            <div style="display:flex; gap:0.5rem;">
                                <button type="submit" class="g-btn g-btn-secondary"
                                    style="flex:1; padding:8px;">Save</button>
                                <a href="{{ route('growya.entitlements.edit', $plan) }}"
                                    class="g-btn g-btn-secondary" style="padding:8px 12px;" title="Configure limits">
                                    Limits ⚙️
                                </a>
                            </div>
                        </form>
                    </div>
                @empty
                    <p style="color:#64748b;">No plans configured yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts::app>
