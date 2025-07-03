<!DOCTYPE html>
<html lang="it">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Giorgio Giotto, Full Stack Developer Portfolio</title>
  <!-- <script src="https://cdn.tailwindcss.com"></script> -->
  @vite('resources/css/app.css')
  <link rel="icon" href="{{ asset('assets/favicon.jpg') }}" type="image/x-icon">
   <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

</head>

<body class="px-5 md:px-auto">
  <div class="bg-white">
    <header class="absolute inset-x-0 top-0 z-10">
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
          <a href="#projects" class="text-sm font-semibold leading-6 text-gray-900 hover:text-indigo-500">Projects</a>
          <a href="#work-experience" class="text-sm font-semibold leading-6 text-gray-900 hover:text-indigo-500">Work Experience</a>
          <a href="#skills" class="text-sm font-semibold leading-6 text-gray-900 hover:text-indigo-500">Skills</a>
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
          class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10">
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

    <div class="relative isolate pt-16">
      <div class="relative px-8 lg:px-12">
        <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80"
          aria-hidden="true">
          <div
            class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-30 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"
            style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)">
          </div>
        </div>
        <div class="mx-auto max-w-7xl py-32 sm:py-48 lg:py-56">
          <div class="flex flex-col sm:flex-row">
            <div class="flex justify-center relative h-auto w-100% sm:w-[50%]">
              <dotlottie-player src="https://lottie.host/ae76dc3b-f344-4a3a-a083-8f3a35db74ca/9GNXYvqLOJ.json"
                background="transparent" speed="1" style="position: absolute; z-index: -1;" loop
                autoplay></dotlottie-player>
              <div class="flex justify-center lg:flex-1">
                <span class="sr-only">Giorgio Giotto IT Project Manager</span>
                <img class=" rounded-full" src="{{ asset('assets/Giorgio-Giotto-profile-foto-nobackground.png') }}"
                  alt="Giorgio Giotto Full Stack Developer Portfolio">
              </div>
            </div>
            <div class="w-100% sm:w-[50%] flex flex-col items-center justify-center">
              <div class="hidden sm:mb-8 sm:flex sm:justify-center">
                <div
                  class="relative rounded-full px-3 py-1 text-sm leading-6 text-gray-600 ring-1 ring-gray-900/10 hover:ring-gray-900/20">
                  Looking for the CV? <a href="https://www.linkedin.com/in/giorgiogiotto/" class="font-semibold text-indigo-600"><span class="absolute inset-0"
                      aria-hidden="true"></span>Download on Linkedin <span aria-hidden="true">&rarr;</span></a>
                </div>
              </div>
              <div class="text-center gap-5px py-10">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl">Hi, I'm Giorgio!
                </h1>
                <h2 class="hidden">A full Stack Developer based in Italy.</h2>
                <p class="mt-6 text-lg leading-8 text-gray-600">An Ops manager, entrepreneur and intrapreneur turned
                  IT Project Manager. Sounds extravagant? Contact me and find it out.</p>
                <div class="mt-10 flex items-center justify-center gap-x-6">
                  <a href="#" class="hidden rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2
                    focus-visible:outline-offset-2 focus-visible:outline-indigo-600" type="button"
                    data-drawer-target="drawer-form" data-drawer-show="drawer-form" aria-controls="drawer-form">Contact
                    me</a>

                  <!-- Linkedin -->
                  <a href="https://www.linkedin.com/in/giorgiogiotto/">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hover:text-indigo-500" fill="currentColor"
                      viewBox="0 0 24 24">
                      <path
                        d="M4.98 3.5c0 1.381-1.11 2.5-2.48 2.5s-2.48-1.119-2.48-2.5c0-1.38 1.11-2.5 2.48-2.5s2.48 1.12 2.48 2.5zm.02 4.5h-5v16h5v-16zm7.982 0h-4.968v16h4.969v-8.399c0-4.67 6.029-5.052 6.029 0v8.399h4.988v-10.131c0-7.88-8.922-7.593-11.018-3.714v-2.155z" />
                    </svg>
                  </a>


                  <!-- Github -->
                  <a href="https://github.com/GazzaBombata">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hover:text-indigo-500" fill="currentColor"
                      viewBox="0 0 24 24">
                      <path
                        d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
                    </svg>
                  </a>


                </div>
              </div>
            </div>
          </div>
        </div>
        <div
          class="absolute inset-x-0 top-[calc(100%-13rem)] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[calc(100%-30rem)]"
          aria-hidden="true">
          <div
            class="relative left-[calc(50%+3rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-30 sm:left-[calc(50%+36rem)] sm:w-[72.1875rem]"
            style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)">
          </div>
        </div>
        <hr class="h-px my-8 bg-gray-200 border-0 drk:bg-gray-700">
      </div>
    </div>
  </div>


  <div class="flex flex-col items-center justify-items-center space-y-10 py-10" id="projects">
    <h2 class="text-4xl font-bold drk:text-white">Web Development Projects</h2>
    <div class="flex flex-col md:flex-row items-center justify-items-center md:space-x-10 py-10 max-h-max">
      <div class="max-w-sm bg-white border border-gray-200 rounded-lg shadow drk:bg-gray-800 drk:border-gray-700">
          <a href="https://medbooks.io" class="flex flex-col items-center">
              <img class="rounded-t-lg h-56" src="{{asset('assets/medbooks-logo.png')}}" alt="Medbooks Logo" />
          </a>
          <div class="p-5">
              <a href="https://medbooks.io">
                  <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 drk:text-white">Medbooks</h5>
              </a>
              <p class="mb-3 font-normal text-gray-700 drk:text-gray-400">Appointments scheduling SaaS for healthcare professionals and clinics, built with Bubble and Javascript.</p>
              <a href="https://medbooks.io" class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 drk:bg-blue-600 drk:hover:bg-blue-700 drk:focus:ring-blue-800">
                  Visit
                  <svg class="rtl:rotate-180 w-3.5 h-3.5 ms-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                  </svg>
              </a>
          </div>
      </div>
      <div class="max-w-sm bg-white border border-gray-200 rounded-lg shadow drk:bg-gray-800 drk:border-gray-700 ">
          <a href="#" class="flex flex-col items-center">
              <img class="rounded-t-lg h-56" src="https://logowik.com/content/uploads/images/546_rotarymbs_pms_c.jpg" alt="Rotary Logo" />
          </a>
          <div class="p-5">
              <a href="#">
                  <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 drk:text-white">Clubberly</h5>
              </a>
              <p class="mb-3 font-normal text-gray-700 drk:text-gray-400">Tool used to manage Rotary Clubs, including emails, events and registrations. Built with Laravel, Livewire, FilamentPHP MySQL and Blade</p>
              <a href="https://clubberly.com" class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white text-white bg-blue-700 rounded focus:outline-none rounded-lg">
                                    Visit
                  <svg class="rtl:rotate-180 w-3.5 h-3.5 ms-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                  </svg>
              </a>
          </div>
      </div>
      <div class="max-w-sm bg-white border border-gray-200 rounded-lg shadow drk:bg-gray-800 drk:border-gray-700">
          <a href="https://integral-hold-411309.oa.r.appspot.com" class="flex flex-col items-center">
              <img class="rounded-t-lg h-56" src="{{asset('assets/tablebooks-screenshot.png')}}" alt="Tablebooks" />
          </a>
          <div class="p-5">
              <a href="https://integral-hold-411309.oa.r.appspot.com">
                  <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 drk:text-white">Tablebooks</h5>
              </a>
              <p class="mb-3 font-normal text-gray-700 drk:text-gray-400">Restaurant table reservations SaaS. Start2Impact final project. Built with NodeJS, MySQL and React, hosted on GCP.</p>
              <a href="#" class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white bg-gray-300 rounded-lg" >
                  Deprecated
                  <svg class="rtl:rotate-180 w-3.5 h-3.5 ms-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                  </svg>
              </a>
          </div>
      </div>
    </div>
  </div>




  <div class="flex flex-col items-center justify-items-center space-y-10 py-10" id="work-experience">
    <h2 class="text-4xl font-bold drk:text-white">Work Experience</h2>
    <ol class="relative border-s border-gray-200 drk:border-gray-700">
      <li class="mb-10 ms-6">
        <span
          class="absolute flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full -start-3 ring-8 ring-white drk:ring-gray-900 drk:bg-blue-900">
          <svg class="w-2.5 h-2.5 text-blue-800 drk:text-blue-300" aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path
              d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z" />
          </svg>
        </span>
        <h3 class="flex items-center mb-1 text-lg font-semibold text-gray-900 drk:text-white">IT Project Manager Freelance<span
            class="bg-blue-100 text-blue-800 text-sm font-medium me-2 px-2.5 py-0.5 rounded drk:bg-blue-900 drk:text-blue-300 ms-3">Current</span>
        </h3>
        <time class="block mb-2 text-sm font-normal leading-none text-gray-400 drk:text-gray-500">Started on February
          2024</time>
        <p class="mb-4 text-base font-normal text-gray-500 drk:text-gray-400">Leveraging Software to bring Business Operations to the next level.</p>
        <a href="#"
          class="hidden inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-gray-100 focus:text-blue-700 drk:bg-gray-800 drk:text-gray-400 drk:border-gray-600 drk:hover:text-white drk:hover:bg-gray-700 drk:focus:ring-gray-700">
          Visit Website
          <svg class="rtl:rotate-180 w-3.5 h-3.5 ms-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
          </svg>
        </a>
      </li>
      <li class="mb-10 ms-6">
        <span
          class="absolute flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full -start-3 ring-8 ring-white drk:ring-gray-900 drk:bg-blue-900">
          <svg class="w-2.5 h-2.5 text-blue-800 drk:text-blue-300" aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path
              d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z" />
          </svg>
        </span>
        <h3 class="flex items-center mb-1 text-lg font-semibold text-gray-900 drk:text-white">IT Project Manager - Fedespedi<span
            class="bg-blue-100 text-blue-800 text-sm font-medium me-2 px-2.5 py-0.5 rounded drk:bg-blue-900 drk:text-blue-300 ms-3">Current</span>
        </h3>
        <time class="block mb-2 text-sm font-normal leading-none text-gray-400 drk:text-gray-500">Started on April
          2024</time>
        <p class="mb-4 text-base font-normal text-gray-500 drk:text-gray-400">Cooperating with the Federation and its associates in managing Digital Projects</p>
        <a href="https://fedespedi.it"
          class="hidden inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-gray-100 focus:text-blue-700 drk:bg-gray-800 drk:text-gray-400 drk:border-gray-600 drk:hover:text-white drk:hover:bg-gray-700 drk:focus:ring-gray-700">
          Visit Website
          <svg class="rtl:rotate-180 w-3.5 h-3.5 ms-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
          </svg>
        </a>
      </li>
      <li class="mb-10 ms-6">
        <span
          class="absolute flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full -start-3 ring-8 ring-white drk:ring-gray-900 drk:bg-blue-900">
          <svg class="w-2.5 h-2.5 text-blue-800 drk:text-blue-300" aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path
              d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z" />
          </svg>
        </span>
        <h3 class="mb-1 text-lg font-semibold text-gray-900 drk:text-white">Operations and Tech - Quisto</h3>
        <time class="block mb-2 text-sm font-normal leading-none text-gray-400 drk:text-gray-500">From May 2023 to January 2024</time>
        <p class="mb-4 text-base font-normal text-gray-500 drk:text-gray-400">Operations Manager and builder of internal tech tools.</p>
        <a href="https://www.linkedin.com/company/wearequisto/"
          class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-gray-100 focus:text-blue-700 drk:bg-gray-800 drk:text-gray-400 drk:border-gray-600 drk:hover:text-white drk:hover:bg-gray-700 drk:focus:ring-gray-700">
          Visit LinkedIn page
          <svg class="rtl:rotate-180 w-3.5 h-3.5 ms-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
          </svg>
        </a>
      </li>
      <li class="mb-10 ms-6">
        <span
          class="absolute flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full -start-3 ring-8 ring-white drk:ring-gray-900 drk:bg-blue-900">
          <svg class="w-2.5 h-2.5 text-blue-800 drk:text-blue-300" aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path
              d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z" />
          </svg>
        </span>
        <h3 class="mb-1 text-lg font-semibold text-gray-900 drk:text-white">Founder - Gluebus</h3>
        <time class="block mb-2 text-sm font-normal leading-none text-gray-400 drk:text-gray-500">From February 2022 to May 2023</time>
        <p class="text-base font-normal text-gray-500 drk:text-gray-400">Smart Bus Platform for local night events, part of StartupBootcamp Amsterdam 2022 batch.</p>
      </li>
      <li class="mb-10 ms-6">
        <span
          class="absolute flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full -start-3 ring-8 ring-white drk:ring-gray-900 drk:bg-blue-900">
          <svg class="w-2.5 h-2.5 text-blue-800 drk:text-blue-300" aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path
              d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z" />
          </svg>
        </span>
        <h3 class="mb-1 text-lg font-semibold text-gray-900 drk:text-white">Shopper Support Lead - Everli</h3>
        <time class="block mb-2 text-sm font-normal leading-none text-gray-400 drk:text-gray-500">From July 2020 to September 2022</time>
        <p class="mb-4 text-base font-normal text-gray-500 drk:text-gray-400">Responsible for a team of 15+ people, managing italian problematic orders for Everli (e-grocery delivery business) </p>
        <a href="https://www.linkedin.com/company/everli-formerly-supermercato24/"
          class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-gray-100 focus:text-blue-700 drk:bg-gray-800 drk:text-gray-400 drk:border-gray-600 drk:hover:text-white drk:hover:bg-gray-700 drk:focus:ring-gray-700">
          Visit LinkedIn page
          <svg class="rtl:rotate-180 w-3.5 h-3.5 ms-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
          </svg>
        </a>
      </li>
      <li class="mb-10 ms-6">
        <span
          class="absolute flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full -start-3 ring-8 ring-white drk:ring-gray-900 drk:bg-blue-900">
          <svg class="w-2.5 h-2.5 text-blue-800 drk:text-blue-300" aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path
              d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z" />
          </svg>
        </span>
        <h3 class="mb-1 text-lg font-semibold text-gray-900 drk:text-white">Area Manager - Everli</h3>
        <time class="block mb-2 text-sm font-normal leading-none text-gray-400 drk:text-gray-500">From April 2019 to July 2020</time>
        <p class="text-base font-normal text-gray-500 drk:text-gray-400">Area Management of Brescia, Verona, Mantova and Cremona areas. Responsible for quality of deliveries and shopper supply.</p>
      </li>
      <li class=" ms-6">
        <span
          class="absolute flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full -start-3 ring-8 ring-white drk:ring-gray-900 drk:bg-blue-900">
          <svg class="w-2.5 h-2.5 text-blue-800 drk:text-blue-300" aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path
              d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z" />
          </svg>
        </span>
        <h3 class="mb-1 text-lg font-semibold text-gray-900 drk:text-white">Business Processes Intern - Esselunga</h3>
        <time class="block mb-2 text-sm font-normal leading-none text-gray-400 drk:text-gray-500">From January 2018 to July 2018</time>
        <p class="text-base font-normal text-gray-500 drk:text-gray-400">Responsible for mapping and optimization of logistic processes and quality checks.</p>
      </li>
    </ol>
  </div>



  <div class="flex flex-col items-center justify-items-center space-y-10 py-10" id="skills" x-data="{ tab: 'languages' }">
  <h2 class="text-4xl font-bold drk:text-white">Skills</h2>
  <div class="w-full md:w-5/6 flex flex-col items-center justify-items-center">
  <div class="w-full bg-white border border-gray-200 rounded-lg shadow drk:bg-gray-800 drk:border-gray-700">
    <div class="sm:hidden">

        <label for="tabs" class="sr-only">Select tab</label>
        <select id="tabs" @change="tab = $event.target.value" class="bg-gray-50 border-0 border-b border-gray-200 text-gray-900 text-sm rounded-t-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 drk:bg-gray-700 drk:border-gray-600 drk:placeholder-gray-400 drk:text-white drk:focus:ring-blue-500 drk:focus:border-blue-500">
            <option value="languages">Languages</option>
            <option value="frameworks">Frameworks</option>
            <option value="tools">Tools</option>
        </select>
    </div>

    <ul class="hidden text-sm w-full font-medium text-center text-gray-500 divide-x divide-gray-200 rounded-lg sm:flex drk:divide-gray-600 drk:text-gray-400 rtl:divide-x-reverse" id="fullWidthTab" role="tablist">
      <li class="w-full">
          <button @click="tab = 'languages'" :aria-selected="tab === 'languages'" id="languages-tab" type="button" role="tab" aria-controls="languages" class="inline-block w-full p-4 rounded-ss-lg bg-gray-50 hover:bg-gray-100 focus:outline-none drk:bg-gray-700 drk:hover:bg-gray-600">Languages</button>
      </li>
      <li class="w-full">
          <button @click="tab = 'frameworks'" :aria-selected="tab === 'frameworks'" id="frameworks-tab" type="button" role="tab" aria-controls="frameworks" class="inline-block w-full p-4 bg-gray-50 hover:bg-gray-100 focus:outline-none drk:bg-gray-700 drk:hover:bg-gray-600">Frameworks</button>
      </li>
      <li class="w-full">
          <button @click="tab = 'tools'" :aria-selected="tab === 'tools'" id="tools-tab" type="button" role="tab" aria-controls="tools" class="inline-block w-full p-4 rounded-se-lg bg-gray-50 hover:bg-gray-100 focus:outline-none drk:bg-gray-700 drk:hover:bg-gray-600">Tools</button>
      </li>
    </ul>
    <div id="fullWidthTabContent" class="border-t border-gray-200 drk:border-gray-600 w-full">
        <div x-show="tab === 'languages'" class="p-4 bg-white rounded-lg md:p-8 drk:bg-gray-800" id="languages" role="tabpanel" aria-labelledby="languages-tab">
            <dl class="grid max-w-screen-xl grid-cols-2 gap-8 p-4 mx-auto text-gray-900 sm:grid-cols-3 xl:grid-cols-5 drk:text-white sm:p-8">
                <div class="flex flex-col items-center justify-center">
                    <img class="w-16 h-16" src="{{asset('assets/html-1.svg')}}" alt="HTML5 Logo" />
                    <dd class="text-gray-500 drk:text-gray-400">HTML5</dd>
                </div>
                <div class="flex flex-col items-center justify-center">
                <img class="w-16 h-16" src="{{asset('assets/css-3.svg')}}" alt="CSS Logo" />
                    <dd class="text-gray-500 drk:text-gray-400">CSS</dd>
                </div>
                <div class="flex flex-col items-center justify-center">
                <img class="w-16 h-16" src="{{asset('assets/logo-javascript.svg')}}" alt="Javascript Logo" />
                    <dd class="text-gray-500 drk:text-gray-400">Javascript</dd>
                </div>
                <div class="flex flex-col items-center justify-center">
                <img class="w-16 h-16" src="{{asset('assets/php-1.svg')}}" alt="PHP Logo" />
                    <dd class="text-gray-500 drk:text-gray-400">PHP</dd>
                </div>
                <div class="flex flex-col items-center justify-center">
                <img class="w-16 h-16" src="{{asset('assets/mysql-6.svg')}}" alt="SQL Logo" />
                    <dd class="text-gray-500 drk:text-gray-400">SQL</dd>
                </div>
            </dl>
        </div>
        <div x-show="tab === 'frameworks'" class="p-4 bg-white rounded-lg md:p-8 drk:bg-gray-800 w-full" id="frameworks" role="tabpanel" aria-labelledby="frameworks-tab">
        <dl class="grid max-w-screen-xl grid-cols-2 gap-8 p-4 mx-auto text-gray-900 sm:grid-cols-3 xl:grid-cols-4 drk:text-white sm:p-8">
                <div class="flex flex-col items-center justify-center">
                    <img class="w-16 h-16" src="{{asset('assets/react-2.svg')}}" alt="HTML5 Logo" />
                    <dd class="text-gray-500 drk:text-gray-400">React</dd>
                </div>
                <div class="flex flex-col items-center justify-center">
                <img class="w-16 h-16" src="{{asset('assets/nodejs-2.svg')}}" alt="CSS Logo" />
                    <dd class="text-gray-500 drk:text-gray-400">NodeJS</dd>
                </div>
                <div class="flex flex-col items-center justify-center">
                <img class="w-16 h-16" src="{{asset('assets/laravel-2.svg')}}" alt="Javascript Logo" />
                    <dd class="text-gray-500 drk:text-gray-400">Laravel</dd>
                </div>
                <div class="flex flex-col items-center justify-center">
                <img class="w-16 h-16" src="{{asset('assets/blade-ui-kit.svg')}}" alt="Javascript Logo" />
                    <dd class="text-gray-500 drk:text-gray-400">Blade</dd>
                </div>
            </dl>
        </div>
        <div x-show="tab === 'tools'" class="p-4 bg-white rounded-lg drk:bg-gray-800 w-full md:p-8" id="tools" role="tabpanel" aria-labelledby="tools-tab">
        <dl class="grid max-w-screen-xl grid-cols-2 gap-8 p-4 mx-auto text-gray-900 sm:grid-cols-3 xl:grid-cols-5 drk:text-white sm:p-8">
                <div class="flex flex-col items-center justify-center">
                    <img class="w-16 h-16" src="{{asset('assets/git-icon.svg')}}" alt="HTML5 Logo" />
                    <dd class="text-gray-500 drk:text-gray-400">Git</dd>
                </div>
                <div class="flex flex-col items-center justify-center">
                <img class="w-16 h-16" src="{{asset('assets/tailwind-css-2.svg')}}" alt="CSS Logo" />
                    <dd class="text-gray-500 drk:text-gray-400">TailwindCSS</dd>
                </div>
                <div class="flex flex-col items-center justify-center">
                <img class="w-16 h-16" src="{{asset('assets/vitejs.svg')}}" alt="Javascript Logo" />
                    <dd class="text-gray-500 drk:text-gray-400">Vite</dd>
                </div>
                <div class="flex flex-col items-center justify-center">
                <img class="w-16 h-16" src="{{asset('assets/google-cloud-1.svg')}}" alt="PHP Logo" />
                    <dd class="text-gray-500 drk:text-gray-400">GCP</dd>
                </div>
                <div class="flex flex-col items-center justify-center">
                <img class="w-16 h-16" src="{{asset('assets/mysql-logo.svg')}}" alt="SQL Logo" />
                    <dd class="text-gray-500 drk:text-gray-400">MySQL</dd>
                </div>
            </dl>
            </div>
        </div>
    </div>
