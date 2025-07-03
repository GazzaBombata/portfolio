<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Pezzazze' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <link rel="icon" href="{{ asset('assets/favicon.jpg') }}" type="image/x-icon">

</head>
<body class="bg-gray-100 text-gray-900 overflow-x-hidden">
<header class="absolute inset-x-0 top-0 z-10 overflow-x-hidden">
    <nav class="flex items-center justify-between p-6 lg:px-8" aria-label="Global">
        <div class="flex lg:flex-1">
            <a href="/" class="-m-1.5 p-1.5">
                <span class="sr-only">Giorgio Giotto Full Stack Developer</span>
                <img class="h-8 w-auto rounded-full" src="{{ asset('assets/favicon.jpg') }}"
                     alt="Giorgio Giotto Full Stack Developer Portfolio">
            </a>
        </div>
        <div class="flex lg:hidden">
            <button type="button"
                    class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-700 toggle-button">
                <span class="sr-only">Apri il menù</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                     aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
        </div>
        <div class="hidden lg:flex lg:gap-x-12">
            <a href="/" class="text-sm font-semibold leading-6 text-gray-900 hover:text-indigo-500">Homepage</a>

        </div>
        <div class="hidden lg:flex lg:flex-1 lg:justify-end">
            <a href="#" class="text-sm font-semibold leading-6 text-gray-900 hover:text-indigo-500">Log in <span
                    aria-hidden="true">&rarr;</span></a>
        </div>
    </nav>
    <!-- Mobile menu, show/hide based on menu open state. -->
    <div class="lg:hidden" role="dialog" aria-modal="true" id="mobile-menu">
        <!-- Background backdrop, show/hide based on slide-over state. -->
        <div class="fixed inset-0 z-50"></div>
        <div
            class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto overflow-x-hidden bg-white px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10">
            <div class="flex items-center justify-between">
                <a href="/" class="-m-1.5 p-1.5">
                    <span class="sr-only">Giorgio Giotto - Full Stack Developer Logo</span>
                    <img class="h-8 w-auto"
                         src="{{ asset('assets/favicon.jpg') }}"
                         alt="Giorgio Giotto - Full Stack Developer">
                </a>
                <button type="button" class="-m-2.5 rounded-md p-2.5 text-gray-700 toggle-button">
                    <span class="sr-only">Chiudi menù</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                         aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="mt-6 flow-root">
                <div class="-my-6 divide-y divide-gray-500/10">
                    <div class="space-y-2 py-6">
                        <a href="#projects"
                           class="toggle-button -mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Projects</a>
                        <a href="#work-experience"
                           class="toggle-button -mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Work Experience</a>
                        <a href="#skills"
                           class="toggle-button -mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Skills</a>
                    </div>
                    <!-- <div class="py-6">
                      <a href="#"
                        class="-mx-3 block rounded-lg px-3 py-2.5 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Log
                        in</a>
                    </div> -->
                </div>
            </div>
        </div>
    </div>
</header>

{{ $slot }}
</body>


@livewireScripts
<script>
    const buttons = document.getElementsByClassName('toggle-button')
    const menu = document.getElementById('mobile-menu')
    menu.style.display = "none";
    for (let i = 0; i < buttons.length; i++) {
        buttons[i].addEventListener('click', function () {
            if (menu.style.display === "none") {
                menu.style.display = "block";
            } else {
                menu.style.display = "none";
            };
        });
    };
</script>
</body>
</html>
