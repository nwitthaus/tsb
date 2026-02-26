<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-neutral-950">
        <div class="mx-auto max-w-4xl px-4 py-8">
            {{ $slot }}
        </div>
        @fluxScripts
    </body>
</html>