</div>
</div>



  <hr class="h-px my-8 bg-gray-200 border-0 drk:bg-gray-700 max-w-7xl">


  <div class="hidden md:flex flex-col items-center justify-items-center py-10">
    <div class="max-w-5xl flex flex-col items-center justify-items-center max-h-3xl">
    <h2 class="text-2xl md:text-4xl font-bold drk:text-white max-w-4xl text-center -mb-20">Wait... <br/>Before you leave, are you the quickest mouse in the web? Make the spider fall!</h2>
    <canvas id="web"></canvas>
    </div>
  </div>

  <div class="py-10"></div>


  <!-- drawer component -->
  <div id="drawer-form"
    class="z-10000 fixed top-0 left-0 z-40 h-screen p-4 overflow-y-auto transition-transform -translate-x-full bg-white w-80 drk:bg-gray-800"
    tabindex="-1" aria-labelledby="drawer-form-label">
    <h5 id="drawer-label"
      class="inline-flex items-center mb-6 text-base font-semibold text-gray-500 uppercase drk:text-gray-400"><svg
        class="w-3.5 h-3.5 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
        viewBox="0 0 20 20">
        <path
          d="M0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm14-7.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1Zm0 4a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1Zm-5-4a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1Zm0 4a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1Zm-5-4a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1Zm0 4a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1ZM20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4Z" />
      </svg>Contact Form</h5>
    <button type="button" data-drawer-hide="drawer-form" aria-controls="drawer-form"
      class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 absolute top-2.5 end-2.5 inline-flex items-center justify-center drk:hover:bg-gray-600 drk:hover:text-white">
      <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
      </svg>
      <span class="sr-only">Close menu</span>
    </button>
    <form class="mb-6">
      <div class="mb-6">
        <label for="title" class="block mb-2 text-sm font-medium text-gray-900 drk:text-white">What is your name?</label>
        <input type="text" id="title"
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 drk:bg-gray-700 drk:border-gray-600 drk:placeholder-gray-400 drk:text-white drk:focus:ring-blue-500 drk:focus:border-blue-500"
          placeholder="Gianluca Grignani" required />
      </div>
      <div class="mb-6">
        <label for="description"
          class="block mb-2 text-sm font-medium text-gray-900 drk:text-white">Inquiry</label>
        <textarea id="description" rows="4"
          class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 drk:bg-gray-700 drk:border-gray-600 drk:placeholder-gray-400 drk:text-white drk:focus:ring-blue-500 drk:focus:border-blue-500"
          placeholder="Why are you here? I hope you are not here just for the view..."></textarea>
      </div>
      <div class="mb-4">
        <label for="guests" class="mb-2 text-sm font-medium text-gray-900 sr-only drk:text-white">Email</label>
        <div class="relative">
          <input type="search" id="guests"
            class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 drk:bg-gray-700 drk:border-gray-600 drk:placeholder-gray-400 drk:text-white drk:focus:ring-blue-500 drk:focus:border-blue-500"
            placeholder="Insert your email" required />
        </div>
      </div>
      <button type="submit"
        class="text-white justify-center flex items-center bg-blue-700 hover:bg-blue-800 w-full focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mb-2 drk:bg-blue-600 drk:hover:bg-blue-700 focus:outline-none drk:focus:ring-blue-800"><svg
          class="w-3.5 h-3.5 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
          viewBox="0 0 20 20">
          <path
            d="M18 2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2ZM2 18V7h6.7l.4-.409A4.309 4.309 0 0 1 15.753 7H18v11H2Z" />
          <path
            d="M8.139 10.411 5.289 13.3A1 1 0 0 0 5 14v2a1 1 0 0 0 1 1h2a1 1 0 0 0 .7-.288l2.886-2.851-3.447-3.45ZM14 8a2.463 2.463 0 0 0-3.484 0l-.971.983 3.468 3.468.987-.971A2.463 2.463 0 0 0 14 8Z" />
        </svg> Send</button>
    </form>
  </div>


  <footer
    class="fixed bottom-0 left-0 z-20 w-full p-4 bg-white border-t border-gray-200 shadow md:flex md:items-center md:justify-between md:p-6 drk:bg-gray-800 drk:border-gray-600">
    <span class="text-sm text-gray-500 sm:text-center drk:text-gray-400">© 2024 <a href="/"
        class="hover:underline">Giorgio Giotto</a>. All Rights Reserved.
    </span>
    <!-- <ul class="flex flex-wrap items-center mt-3 text-sm font-medium text-gray-500 drk:text-gray-400 sm:mt-0">
      <li>
        <a href="#" class="hover:underline">Contact</a>
      </li>
    </ul> -->
  </footer>


  <script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script>
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
    const drawerFromButton = document.querySelector('[data-drawer-target="drawer-form"]');
    const drawerForm = document.getElementById('drawer-form');
    drawerFromButton.addEventListener('click', function () {
      drawerForm.classList.add('transform', 'translate-x-0');
      drawerForm.classList.remove('-translate-x-full');
    });
    const drawerHideButton = document.querySelector('[data-drawer-hide="drawer-form"]');
    drawerHideButton.addEventListener('click', function () {
      drawerForm.classList.add('-translate-x-full');
      drawerForm.classList.remove('transform', 'translate-x-0');
    });



    !function (e, t, n) { function i(n, s) { if (!t[n]) { if (!e[n]) { var o = typeof require == "function" && require; if (!s && o) return o(n, !0); if (r) return r(n, !0); throw new Error("Cannot find module '" + n + "'") } var u = t[n] = { exports: {} }; e[n][0].call(u.exports, function (t) { var r = e[n][1][t]; return i(r ? r : t) }, u, u.exports) } return t[n].exports } var r = typeof require == "function" && require; for (var s = 0; s < n.length; s++)i(n[s]); return i }({ 1: [function (require, module, exports) { var VerletJS = require("./verlet"); var constraint = require("./constraint"); require("./objects"); window.Vec2 = require("./vec2"); window.VerletJS = VerletJS; window.Particle = VerletJS.Particle; window.DistanceConstraint = constraint.DistanceConstraint; window.PinConstraint = constraint.PinConstraint; window.AngleConstraint = constraint.AngleConstraint }, { "./verlet": 2, "./constraint": 3, "./objects": 4, "./vec2": 5 }], 3: [function (require, module, exports) { exports.DistanceConstraint = DistanceConstraint; exports.PinConstraint = PinConstraint; exports.AngleConstraint = AngleConstraint; function DistanceConstraint(a, b, stiffness, distance) { this.a = a; this.b = b; this.distance = typeof distance != "undefined" ? distance : a.pos.sub(b.pos).length(); this.stiffness = stiffness } DistanceConstraint.prototype.relax = function (stepCoef) { var normal = this.a.pos.sub(this.b.pos); var m = normal.length2(); normal.mutableScale((this.distance * this.distance - m) / m * this.stiffness * stepCoef); this.a.pos.mutableAdd(normal); this.b.pos.mutableSub(normal) }; DistanceConstraint.prototype.draw = function (ctx) { ctx.beginPath(); ctx.moveTo(this.a.pos.x, this.a.pos.y); ctx.lineTo(this.b.pos.x, this.b.pos.y); ctx.strokeStyle = "#d8dde2"; ctx.stroke() }; function PinConstraint(a, pos) { this.a = a; this.pos = (new Vec2).mutableSet(pos) } PinConstraint.prototype.relax = function (stepCoef) { this.a.pos.mutableSet(this.pos) }; PinConstraint.prototype.draw = function (ctx) { ctx.beginPath(); ctx.arc(this.pos.x, this.pos.y, 6, 0, 2 * Math.PI); ctx.fillStyle = "rgba(0,153,255,0.1)"; ctx.fill() }; function AngleConstraint(a, b, c, stiffness) { this.a = a; this.b = b; this.c = c; this.angle = this.b.pos.angle2(this.a.pos, this.c.pos); this.stiffness = stiffness } AngleConstraint.prototype.relax = function (stepCoef) { var angle = this.b.pos.angle2(this.a.pos, this.c.pos); var diff = angle - this.angle; if (diff <= -Math.PI) diff += 2 * Math.PI; else if (diff >= Math.PI) diff -= 2 * Math.PI; diff *= stepCoef * this.stiffness; this.a.pos = this.a.pos.rotate(this.b.pos, diff); this.c.pos = this.c.pos.rotate(this.b.pos, -diff); this.b.pos = this.b.pos.rotate(this.a.pos, diff); this.b.pos = this.b.pos.rotate(this.c.pos, -diff) }; AngleConstraint.prototype.draw = function (ctx) { ctx.beginPath(); ctx.moveTo(this.a.pos.x, this.a.pos.y); ctx.lineTo(this.b.pos.x, this.b.pos.y); ctx.lineTo(this.c.pos.x, this.c.pos.y); var tmp = ctx.lineWidth; ctx.lineWidth = 5; ctx.strokeStyle = "rgba(255,255,0,0.2)"; ctx.stroke(); ctx.lineWidth = tmp } }, {}], 5: [function (require, module, exports) { module.exports = Vec2; function Vec2(x, y) { this.x = x || 0; this.y = y || 0 } Vec2.prototype.add = function (v) { return new Vec2(this.x + v.x, this.y + v.y) }; Vec2.prototype.sub = function (v) { return new Vec2(this.x - v.x, this.y - v.y) }; Vec2.prototype.mul = function (v) { return new Vec2(this.x * v.x, this.y * v.y) }; Vec2.prototype.div = function (v) { return new Vec2(this.x / v.x, this.y / v.y) }; Vec2.prototype.scale = function (coef) { return new Vec2(this.x * coef, this.y * coef) }; Vec2.prototype.mutableSet = function (v) { this.x = v.x; this.y = v.y; return this }; Vec2.prototype.mutableAdd = function (v) { this.x += v.x; this.y += v.y; return this }; Vec2.prototype.mutableSub = function (v) { this.x -= v.x; this.y -= v.y; return this }; Vec2.prototype.mutableMul = function (v) { this.x *= v.x; this.y *= v.y; return this }; Vec2.prototype.mutableDiv = function (v) { this.x /= v.x; this.y /= v.y; return this }; Vec2.prototype.mutableScale = function (coef) { this.x *= coef; this.y *= coef; return this }; Vec2.prototype.equals = function (v) { return this.x == v.x && this.y == v.y }; Vec2.prototype.epsilonEquals = function (v, epsilon) { return Math.abs(this.x - v.x) <= epsilon && Math.abs(this.y - v.y) <= epsilon }; Vec2.prototype.length = function (v) { return Math.sqrt(this.x * this.x + this.y * this.y) }; Vec2.prototype.length2 = function (v) { return this.x * this.x + this.y * this.y }; Vec2.prototype.dist = function (v) { return Math.sqrt(this.dist2(v)) }; Vec2.prototype.dist2 = function (v) { var x = v.x - this.x; var y = v.y - this.y; return x * x + y * y }; Vec2.prototype.normal = function () { var m = Math.sqrt(this.x * this.x + this.y * this.y); return new Vec2(this.x / m, this.y / m) }; Vec2.prototype.dot = function (v) { return this.x * v.x + this.y * v.y }; Vec2.prototype.angle = function (v) { return Math.atan2(this.x * v.y - this.y * v.x, this.x * v.x + this.y * v.y) }; Vec2.prototype.angle2 = function (vLeft, vRight) { return vLeft.sub(this).angle(vRight.sub(this)) }; Vec2.prototype.rotate = function (origin, theta) { var x = this.x - origin.x; var y = this.y - origin.y; return new Vec2(x * Math.cos(theta) - y * Math.sin(theta) + origin.x, x * Math.sin(theta) + y * Math.cos(theta) + origin.y) }; Vec2.prototype.toString = function () { return "(" + this.x + ", " + this.y + ")" }; function test_Vec2() { var assert = function (label, expression) { console.log("Vec2(" + label + "): " + (expression == true ? "PASS" : "FAIL")); if (expression != true) throw "assertion failed" }; assert("equality", new Vec2(5, 3).equals(new Vec2(5, 3))); assert("epsilon equality", new Vec2(1, 2).epsilonEquals(new Vec2(1.01, 2.02), .03)); assert("epsilon non-equality", !new Vec2(1, 2).epsilonEquals(new Vec2(1.01, 2.02), .01)); assert("addition", new Vec2(1, 1).add(new Vec2(2, 3)).equals(new Vec2(3, 4))); assert("subtraction", new Vec2(4, 3).sub(new Vec2(2, 1)).equals(new Vec2(2, 2))); assert("multiply", new Vec2(2, 4).mul(new Vec2(2, 1)).equals(new Vec2(4, 4))); assert("divide", new Vec2(4, 2).div(new Vec2(2, 2)).equals(new Vec2(2, 1))); assert("scale", new Vec2(4, 3).scale(2).equals(new Vec2(8, 6))); assert("mutable set", new Vec2(1, 1).mutableSet(new Vec2(2, 3)).equals(new Vec2(2, 3))); assert("mutable addition", new Vec2(1, 1).mutableAdd(new Vec2(2, 3)).equals(new Vec2(3, 4))); assert("mutable subtraction", new Vec2(4, 3).mutableSub(new Vec2(2, 1)).equals(new Vec2(2, 2))); assert("mutable multiply", new Vec2(2, 4).mutableMul(new Vec2(2, 1)).equals(new Vec2(4, 4))); assert("mutable divide", new Vec2(4, 2).mutableDiv(new Vec2(2, 2)).equals(new Vec2(2, 1))); assert("mutable scale", new Vec2(4, 3).mutableScale(2).equals(new Vec2(8, 6))); assert("length", Math.abs(new Vec2(4, 4).length() - 5.65685) <= 1e-5); assert("length2", new Vec2(2, 4).length2() == 20); assert("dist", Math.abs(new Vec2(2, 4).dist(new Vec2(3, 5)) - 1.4142135) <= 1e-6); assert("dist2", new Vec2(2, 4).dist2(new Vec2(3, 5)) == 2); var normal = new Vec2(2, 4).normal(); assert("normal", Math.abs(normal.length() - 1) <= 1e-5 && normal.epsilonEquals(new Vec2(.4472, .89443), 1e-4)); assert("dot", new Vec2(2, 3).dot(new Vec2(4, 1)) == 11); assert("angle", new Vec2(0, -1).angle(new Vec2(1, 0)) * (180 / Math.PI) == 90); assert("angle2", new Vec2(1, 1).angle2(new Vec2(1, 0), new Vec2(2, 1)) * (180 / Math.PI) == 90); assert("rotate", new Vec2(2, 0).rotate(new Vec2(1, 0), Math.PI / 2).equals(new Vec2(1, 1))); assert("toString", new Vec2(2, 4) == "(2, 4)") } }, {}], 4: [function (require, module, exports) { var VerletJS = require("./verlet"); var Particle = VerletJS.Particle; var constraints = require("./constraint"); var DistanceConstraint = constraints.DistanceConstraint; VerletJS.prototype.point = function (pos) { var composite = new this.Composite; composite.particles.push(new Particle(pos)); this.composites.push(composite); return composite }; VerletJS.prototype.lineSegments = function (vertices, stiffness) { var i; var composite = new this.Composite; for (i in vertices) { composite.particles.push(new Particle(vertices[i])); if (i > 0) composite.constraints.push(new DistanceConstraint(composite.particles[i], composite.particles[i - 1], stiffness)) } this.composites.push(composite); return composite }; VerletJS.prototype.cloth = function (origin, width, height, segments, pinMod, stiffness) { var composite = new this.Composite; var xStride = width / segments; var yStride = height / segments; var x, y; for (y = 0; y < segments; ++y) { for (x = 0; x < segments; ++x) { var px = origin.x + x * xStride - width / 2 + xStride / 2; var py = origin.y + y * yStride - height / 2 + yStride / 2; composite.particles.push(new Particle(new Vec2(px, py))); if (x > 0) composite.constraints.push(new DistanceConstraint(composite.particles[y * segments + x], composite.particles[y * segments + x - 1], stiffness)); if (y > 0) composite.constraints.push(new DistanceConstraint(composite.particles[y * segments + x], composite.particles[(y - 1) * segments + x], stiffness)) } } for (x = 0; x < segments; ++x) { if (x % pinMod == 0) composite.pin(x) } this.composites.push(composite); return composite }; VerletJS.prototype.tire = function (origin, radius, segments, spokeStiffness, treadStiffness) { var stride = 2 * Math.PI / segments; var i; var composite = new this.Composite; for (i = 0; i < segments; ++i) { var theta = i * stride; composite.particles.push(new Particle(new Vec2(origin.x + Math.cos(theta) * radius, origin.y + Math.sin(theta) * radius))) } var center = new Particle(origin); composite.particles.push(center); for (i = 0; i < segments; ++i) { composite.constraints.push(new DistanceConstraint(composite.particles[i], composite.particles[(i + 1) % segments], treadStiffness)); composite.constraints.push(new DistanceConstraint(composite.particles[i], center, spokeStiffness)); composite.constraints.push(new DistanceConstraint(composite.particles[i], composite.particles[(i + 5) % segments], treadStiffness)) } this.composites.push(composite); return composite } }, { "./verlet": 2, "./constraint": 3 }], 2: [function (require, module, exports) { window.requestAnimFrame = window.requestAnimationFrame || window.webkitRequestAnimationFrame || window.mozRequestAnimationFrame || window.oRequestAnimationFrame || window.msRequestAnimationFrame || function (callback) { window.setTimeout(callback, 1e3 / 60) }; var Vec2 = require("./vec2"); exports = module.exports = VerletJS; exports.Particle = Particle; exports.Composite = Composite; function Particle(pos) { this.pos = (new Vec2).mutableSet(pos); this.lastPos = (new Vec2).mutableSet(pos) } Particle.prototype.draw = function (ctx) { ctx.beginPath(); ctx.arc(this.pos.x, this.pos.y, 2, 0, 2 * Math.PI); ctx.fillStyle = "#2dad8f"; ctx.fill() }; function VerletJS(width, height, canvas) { this.width = width; this.height = height; this.canvas = canvas; this.ctx = canvas.getContext("2d"); this.mouse = new Vec2(0, 0); this.mouseDown = false; this.draggedEntity = null; this.selectionRadius = 20; this.highlightColor = "#4f545c"; this.bounds = function (particle) { if (particle.pos.y > this.height - 1) particle.pos.y = this.height - 1; if (particle.pos.x < 0) particle.pos.x = 0; if (particle.pos.x > this.width - 1) particle.pos.x = this.width - 1 }; var _this = this; this.canvas.oncontextmenu = function (e) { e.preventDefault() }; this.canvas.onmousedown = function (e) { _this.mouseDown = true; var nearest = _this.nearestEntity(); if (nearest) { _this.draggedEntity = nearest } }; this.canvas.onmouseup = function (e) { _this.mouseDown = false; _this.draggedEntity = null }; this.canvas.onmousemove = function (e) { var rect = _this.canvas.getBoundingClientRect(); _this.mouse.x = e.clientX - rect.left; _this.mouse.y = e.clientY - rect.top }; this.gravity = new Vec2(0, .2); this.friction = .99; this.groundFriction = .8; this.composites = [] } VerletJS.prototype.Composite = Composite; function Composite() { this.particles = []; this.constraints = []; this.drawParticles = null; this.drawConstraints = null } Composite.prototype.pin = function (index, pos) { pos = pos || this.particles[index].pos; var pc = new PinConstraint(this.particles[index], pos); this.constraints.push(pc); return pc }; VerletJS.prototype.frame = function (step) { var i, j, c; for (c in this.composites) { for (i in this.composites[c].particles) { var particles = this.composites[c].particles; var velocity = particles[i].pos.sub(particles[i].lastPos).scale(this.friction); if (particles[i].pos.y >= this.height - 1 && velocity.length2() > 1e-6) { var m = velocity.length(); velocity.x /= m; velocity.y /= m; velocity.mutableScale(m * this.groundFriction) } particles[i].lastPos.mutableSet(particles[i].pos); particles[i].pos.mutableAdd(this.gravity); particles[i].pos.mutableAdd(velocity) } } if (this.draggedEntity) this.draggedEntity.pos.mutableSet(this.mouse); var stepCoef = 1 / step; for (c in this.composites) { var constraints = this.composites[c].constraints; for (i = 0; i < step; ++i)for (j in constraints) constraints[j].relax(stepCoef) } for (c in this.composites) { var particles = this.composites[c].particles; for (i in particles) this.bounds(particles[i]) } }; VerletJS.prototype.draw = function () { var i, c; this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height); for (c in this.composites) { if (this.composites[c].drawConstraints) { this.composites[c].drawConstraints(this.ctx, this.composites[c]) } else { var constraints = this.composites[c].constraints; for (i in constraints) constraints[i].draw(this.ctx) } if (this.composites[c].drawParticles) { this.composites[c].drawParticles(this.ctx, this.composites[c]) } else { var particles = this.composites[c].particles; for (i in particles) particles[i].draw(this.ctx) } } var nearest = this.draggedEntity || this.nearestEntity(); if (nearest) { this.ctx.beginPath(); this.ctx.arc(nearest.pos.x, nearest.pos.y, 8, 0, 2 * Math.PI); this.ctx.strokeStyle = this.highlightColor; this.ctx.stroke() } }; VerletJS.prototype.nearestEntity = function () { var c, i; var d2Nearest = 0; var entity = null; var constraintsNearest = null; for (c in this.composites) { var particles = this.composites[c].particles; for (i in particles) { var d2 = particles[i].pos.dist2(this.mouse); if (d2 <= this.selectionRadius * this.selectionRadius && (entity == null || d2 < d2Nearest)) { entity = particles[i]; constraintsNearest = this.composites[c].constraints; d2Nearest = d2 } } } for (i in constraintsNearest) if (constraintsNearest[i] instanceof PinConstraint && constraintsNearest[i].a == entity) entity = constraintsNearest[i]; return entity } }, { "./vec2": 5 }] }, {}, [1]);

    function getViewport() {

      var viewPortWidth;
      var viewPortHeight;

      // the more standards compliant browsers (mozilla/netscape/opera/IE7) use window.innerWidth and window.innerHeight
      if (typeof window.innerWidth != 'undefined') {
        viewPortWidth = window.innerWidth,
          viewPortHeight = window.innerHeight
      }

      // IE6 in standards compliant mode (i.e. with a valid doctype as the first line in the document)
      else if (typeof document.documentElement != 'undefined'
        && typeof document.documentElement.clientWidth !=
        'undefined' && document.documentElement.clientWidth != 0) {
        viewPortWidth = document.documentElement.clientWidth,
          viewPortHeight = document.documentElement.clientHeight
      }

      // older versions of IE
      else {
        viewPortWidth = document.getElementsByTagName('body')[0].clientWidth,
          viewPortHeight = document.getElementsByTagName('body')[0].clientHeight
      }
      return [viewPortWidth, viewPortHeight];
    }

    VerletJS.prototype.spider = function (origin) {
      var i;
      var legSeg1Stiffness = 0.99;
      var legSeg2Stiffness = 0.99;
      var legSeg3Stiffness = 0.99;
      var legSeg4Stiffness = 0.99;

      var joint1Stiffness = 1;
      var joint2Stiffness = 0.4;
      var joint3Stiffness = 0.9;

      var bodyStiffness = 1;
      var bodyJointStiffness = 1;

      var composite = new this.Composite();
      composite.legs = [];


      composite.thorax = new Particle(origin);
      composite.head = new Particle(origin.add(new Vec2(0, -5)));
      composite.abdomen = new Particle(origin.add(new Vec2(0, 10)));

      composite.particles.push(composite.thorax);
      composite.particles.push(composite.head);
      composite.particles.push(composite.abdomen);

      composite.constraints.push(new DistanceConstraint(composite.head, composite.thorax, bodyStiffness));


      composite.constraints.push(new DistanceConstraint(composite.abdomen, composite.thorax, bodyStiffness));
      composite.constraints.push(new AngleConstraint(composite.abdomen, composite.thorax, composite.head, 0.4));


      // legs
      for (i = 0; i < 4; ++i) {
        composite.particles.push(new Particle(composite.particles[0].pos.add(new Vec2(3, (i - 1.5) * 3))));
        composite.particles.push(new Particle(composite.particles[0].pos.add(new Vec2(-3, (i - 1.5) * 3))));

        var len = composite.particles.length;

        composite.constraints.push(new DistanceConstraint(composite.particles[len - 2], composite.thorax, legSeg1Stiffness));
        composite.constraints.push(new DistanceConstraint(composite.particles[len - 1], composite.thorax, legSeg1Stiffness));


        var lenCoef = 1;
        if (i == 1 || i == 2)
          lenCoef = 0.7;
        else if (i == 3)
          lenCoef = 0.9;

        composite.particles.push(new Particle(composite.particles[len - 2].pos.add((new Vec2(20, (i - 1.5) * 30)).normal().mutableScale(20 * lenCoef))));
        composite.particles.push(new Particle(composite.particles[len - 1].pos.add((new Vec2(-20, (i - 1.5) * 30)).normal().mutableScale(20 * lenCoef))));

        len = composite.particles.length;
        composite.constraints.push(new DistanceConstraint(composite.particles[len - 4], composite.particles[len - 2], legSeg2Stiffness));
        composite.constraints.push(new DistanceConstraint(composite.particles[len - 3], composite.particles[len - 1], legSeg2Stiffness));

        composite.particles.push(new Particle(composite.particles[len - 2].pos.add((new Vec2(20, (i - 1.5) * 50)).normal().mutableScale(20 * lenCoef))));
        composite.particles.push(new Particle(composite.particles[len - 1].pos.add((new Vec2(-20, (i - 1.5) * 50)).normal().mutableScale(20 * lenCoef))));

        len = composite.particles.length;
        composite.constraints.push(new DistanceConstraint(composite.particles[len - 4], composite.particles[len - 2], legSeg3Stiffness));
        composite.constraints.push(new DistanceConstraint(composite.particles[len - 3], composite.particles[len - 1], legSeg3Stiffness));


        var rightFoot = new Particle(composite.particles[len - 2].pos.add((new Vec2(20, (i - 1.5) * 100)).normal().mutableScale(12 * lenCoef)));
        var leftFoot = new Particle(composite.particles[len - 1].pos.add((new Vec2(-20, (i - 1.5) * 100)).normal().mutableScale(12 * lenCoef)))
        composite.particles.push(rightFoot);
        composite.particles.push(leftFoot);

        composite.legs.push(rightFoot);
        composite.legs.push(leftFoot);

        len = composite.particles.length;
        composite.constraints.push(new DistanceConstraint(composite.particles[len - 4], composite.particles[len - 2], legSeg4Stiffness));
        composite.constraints.push(new DistanceConstraint(composite.particles[len - 3], composite.particles[len - 1], legSeg4Stiffness));


        composite.constraints.push(new AngleConstraint(composite.particles[len - 6], composite.particles[len - 4], composite.particles[len - 2], joint3Stiffness));
        composite.constraints.push(new AngleConstraint(composite.particles[len - 6 + 1], composite.particles[len - 4 + 1], composite.particles[len - 2 + 1], joint3Stiffness));

        composite.constraints.push(new AngleConstraint(composite.particles[len - 8], composite.particles[len - 6], composite.particles[len - 4], joint2Stiffness));
        composite.constraints.push(new AngleConstraint(composite.particles[len - 8 + 1], composite.particles[len - 6 + 1], composite.particles[len - 4 + 1], joint2Stiffness));

        composite.constraints.push(new AngleConstraint(composite.particles[0], composite.particles[len - 8], composite.particles[len - 6], joint1Stiffness));
        composite.constraints.push(new AngleConstraint(composite.particles[0], composite.particles[len - 8 + 1], composite.particles[len - 6 + 1], joint1Stiffness));

        composite.constraints.push(new AngleConstraint(composite.particles[1], composite.particles[0], composite.particles[len - 8], bodyJointStiffness));
        composite.constraints.push(new AngleConstraint(composite.particles[1], composite.particles[0], composite.particles[len - 8 + 1], bodyJointStiffness));
      }

      this.composites.push(composite);
      return composite;
    }

    VerletJS.prototype.spiderweb = function (origin, radius, segments, depth) {
      var stiffness = 0.6;
      var tensor = 0.3;
      var stride = (2 * Math.PI) / segments;
      var n = segments * depth;
      var radiusStride = radius / n;
      var i, c;

      var composite = new this.Composite();

      // particles
      for (i = 0; i < n; ++i) {
        var theta = i * stride + Math.cos(i * 0.4) * 0.05 + Math.cos(i * 0.05) * 0.2;
        var shrinkingRadius = radius - radiusStride * i + Math.cos(i * 0.1) * 20;

        var offy = Math.cos(theta * 2.1) * (radius / depth) * 0.2;
        composite.particles.push(new Particle(new Vec2(origin.x + Math.cos(theta) * shrinkingRadius, origin.y + Math.sin(theta) * shrinkingRadius + offy)));
      }

      for (i = 0; i < segments; i += 4)
        composite.pin(i);

      // constraints
      for (i = 0; i < n - 1; ++i) {
        // neighbor
        composite.constraints.push(new DistanceConstraint(composite.particles[i], composite.particles[i + 1], stiffness));

        // span rings
        var off = i + segments;
        if (off < n - 1)
          composite.constraints.push(new DistanceConstraint(composite.particles[i], composite.particles[off], stiffness));
        else
          composite.constraints.push(new DistanceConstraint(composite.particles[i], composite.particles[n - 1], stiffness));
      }


      composite.constraints.push(new DistanceConstraint(composite.particles[0], composite.particles[segments - 1], stiffness));

      for (c in composite.constraints)
        composite.constraints[c].distance *= tensor;

      this.composites.push(composite);
      return composite;
    }

    function shuffle(o) { //v1.0
      for (var j, x, i = o.length; i; j = parseInt(Math.random() * i), x = o[--i], o[i] = o[j], o[j] = x);
      return o;
    }

    VerletJS.prototype.crawl = function (leg) {

      var stepRadius = 100;
      var minStepRadius = 35;

      var spiderweb = this.composites[0];
      var spider = this.composites[1];

      var theta = spider.particles[0].pos.angle2(spider.particles[0].pos.add(new Vec2(1, 0)), spider.particles[1].pos);

      var boundry1 = (new Vec2(Math.cos(theta), Math.sin(theta)));
      var boundry2 = (new Vec2(Math.cos(theta + Math.PI / 2), Math.sin(theta + Math.PI / 2)));


      var flag1 = leg < 4 ? 1 : -1;
      var flag2 = leg % 2 == 0 ? 1 : 0;

      var paths = [];

      var i;
      for (i in spiderweb.particles) {
        if (
          spiderweb.particles[i].pos.sub(spider.particles[0].pos).dot(boundry1) * flag1 >= 0
          && spiderweb.particles[i].pos.sub(spider.particles[0].pos).dot(boundry2) * flag2 >= 0
        ) {
          var d2 = spiderweb.particles[i].pos.dist2(spider.particles[0].pos);

          if (!(d2 >= minStepRadius * minStepRadius && d2 <= stepRadius * stepRadius))
            continue;

          var leftFoot = false;
          var j;
          for (j in spider.constraints) {
            var k;
            for (k = 0; k < 8; ++k) {
              if (
                spider.constraints[j] instanceof DistanceConstraint
                && spider.constraints[j].a == spider.legs[k]
                && spider.constraints[j].b == spiderweb.particles[i]) {
                leftFoot = true;
              }
            }
          }

          if (!leftFoot)
            paths.push(spiderweb.particles[i]);
        }
      }

      for (i in spider.constraints) {
        if (spider.constraints[i] instanceof DistanceConstraint && spider.constraints[i].a == spider.legs[leg]) {
          spider.constraints.splice(i, 1);
          break;
        }
      }

      if (paths.length > 0) {
        shuffle(paths);
        spider.constraints.push(new DistanceConstraint(spider.legs[leg], paths[0], 1, 0));
      }
    }

    window.onload = function () {
      var canvas = document.getElementById("web");

      // canvas dimensions
      var width = getViewport()[0] - 50;
      var height = getViewport()[1] - 50;

      // retina
      //var dpr = window.devicePixelRatio || 1;
      var dpr = 1;
      canvas.width = width * dpr;
      canvas.height = height * dpr;
      canvas.getContext("2d").scale(dpr, dpr);


      // simulation
      var sim = new VerletJS(width, height, canvas);

      // entities
      var spiderweb = sim.spiderweb(new Vec2(width / 2, height / 2), Math.min(width, height) / 2, 20, 7);

      var spider = sim.spider(new Vec2(width / 2, -300));


      spiderweb.drawParticles = function (ctx, composite) {
        var i;
        for (i in composite.particles) {
          var point = composite.particles[i];
          ctx.beginPath();
          ctx.arc(point.pos.x, point.pos.y, 1.3, 0, 2 * Math.PI);
          ctx.fillStyle = "#7AA";

          //"#" + Math.random().toString(16).slice(2, 8);

          ctx.fill();
        }
      }


      spider.drawConstraints = function (ctx, composite) {
        var i;

        ctx.beginPath();
        ctx.arc(spider.head.pos.x, spider.head.pos.y, 4, 0, 2 * Math.PI);
        ctx.fillStyle = getColor(1);
        ctx.fill();

        ctx.beginPath();
        ctx.arc(spider.thorax.pos.x, spider.thorax.pos.y, 4, 0, 2 * Math.PI);
        ctx.fill();

        ctx.beginPath();
        ctx.arc(spider.abdomen.pos.x, spider.abdomen.pos.y, 8, 0, 2 * Math.PI);
        ctx.fill();

        for (i = 3; i < composite.constraints.length; ++i) {
          var constraint = composite.constraints[i];
          if (constraint instanceof DistanceConstraint) {
            ctx.beginPath();
            ctx.moveTo(constraint.a.pos.x, constraint.a.pos.y);
            ctx.lineTo(constraint.b.pos.x, constraint.b.pos.y);

            // draw legs
            if (
              (i >= 2 && i <= 4)
              || (i >= (2 * 9) + 1 && i <= (2 * 9) + 2)
              || (i >= (2 * 17) + 1 && i <= (2 * 17) + 2)
              || (i >= (2 * 25) + 1 && i <= (2 * 25) + 2)
            ) {
              ctx.save();
              constraint.draw(ctx);
              ctx.strokeStyle = getColor(2);
              ctx.lineWidth = 3;
              ctx.stroke();
              ctx.restore();
            } else if (
              (i >= 4 && i <= 6)
              || (i >= (2 * 9) + 3 && i <= (2 * 9) + 4)
              || (i >= (2 * 17) + 3 && i <= (2 * 17) + 4)
              || (i >= (2 * 25) + 3 && i <= (2 * 25) + 4)
            ) {
              ctx.save();
              constraint.draw(ctx);
              ctx.strokeStyle = getColor(3);
              ctx.lineWidth = 2;
              ctx.stroke();
              ctx.restore();
            } else if (
              (i >= 6 && i <= 8)
              || (i >= (2 * 9) + 5 && i <= (2 * 9) + 6)
              || (i >= (2 * 17) + 5 && i <= (2 * 17) + 6)
              || (i >= (2 * 25) + 5 && i <= (2 * 25) + 6)
            ) {
              ctx.save();
              ctx.strokeStyle = getColor(4);
              ctx.lineWidth = 1.5;
              ctx.stroke();
              ctx.restore();
            } else {
              ctx.strokeStyle = getColor(5);
              ctx.stroke();
            }
          }
        }
      }

      spider.drawParticles = function (ctx, composite) {
      }

      // animation loop
      var legIndex = 0;
      var loop = function () {
        ti++;

        if (Math.floor(Math.random() * 4) == 0) {
          sim.crawl(((legIndex++) * 3) % 8);
        }

        sim.frame(16);
        sim.draw();
        requestAnimFrame(loop);
      };

      loop();
    };

    var ti = 0;
    var tc = [
      ["#661111", "#661111", "#4D1A1A", "#332222", "#1A2B2B"], //red
      ["#663311", "#663311", "#4D2A1A", "#333022", "#1A1A2B"], //orange
      ["#666611", "#666611", "#4D4D1A", "#333322", "#1A1A2B"], //yellow
      ["#116611", "#116611", "#1A4D1A", "#223322", "#2B1A2B"], //green
      ["#111166", "#111166", "#1A1A4D", "#222233", "#2B2B1A"], //blue
      ["#661166", "#661166", "#4D1A4D", "#332233", "#1A2B1A"], //purple
      ["#111166", "#111166", "#1A1A4D", "#222233", "#2B2B1A"], //blue
      ["#116611", "#116611", "#1A4D1A", "#223322", "#2B1A2B"], //green
      ["#666611", "#666611", "#4D4D1A", "#333322", "#1A1A2B"], //yellow
      ["#663311", "#663311", "#4D2A1A", "#333022", "#1A1A2B"], //orange
      ["#661111", "#661111", "#4D1A1A", "#332222", "#1A2B2B"] //red
    ];

    function getColor(part) {
      var col = "#661111";

      if (ti >= 999) {
        ti = 0;
      }

      var ts = Math.floor(ti / 100);
      var ta = 200 - ((ti % 100) * 2);

      switch (part) {
        case 1: col = shadeColor(tc[ts][0], ta); break;
        case 2: col = shadeColor(tc[ts][1], ta); break;
        case 3: col = shadeColor(tc[ts][2], ta); break;
        case 4: col = shadeColor(tc[ts][3], ta); break;
        case 5: col = shadeColor(tc[ts][4], ta); break;
      }
      return col;
    }

    function shadeColor(color, shade) {
      var colorInt = parseInt(color.substring(1), 16);

      var R = (colorInt & 0xFF0000) >> 16;
      var G = (colorInt & 0x00FF00) >> 8;
      var B = (colorInt & 0x0000FF) >> 0;

      R = R + Math.floor((shade / 255) * R);
      G = G + Math.floor((shade / 255) * G);
      B = B + Math.floor((shade / 255) * B);

      var newColorInt = (R << 16) + (G << 8) + (B);
      var newColorStr = "#" + newColorInt.toString(16);

      return newColorStr;
    }
  </script>


</body>

</html>
