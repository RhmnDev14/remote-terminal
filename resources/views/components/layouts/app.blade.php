<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: localStorage.getItem('darkMode') !== 'false' }" :class="{ 'dark': darkMode }">
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

        <style>
            /* Light mode styles */
            :root {
                --bg-primary: #1f2937;
                --bg-secondary: #111827;
                --text-primary: #ffffff;
                --text-secondary: #9ca3af;
            }
            
            html:not(.dark) {
                --bg-primary: #f3f4f6;
                --bg-secondary: #ffffff;
                --text-primary: #111827;
                --text-secondary: #6b7280;
            }
            
            html:not(.dark) body {
                background-color: var(--bg-secondary);
                color: var(--text-primary);
            }
            
            html:not(.dark) .bg-gray-800 {
                background-color: #e5e7eb !important;
            }
            
            html:not(.dark) .bg-gray-900 {
                background-color: #f9fafb !important;
            }
            
            html:not(.dark) .bg-gray-700 {
                background-color: #d1d5db !important;
            }
            
            html:not(.dark) .text-gray-400 {
                color: #6b7280 !important;
            }
            
            html:not(.dark) .text-gray-600 {
                color: #4b5563 !important;
            }
            
            html:not(.dark) .text-white {
                color: #111827 !important;
            }
            
            html:not(.dark) .border-gray-600 {
                border-color: #9ca3af !important;
            }
            
            html:not(.dark) .border-gray-700 {
                border-color: #d1d5db !important;
            }
            
            html:not(.dark) .border-gray-800 {
                border-color: #e5e7eb !important;
            }
        </style>
    </head>
    <body class="bg-gray-900 text-white antialiased transition-colors duration-300">
        {{ $slot }}

        <script>
            // Alpine.js store for dark mode
            document.addEventListener('alpine:init', () => {
                Alpine.store('darkMode', {
                    on: localStorage.getItem('darkMode') !== 'false',
                    toggle() {
                        this.on = !this.on;
                        localStorage.setItem('darkMode', this.on);
                        document.documentElement.classList.toggle('dark', this.on);
                    },
                    init() {
                        document.documentElement.classList.toggle('dark', this.on);
                    }
                });
            });

            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/service-worker.js')
                    .then(reg => console.log('SW Registered!', reg))
                    .catch(err => console.log('SW Failed!', err));
            }
        </script>
    </body>
</html>
