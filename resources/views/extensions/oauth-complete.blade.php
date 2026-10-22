<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Social connection</title>
</head>

<body style="font-family:system-ui;background:#f1f5f9;padding:8%;color:#173b36">
    <main style="max-width:650px;margin:auto;background:white;border-radius:24px;padding:36px">
        <h1>{{ $error ? 'Connection needs attention' : 'Authorization received' }}</h1>
        <p>{{ $error ?: 'Return to Growya and choose the Facebook Page or linked Instagram business account to connect.' }}
        </p>
        <p>{{ $business->name }}</p>@auth<a href="{{ route('extensions.workspace', [$business, 'social']) }}">Return to
            social workspace</a>@endauth
    </main>
</body>

</html>
