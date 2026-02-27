<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'KPRM' }}</title>
    <script>
        (function () {
            var theme = localStorage.getItem('theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (theme === 'dark' || ((theme === 'system' || theme === null) && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    @vite(['resources/css/app.css'])
    @livewireStyles
</head>
<body>
    {{ $slot }}
    @livewireScripts
</body>
</html>
