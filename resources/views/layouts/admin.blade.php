<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Yönetim Paneli') — {{ config('app.name', 'Başvuru 360') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <script>
        (function () {
            try {
                var themes = {
                    default: {'--sidebar-bg':'#1e1e2d','--sidebar-text-muted':'#9899ac','--sidebar-border-color':'#2b2b40','--sidebar-dropdown-content':'rgba(0,0,0,0.2)','--sidebar-menu-active':'rgba(0,0,0,0.2)','--sidebar-accent':'#3699ff','--sidebar-text-light':'#ffffff','--sidebar-hover-bg':'#2b2b40'},
                    onyx: {'--sidebar-bg':'#000000','--sidebar-text-muted':'#a0a0a0','--sidebar-border-color':'#333333','--sidebar-dropdown-content':'rgba(0,0,0,0.2)','--sidebar-menu-active':'rgba(0,0,0,0.2)','--sidebar-accent':'#ffffff','--sidebar-text-light':'#ffffff','--sidebar-hover-bg':'#1a1a1a'},
                    light: {'--sidebar-bg':'#ffffff','--sidebar-text-muted':'#5e6278','--sidebar-border-color':'#eff2f5','--sidebar-dropdown-content':'#f3f3f3','--sidebar-menu-active':'rgba(0,0,0,0.05)','--sidebar-accent':'#3699ff','--sidebar-text-light':'#3f4254','--sidebar-hover-bg':'#f5f8fa'},
                    bordo: {'--sidebar-bg':'#7d062b','--sidebar-text-muted':'#d5d5d5','--sidebar-border-color':'#630623','--sidebar-dropdown-content':'rgba(0,0,0,0.2)','--sidebar-menu-active':'rgba(0,0,0,0.2)','--sidebar-accent':'#f9fdc7','--sidebar-text-light':'#ffffff','--sidebar-hover-bg':'#630623'},
                    kucukcekmece: {'--sidebar-bg':'#23408f','--sidebar-text-muted':'#b8c6e6','--sidebar-border-color':'#1a3270','--sidebar-dropdown-content':'rgba(0,0,0,0.2)','--sidebar-menu-active':'rgba(0,0,0,0.25)','--sidebar-accent':'#ffc72c','--sidebar-text-light':'#ffffff','--sidebar-hover-bg':'#1a3270'}
                };
                var theme = themes[localStorage.getItem('sidebar_theme')] || themes.default;
                var root = document.documentElement;
                Object.keys(theme).forEach(function (key) { root.style.setProperty(key, theme[key]); });
                var font = localStorage.getItem('sidebar_font_size');
                if (font) root.style.setProperty('--menu-font-size', font + 'px');
                if (localStorage.getItem('sidebar_hidden') === '1') document.documentElement.classList.add('sidebar-pref-hidden');
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f1f5f9] font-sans text-slate-800 antialiased">
    @if (session('success') || session('error'))
        <script>
            window.__flash = {
                success: @json(session('success')),
                error: @json(session('error'))
            };
        </script>
    @endif

    <div class="flex min-h-screen">
        @include('layouts.partials.sidebar')

        <div id="adminMain" class="admin-main">
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
