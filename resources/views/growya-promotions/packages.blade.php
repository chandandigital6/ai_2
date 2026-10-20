<x-layouts::app title="Promotion Packages">
    <div class="g-page">
        <div class="g-page-heading" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
            <div>
                <p class="g-kicker" style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin:0;">Administration / Promotions</p>
                <h1 style="font-size:1.75rem; font-weight:700; margin:0.25rem 0;">Paid promotion packages</h1>
                <p style="color:#64748b; margin:0;">Create and manage ad packages for businesses and products.</p>
            </div>
            @if (Route::has('growya.promotions.admin.index'))
                <a class="g-btn g-btn-secondary" href="{{ route('growya.promotions.admin.index') }}">Review requests</a>
            @endif
        </div>

        @if (session('success'))
            <div class="g-alert g-tone-green" style="padding:12px 16px; border-radius:8px; margin-bottom:1.25rem; background:#dcfce7; color:#166534;">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="g-alert" style="padding:12px 16px; border-radius:8px; margin-bottom:1.25rem; background:#fee2e2; color:#991b1b;">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Create Package Panel --}}
        <section class="g-panel" style="padding:1.5rem; border-radius:12px; background:var(--g-panel-bg, #fff); border:1px solid var(--g-border, #e2e8f0); margin-bottom:2rem;">
            <h2 style="font-size:1.15rem; font-weight:700; margin:0 0 1rem; color:#0f172a;">Create new package</h2>
            <form method="POST" action="{{ route('growya.promotions.packages.store') }}" style="display:grid; gap:1rem; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); align-items:flex-end;">
                @csrf
                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Package name</label>
                    <input name="name" placeholder="e.g. Homepage Spotlight" required maxlength="120" style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px;">
                </div>

                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Target type</label>
                    <select name="target_type" style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px; background:#fff;">
                        <option value="business">Business</option>
                        <option value="product">Product</option>
                    </select>
                </div>

                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Duration (Days)</label>
                    <input type="number" name="duration_days" min="1" max="365" placeholder="e.g. 7" required style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px;">
                </div>

                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Price (₹)</label>
                    <input name="price" type="number" step="0.01" min="0" placeholder="e.g. 499.00" required style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px;">
                </div>

                <div style="display:flex; align-items:center; gap:8px; padding-bottom:10px;">
                    <input type="checkbox" id="new_pkg_active" name="is_active" value="1" checked style="width:18px; height:18px;">
                    <label for="new_pkg_active" style="font-size:0.85rem; font-weight:600;">Active</label>
                </div>

                <div>
                    <button type="submit" class="g-btn g-btn-primary" style="width:100%; padding:9px;">Create package</button>
                </div>
            </form>
        </section>

        {{-- Existing Packages List --}}
        <section class="g-panel" style="padding:1.5rem; border-radius:12px; background:var(--g-panel-bg, #fff); border:1px solid var(--g-border, #e2e8f0);">
            <h2 style="font-size:1.15rem; font-weight:700; margin:0 0 1rem; color:#0f172a;">Existing packages</h2>
            <div style="display:flex; flex-direction:column; gap:1rem;">
                @forelse ($packages as $p)
                    <div style="border:1px solid #e2e8f0; border-radius:10px; padding:1.25rem; background:#f8fafc;">
                        <form method="POST" action="{{ route('growya.promotions.packages.update', $p) }}" style="display:grid; gap:1rem; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); align-items:flex-end;">
                            @csrf
                            @method('PATCH')

                            <div>
                                <span style="font-size:0.7rem; font-weight:700; text-transform:uppercase; padding:2px 6px; border-radius:4px; {{ $p->target_type === 'product' ? 'background:#eff6ff; color:#1d4ed8;' : 'background:#f3e8ff; color:#6b21a8;' }}">
                                    {{ $p->target_type }} package
                                </span>
                                <label style="display:block; font-size:0.8rem; font-weight:600; margin-top:6px; margin-bottom:4px;">Package name</label>
                                <input name="name" value="{{ $p->name }}" required style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:7px; background:#fff;">
                            </div>

                            <div>
                                <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Duration (Days)</label>
                                <input type="number" name="duration_days" value="{{ $p->duration_days }}" min="1" max="365" required style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:7px; background:#fff;">
                            </div>

                            <div>
                                <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Price (₹)</label>
                                <input name="price" type="number" step="0.01" min="0" value="{{ $p->price }}" required style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:7px; background:#fff;">
                            </div>

                            <div style="display:flex; align-items:center; gap:8px; padding-bottom:10px;">
                                <input type="checkbox" id="pkg_active_{{ $p->id }}" name="is_active" value="1" @checked($p->is_active) style="width:18px; height:18px;">
                                <label for="pkg_active_{{ $p->id }}" style="font-size:0.85rem; font-weight:600;">Active</label>
                            </div>

                            <div>
                                <button type="submit" class="g-btn g-btn-secondary" style="width:100%; padding:8px;">Save changes</button>
                            </div>
                        </form>
                    </div>
                @empty
                    <p style="color:#64748b; text-align:center; padding:1.5rem 0;">No promotion packages created yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts::app>
