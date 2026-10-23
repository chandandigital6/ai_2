@auth
@once
<script type="application/json" id="growya-push-settings">{!! json_encode([
    'userId' => auth()->id(), 'configUrl' => route('growya.push.config'),
    'registerUrl' => route('growya.push.store'), 'disableUrl' => route('growya.push.disable'),
    'testUrl' => route('growya.push.test'), 'workerUrl' => route('growya.push.worker'),
    'csrf' => csrf_token(),
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
<script src="{{ asset('growya-ui/push.js') }}?v=20261023-1" defer></script>
@endonce
@endauth
