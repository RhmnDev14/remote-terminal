<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Remote Terminal' }}</title>
        
        <!-- PWA Manifest & Meta -->
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#111827">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <link rel="apple-touch-icon" href="https://via.placeholder.com/192x192.png?text=RT">

        <!-- xterm.js CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@xterm/xterm@5.5.0/css/xterm.css">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Firebase -->
        @vite(['resources/js/firebase.js'])
        
        <!-- Interactive Terminal -->
        @vite(['resources/js/interactive-terminal.js'])
    </head>
    <body class="bg-gray-900 text-white antialiased">
        {{ $slot }}

        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/service-worker.js')
                    .then(reg => console.log('SW Registered!', reg))
                    .catch(err => console.log('SW Failed!', err));
            }
        </script>
    </body>
</html>
