<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('views.no_company.title') }}</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: #f4f4f5;
            color: #18181b;
        }
        .card {
            width: 100%;
            max-width: 28rem;
            background: #fff;
            border: 1px solid #e4e4e7;
            border-radius: 1rem;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 10px 30px -12px rgba(0, 0, 0, .15);
        }
        .icon {
            width: 4rem; height: 4rem;
            margin: 0 auto 1.5rem;
            border-radius: 9999px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(241, 120, 90, .12);
            color: #F1785A;
        }
        .icon svg { width: 2rem; height: 2rem; }
        h1 { font-size: 1.375rem; font-weight: 700; margin-bottom: .75rem; }
        p { font-size: .95rem; line-height: 1.6; color: #52525b; margin-bottom: 1.75rem; }
        button {
            font: inherit;
            cursor: pointer;
            border: 0;
            border-radius: .625rem;
            padding: .7rem 1.4rem;
            font-weight: 600;
            color: #fff;
            background: #F1785A;
            transition: opacity .15s ease;
        }
        button:hover { opacity: .9; }
        @media (prefers-color-scheme: dark) {
            body { background: #18181b; color: #fafafa; }
            .card { background: #27272a; border-color: #3f3f46; }
            p { color: #a1a1aa; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
        </div>

        <h1>{{ __('views.no_company.heading') }}</h1>
        <p>{{ __('views.no_company.body') }}</p>

        <form method="POST" action="{{ route('filament.app.auth.logout') }}">
            @csrf
            <button type="submit">{{ __('views.no_company.logout') }}</button>
        </form>
    </div>
</body>
</html>
