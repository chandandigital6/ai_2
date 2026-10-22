@php
    $connected = $data['accounts']->where('connection_status', 'connected');
    $canManage = $can('social.manage');
    $selectablePages = collect($data['candidates'])->filter(function ($page) use ($connected) {
        $facebookSaved = $connected->where('platform', 'facebook')->contains(fn($a) => (string)$a->external_id === (string)$page['id']);
        $igId = $page['instagram']['id'] ?? null;
        $instagramSaved = !$igId || $connected->where('platform', 'instagram')->contains(fn($a) => (string)$a->external_id === (string)$igId);
        return !$facebookSaved || !$instagramSaved;
    });
    $labels = ['draft'=>'Draft', 'queued'=>'Queued', 'processing'=>'Publishing', 'waiting'=>'Processing image', 'published'=>'Published', 'partial'=>'Partly published', 'failed'=>'Failed', 'unknown'=>'Check on platform', 'cancelled'=>'Cancelled'];
@endphp
@include('extensions.social-style')
<div class="sp-shell" data-social-workspace data-server-error="{{ $errors->first() }}">
    <section class="sp-hero">
        <div><span class="sp-eyebrow">YOUR SOCIAL CHANNELS</span><h2>One workspace. Your audience.</h2><p>Connect your accounts, choose where to post and track each publication.</p></div>
        <div class="sp-count"><strong>{{ $connected->count() }}</strong><span>accounts connected</span></div>
    </section>
    @if(session('social_notice'))<div class="sp-notice" role="status">{{ session('social_notice') }}</div>@endif
    @if($data['connectionError'] ?? null)<div class="sp-notice sp-warning" role="alert">{{ $data['connectionError'] }}</div>@endif
    <section class="sp-panel" id="social-accounts">
        <div class="sp-heading"><div><span class="sp-eyebrow">STEP 01</span><h2>Your accounts</h2><p>Only accounts marked Connected can receive posts.</p></div>
        @if($canManage)<form method="POST" action="{{ route('extensions.social.connect', $business) }}">@csrf<button class="g-btn g-btn-primary" type="submit">{{ $connected->isEmpty() ? 'Connect Facebook / Instagram' : 'Add or reconnect account' }}</button></form>@endif</div>
        <div class="sp-account-grid">
        @foreach(['facebook'=>'Facebook', 'instagram'=>'Instagram'] as $platform=>$platformName)
            @php $platformAccounts = $data['accounts']->where('platform', $platform); @endphp
            @forelse($platformAccounts as $account)
            <article class="sp-account" data-account-platform="{{ $platform }}">
                <div class="sp-row"><span class="sp-platform sp-{{ $platform }}" aria-hidden="true">{{ $platform === 'facebook' ? 'f' : '◎' }}</span><span class="sp-pill {{ $account->connection_status === 'connected' ? 'sp-ok' : 'sp-warn' }}">{{ ucfirst(str_replace('_', ' ', $account->connection_status)) }}</span></div>
                <span class="sp-eyebrow">{{ $platformName }}</span><h3>{{ $account->account_name ?: $platformName.' account' }}</h3><p class="sp-muted">Account ID: {{ $account->external_id }}</p>
                @if($account->connection_status !== 'connected')<p class="sp-help">Use Add or reconnect account to restore publishing access.</p>@endif
                @if($canManage)<form method="POST" action="{{ route('extensions.social.disconnect', [$business, $account]) }}">@csrf @method('DELETE')<button class="sp-text-button" type="submit">{{ $account->connection_status === 'disconnected' ? 'Clear connection' : 'Disconnect account' }}</button></form>@endif
            </article>
            @empty
            <article class="sp-account sp-empty-account"><span class="sp-platform sp-{{ $platform }}" aria-hidden="true">{{ $platform === 'facebook' ? 'f' : '◎' }}</span><span class="sp-eyebrow">{{ $platformName }}</span><h3>No {{ $platformName }} account connected</h3><p>{{ $platform === 'facebook' ? 'Connect and choose a Facebook Page you manage.' : 'Link a professional Instagram account to your Facebook Page, then select it below.' }}</p></article>
            @endforelse
        @endforeach
        </div>
        @if($connected->isEmpty())<p class="sp-help">Authorization alone does not save an account. After Facebook login, select a Page or Instagram account in the section below.</p>@endif
    </section>

    @if($canManage && $selectablePages->isNotEmpty())
    <section class="sp-panel sp-selection" id="social-page-selection"><div class="sp-heading"><div><span class="sp-eyebrow">FINISH CONNECTION</span><h2>Choose your accounts</h2><p>Facebook returned {{ count($data['candidates']) }} Page(s). Connect each channel you want to use. Selection expires after 10 minutes.</p></div></div>
        <div class="sp-account-grid">
        @foreach($selectablePages as $page)
            @foreach(['facebook','instagram'] as $platform)
                @php
                    $ig = $page['instagram'] ?? null;
                    $externalId = $platform === 'instagram' ? ($ig['id'] ?? null) : $page['id'];
                    $alreadyConnected = $externalId && $connected->where('platform', $platform)->contains(fn($a) => (string)$a->external_id === (string)$externalId);
                @endphp
                <article class="sp-account"><div class="sp-row"><span class="sp-platform sp-{{ $platform }}" aria-hidden="true">{{ $platform === 'facebook' ? 'f' : '◎' }}</span><span class="sp-pill {{ $alreadyConnected ? 'sp-ok' : '' }}">{{ $alreadyConnected ? 'Connected' : ucfirst($platform) }}</span></div>
                    <h3>{{ $platform === 'instagram' ? ($ig['username'] ?? ($ig ? 'Linked Instagram account' : 'Instagram not linked')) : $page['name'] }}</h3>
                    <p class="sp-muted">{{ $platform === 'instagram' ? 'Facebook Page: '.$page['name'] : 'Page ID: '.$page['id'] }}</p>
                    @if($externalId && ! $alreadyConnected)<form method="POST" action="{{ route('extensions.social.select', $business) }}">@csrf<input type="hidden" name="connection_key" value="{{ $data['connectionKey'] }}"><input type="hidden" name="page_id" value="{{ $page['id'] }}"><input type="hidden" name="platform" value="{{ $platform }}"><button class="g-btn g-btn-primary" type="submit">Connect {{ ucfirst($platform) }}</button></form>
                    @elseif(!$externalId)<p class="sp-help">Link a professional Instagram account to this Page, then reconnect.</p>@endif
                </article>
            @endforeach
        @endforeach
        </div>
    </section>
    @elseif(($data['connectionAttempted'] ?? false) && count($data['candidates']) === 0 && empty($data['connectionError']))
        <div class="sp-notice sp-warning">No available Pages were returned. Reconnect and allow access to a Facebook Page you manage.</div>
    @endif

    @if($canManage)
    <div class="sp-compose-layout">
    <section class="sp-panel"><div class="sp-heading"><div><span class="sp-eyebrow">STEP 02</span><h2>Create your next post</h2><p>Select accounts and review the preview before submitting.</p></div></div>
        @if($connected->isEmpty())<div class="sp-notice sp-warning">Connect an account above to enable posting.</div>@endif
        <form method="POST" enctype="multipart/form-data" action="{{ route('extensions.social.store', $business) }}" data-social-compose>
            @csrf<input type="hidden" name="request_key" value="{{ old('request_key', (string) \Illuminate\Support\Str::uuid()) }}">
            <fieldset @disabled($connected->isEmpty())>
                <legend class="sp-label">Publish to</legend><div class="sp-channel-list">
                @foreach($connected as $a)<label class="sp-channel"><input type="checkbox" name="account_ids[]" value="{{ $a->id }}" data-platform="{{ $a->platform }}" @checked(in_array((string)$a->id, array_map('strval', old('account_ids', [])), true))><span class="sp-platform sp-small sp-{{ $a->platform }}" aria-hidden="true">{{ $a->platform === 'facebook' ? 'f' : '◎' }}</span><span><strong>{{ $a->account_name }}</strong><small>{{ ucfirst($a->platform) }}</small></span></label>@endforeach
                </div>
                @if($data['festivals']->isNotEmpty())<label class="ex-field">Festival inspiration<select data-social-festival><option value="">Choose a festival (optional)</option>@foreach($data['festivals'] as $festival)<option value="{{ $festival->default_caption }}">{{ $festival->name }} · {{ $festival->festival_date->format('d M') }}</option>@endforeach</select></label>@endif
                <label class="ex-field">Caption<textarea name="caption" rows="5" maxlength="2200" required placeholder="What would you like to share?">{{ old('caption') }}</textarea></label><p class="sp-muted"><span data-caption-count>0</span>/2200 characters</p>
                <label class="ex-field">Post image<input type="file" name="image" accept="image/jpeg,image/png"></label><p class="sp-help">Facebook: text or one image. Instagram: one JPEG image is required. Maximum upload: 8 MB.</p>
                <div class="ex-grid"><label class="ex-field">Publish option<select name="publish_mode"><option value="draft" @selected(old('publish_mode') === 'draft')>Save draft</option><option value="now" @selected(old('publish_mode') === 'now')>Publish now</option><option value="scheduled" @selected(old('publish_mode') === 'scheduled')>Schedule for later</option></select></label><label class="ex-field" data-scheduled-field>Scheduled time · {{ config('app.timezone') }}<input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"></label></div>
                <p class="sp-form-error" data-compose-error role="alert" hidden></p><button type="submit" class="g-btn g-btn-primary" data-compose-submit>Save draft</button>
                <p class="sp-help">Publish now enters the publishing queue. The history below confirms success separately for each account.</p>
            </fieldset>
        </form>
    </section>
    <aside class="sp-panel sp-preview"><span class="sp-eyebrow">LIVE PREVIEW</span><h2>Your post at a glance</h2><div class="sp-preview-head"><span class="sp-avatar">{{ mb_strtoupper(mb_substr($business->name,0,1)) }}</span><div><strong>{{ $business->name }}</strong><small data-preview-accounts>No accounts selected</small></div></div><p class="sp-preview-caption" data-preview-caption>Your caption preview will appear here.</p><img data-preview-image alt="Selected image preview" hidden><div class="sp-preview-placeholder" data-preview-placeholder>Your image preview</div><p class="sp-help">Preview is approximate. Each platform controls the final layout.</p></aside>
    </div>

    <section class="sp-panel"><details><summary class="sp-details-title">Recurring posting <span>Set up a daily or weekly post</span></summary><p class="sp-help">Repeats the saved caption and image. This does not generate a new AI image each day.</p>
    <form method="POST" enctype="multipart/form-data" action="{{ route('extensions.social.schedule', $business) }}" data-social-recurring>@csrf<fieldset @disabled($connected->isEmpty())><div class="ex-grid"><label class="ex-field">Account<select name="social_account_id" required><option value="">Choose connected account</option>@foreach($connected as $a)<option value="{{ $a->id }}">{{ $a->account_name }} · {{ ucfirst($a->platform) }}</option>@endforeach</select></label><label class="ex-field">Frequency<select name="frequency"><option value="daily">Daily</option><option value="weekly">Weekly</option></select></label><label class="ex-field">Time<input type="time" name="run_time" required></label><label class="ex-field">Timezone<input name="timezone" value="Asia/Kolkata" required></label></div><div class="sp-weekdays" data-weekdays hidden>@foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $n=>$day)<label><input type="checkbox" name="days_of_week[]" value="{{ $n }}"> {{ $day }}</label>@endforeach</div><label class="ex-field">Caption<textarea name="caption" required maxlength="2200" rows="3"></textarea></label><label class="ex-field">Image<input name="image" type="file" accept="image/jpeg,image/png"></label><p class="sp-help">For Instagram, upload a JPEG image.</p><button type="submit" class="g-btn g-btn-primary">Start schedule</button></fieldset></form></details>
    @foreach($data['schedules'] as $s)<div class="sp-schedule"><div><strong>{{ $data['accounts']->firstWhere('id',$s->social_account_id)?->account_name ?? 'Removed account' }}</strong><p>{{ ucfirst($s->frequency) }} at {{ $s->run_time }} · {{ $s->timezone }}</p></div><span class="sp-pill {{ $s->is_active ? 'sp-ok' : '' }}">{{ $s->is_active ? 'Active' : 'Stopped' }}</span>@if($s->is_active)<form method="POST" action="{{ route('extensions.social.stop-schedule',[$business,$s]) }}">@csrf @method('DELETE')<button class="sp-text-button" type="submit">Stop schedule</button></form>@endif</div>@endforeach
    </section>
    @endif

    <section class="sp-panel" id="social-post-history" data-pending="{{ $data['posts']->contains(fn($p) => $p->overall_status === 'queued') ? 'true' : 'false' }}">
        <div class="sp-heading"><div><span class="sp-eyebrow">STEP 03</span><h2>Post history</h2><p>Queued means waiting. Published means the platform confirmed the post.</p></div><a class="g-btn g-btn-secondary" href="{{ request()->fullUrl() }}#social-post-history">Refresh status</a></div>
        <p class="sp-help" data-poll-status role="status">Pending posts refresh here automatically while this page is open.</p>
        @forelse($data['posts'] as $post)
        <article class="sp-history-item"><div class="sp-row"><strong>Post #{{ $post->id }}</strong><span class="sp-pill {{ $post->overall_status === 'published' ? 'sp-ok' : (in_array($post->overall_status,['failed','partial']) ? 'sp-warn' : '') }}">{{ $labels[$post->overall_status] ?? ucfirst($post->overall_status) }}</span><time>{{ $post->created_at?->format('d M Y, h:i A') }}</time></div><p class="sp-preview-caption">{{ $post->caption }}</p>
            @foreach($post->social_post_platforms as $target)
            @php $targetAccount = $target->social_account; @endphp
            <div class="sp-result"><div><strong>{{ $targetAccount?->account_name ?? 'Removed account' }}</strong><small>{{ ucfirst($targetAccount?->platform ?? 'Account') }} · {{ $labels[$target->status] ?? ucfirst($target->status) }}</small>@if($target->error)<p class="sp-error-text">{{ $target->error }}</p>@endif</div>
            @if($target->status === 'published' && $target->external_post_url && str_starts_with($target->external_post_url,'https://www.facebook.com/'))<a class="sp-text-button" href="{{ $target->external_post_url }}" target="_blank" rel="noopener noreferrer">View published post ↗</a>@elseif($target->external_post_id)<span class="sp-muted">Post ID: {{ $target->external_post_id }}</span>@endif</div>
            @endforeach
            @if($canManage)<div class="sp-row">@if(in_array($post->overall_status,['draft','cancelled']))<form method="POST" action="{{ route('extensions.social.publish',[$business,$post]) }}">@csrf<button type="submit" class="g-btn g-btn-primary">Publish now</button></form>@elseif($post->overall_status === 'queued')<form method="POST" action="{{ route('extensions.social.cancel',[$business,$post]) }}">@csrf<button type="submit" class="g-btn g-btn-secondary">Cancel queued post</button></form>@endif</div>@endif
        </article>
        @empty<div class="sp-empty-history"><h3>Your first post starts here</h3><p>Connect an account, write a caption and save a draft or publish.</p></div>@endforelse
        {{ $data['posts']->links() }}
        @if($data['posts']->contains(fn($p) => $p->overall_status === 'queued'))<p class="sp-help">Stuck in Queued? The server needs the social queue worker and the minute scheduler running.</p>@endif
    </section>
    <div class="sp-toast" role="alert" data-social-toast hidden></div>
</div>
@include('extensions.social-script')
