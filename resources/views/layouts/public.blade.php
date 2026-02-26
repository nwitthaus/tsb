<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <script>document.documentElement.classList.remove('dark')</script>
    </head>
    <body class="min-h-screen bg-white antialiased">
        {{ $slot }}
        @fluxScripts
    </body>
</html>
