<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#F2F2F0] font-grotesk text-[#141414] antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center p-6 md:p-10">
            <div class="w-full max-w-[400px]">
                {{-- Card with red accent bar --}}
                <div class="border border-[#141414] bg-white">
                    <div class="h-1 bg-red-600"></div>
                    <div class="p-10">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
