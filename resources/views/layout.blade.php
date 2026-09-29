<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sketchline — Draw together, live' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: '#171512',
                        chalk: '#F1ECE2',
                        clay: '#C4694B',
                        slate: '#5C6B73',
                    },
                    fontFamily: {
                        display: ['Georgia', 'ui-serif', 'serif'],
                    },
                },
            },
        };
    </script>
</head>

<body class="min-h-screen bg-ink text-chalk">@if(session('message'))
    <div class="fixed top-4 left-1/2 -translate-x-1/2 z-50 bg-clay text-ink px-4 py-2 rounded-lg shadow-lg">
        {{ session('message') }}
</div>@endif @yield('body')
@yield('scripts')
</body>

</html>