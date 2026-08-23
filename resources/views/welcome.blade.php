<!DOCTYPE html>
<html lang="it" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Giorgio Giotto — IT Project Manager, logistica e supply chain</title>
    <meta name="description" content="Digitalizzo processi in logistica e supply chain: analisi dei processi, integrazione di sistemi, progetti con più stakeholder. IT Project Manager e founder di G8 Labs.">

    {{-- Chi arriva da LinkedIn o da Substack vede l'anteprima, non un link nudo. --}}
    <meta property="og:title" content="Giorgio Giotto — IT Project Manager, logistica e supply chain">
    <meta property="og:description" content="Lavoro tra business, operations e IT su processi complessi e sistemi che non si parlano tra loro.">
    <meta property="og:type" content="profile">
    <meta property="og:url" content="https://giorgiogiotto.it">
    <meta property="og:image" content="https://giorgiogiotto.it/icons/icon-512.png">
    <meta name="twitter:card" content="summary">

    {{-- Icone.
         `favicon.ico` con dentro 16/32/48 resta per i browser che cercano
         ancora quella; il PNG serve a tutti gli altri. `apple-touch-icon` è
         quadrata piena e senza trasparenza di proposito: iOS smussa da sé gli
         angoli, e una PNG con alpha diventa un rettangolo nero sulla home del
         telefono. Il manifest è ciò che rende decente il salvataggio su
         Android — senza, il sistema ritaglia una miniatura della pagina. --}}
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/icons/icon-32.png" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#1a5490">
    <meta name="apple-mobile-web-app-title" content="Giotto">
    <link rel="canonical" href="https://giorgiogiotto.it">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f6f4f1] text-[#1c1c1a] antialiased">

    <div class="mx-auto max-w-3xl px-6">

        {{-- ── Chi sono ─────────────────────────────────────────────────
             Il nome, cosa faccio e dove. Niente slogan: chi arriva qui ci
             arriva da LinkedIn o dalla newsletter e vuole capire in tre
             secondi se sono la persona giusta. --}}
        <header class="pt-20 pb-16 sm:pt-28">
            {{-- La foto tornerà qui quando ce n'è una decente: l'unica
                 disponibile è 90×90, cioè sfocata su qualunque schermo
                 moderno, e una foto sgranata su una pagina che parla di
                 competenza dice la cosa sbagliata prima di ogni parola. --}}
            <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">Giorgio Giotto</h1>

            <p class="mt-3 text-lg text-[#1a5490] font-medium">
                IT Project Manager · Founder di
                <a href="https://g8labs.it" class="underline decoration-[#1a5490]/30 underline-offset-4 hover:decoration-[#1a5490]">G8 Labs</a>
            </p>

            <p class="mt-6 text-lg leading-relaxed text-[#3d3d3a]">
                Digitalizzo processi in <strong class="font-semibold text-[#1c1c1a]">logistica e supply chain</strong>:
                contesti con processi complessi, sistemi frammentati e operatività che coinvolge più attori.
            </p>

            <p class="mt-4 text-lg leading-relaxed text-[#3d3d3a]">
                Nella pratica il problema raramente è la tecnologia. Il punto è capire come funzionano
                davvero i processi, allineare persone con obiettivi diversi, e far dialogare strumenti
                che non sono stati progettati per lavorare insieme.
            </p>

            <div class="mt-9 flex flex-wrap gap-3">
                <a href="https://www.linkedin.com/in/giorgiogiotto/" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 rounded-full bg-[#1a5490] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-[#164876]">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05a3.74 3.74 0 0 1 3.37-1.85c3.6 0 4.27 2.37 4.27 5.46v6.28ZM5.34 7.43a2.07 2.07 0 1 1 0-4.13 2.07 2.07 0 0 1 0 4.13Zm1.78 13.02H3.55V9h3.57v11.45ZM22.22 0H1.77C.79 0 0 .77 0 1.72v20.56C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.72V1.72C24 .77 23.2 0 22.22 0Z"/></svg>
                    LinkedIn
                </a>

                <a href="https://pmitlogisticatrasporti.substack.com/" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 rounded-full border border-[#1c1c1a]/15 bg-white px-5 py-2.5 text-sm font-medium transition hover:border-[#1a5490]/40">
                    <svg class="h-4 w-4 text-[#ff6719]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M22.539 8.242H1.46V5.406h21.08v2.836ZM1.46 10.812V24L12 18.11 22.54 24V10.812H1.46ZM22.54 0H1.46v2.836h21.08V0Z"/></svg>
                    La newsletter
                </a>
            </div>
        </header>

        <hr class="border-black/8">

        {{-- ── Cosa faccio ──────────────────────────────────────────────
             Le quattro cose in cui le aziende mi cercano, dette come le
             direbbe chi ha il problema — non come le direbbe un catalogo
             di servizi. --}}
        <section class="py-16">
            <h2 class="text-sm font-semibold uppercase tracking-widest text-[#8a8a84]">Cosa faccio</h2>

            <dl class="mt-8 grid gap-8 sm:grid-cols-2">
                <div>
                    <dt class="font-semibold">Analizzo e ripenso i processi</dt>
                    <dd class="mt-1.5 text-[#3d3d3a] leading-relaxed">
                        Prima di cambiare uno strumento, capire come si lavora davvero — non come
                        dice il manuale.
                    </dd>
                </div>
                <div>
                    <dt class="font-semibold">Integro i sistemi che avete già</dt>
                    <dd class="mt-1.5 text-[#3d3d3a] leading-relaxed">
                        Far parlare gestionali, portali e strumenti nuovi senza buttare via quello
                        che funziona.
                    </dd>
                </div>
                <div>
                    <dt class="font-semibold">Governo progetti con molti attori</dt>
                    <dd class="mt-1.5 text-[#3d3d3a] leading-relaxed">
                        Committente, fornitori, IT e operations: tenere insieme obiettivi che non
                        coincidono.
                    </dd>
                </div>
                <div>
                    <dt class="font-semibold">Traduco il business in tecnica</dt>
                    <dd class="mt-1.5 text-[#3d3d3a] leading-relaxed">
                        E viceversa. È la parte che di solito manca, ed è quella che fa fallire i
                        progetti.
                    </dd>
                </div>
            </dl>

            <p class="mt-10 text-[#3d3d3a] leading-relaxed">
                Il mio approccio è semplice: partire da come funzionano davvero le cose, lavorare
                per iterazioni, costruire soluzioni che reggano sul campo e non solo sulla carta.
            </p>
        </section>

        <hr class="border-black/8">

        {{-- ── Dove lavoro ──────────────────────────────────────────────
             Tre livelli, che è il modo in cui lui stesso descrive il
             proprio lavoro: istituzionale, aziendale, tecnico. --}}
        <section class="py-16">
            <h2 class="text-sm font-semibold uppercase tracking-widest text-[#8a8a84]">Dove</h2>

            <ul class="mt-8 space-y-7">
                <li class="flex flex-col gap-1 sm:flex-row sm:gap-6">
                    <span class="w-32 shrink-0 text-sm text-[#8a8a84] sm:pt-0.5">Dal 2025</span>
                    <div>
                        <p class="font-semibold">G8 Labs — <span class="font-normal">fondatore</span></p>
                        <p class="mt-1 text-[#3d3d3a] leading-relaxed">
                            Consulenza sulla trasformazione digitale per aziende di logistica e trasporti.
                        </p>
                    </div>
                </li>
                <li class="flex flex-col gap-1 sm:flex-row sm:gap-6">
                    <span class="w-32 shrink-0 text-sm text-[#8a8a84] sm:pt-0.5">Dal 2024</span>
                    <div>
                        <p class="font-semibold">Fedespedi — <span class="font-normal">IT Project Manager</span></p>
                        <p class="mt-1 text-[#3d3d3a] leading-relaxed">
                            Progetti di innovazione digitale per la federazione degli spedizionieri e per le
                            aziende associate, e coordinamento del Digital Advisory Body.
                        </p>
                    </div>
                </li>
                <li class="flex flex-col gap-1 sm:flex-row sm:gap-6">
                    <span class="w-32 shrink-0 text-sm text-[#8a8a84] sm:pt-0.5">Dal 2023</span>
                    <div>
                        <p class="font-semibold">Quisto — <span class="font-normal">Operations &amp; Tech</span></p>
                        <p class="mt-1 text-[#3d3d3a] leading-relaxed">
                            Logistica di un distributore digitale per bar e ristoranti: approvvigionamento,
                            magazzino, consegne. E il CRM su misura che le tiene insieme.
                        </p>
                    </div>
                </li>
                <li class="flex flex-col gap-1 sm:flex-row sm:gap-6">
                    <span class="w-32 shrink-0 text-sm text-[#8a8a84] sm:pt-0.5">Prima</span>
                    <div>
                        <p class="font-semibold text-[#3d3d3a]">Everli, Esselunga, Gluebus</p>
                        <p class="mt-1 text-[#3d3d3a] leading-relaxed">
                            Operations sul campo prima che dietro a un progetto: team di supporto,
                            aree commerciali, analisi di processi logistici. E una startup di trasporto
                            notturno, fondata e chiusa.
                        </p>
                    </div>
                </li>
            </ul>

            <p class="mt-10 text-sm text-[#8a8a84]">
                Bocconi, laurea e master in Economia aziendale · Washington University in St. Louis
            </p>
        </section>

        <hr class="border-black/8">

        {{-- ── Newsletter ───────────────────────────────────────────────
             Non un modulo di iscrizione: l'iscrizione si fa su Substack,
             e un secondo campo email qui vorrebbe dire mantenere una
             seconda lista. --}}
        <section class="py-16">
            <h2 class="text-sm font-semibold uppercase tracking-widest text-[#8a8a84]">Scrivo</h2>

            <a href="https://pmitlogisticatrasporti.substack.com/" target="_blank" rel="noopener"
               class="mt-8 block rounded-2xl border border-black/8 bg-white p-7 transition hover:border-[#1a5490]/40">
                <p class="text-xl font-semibold">IT Project Management in Logistica e Trasporti</p>
                <p class="mt-3 text-[#3d3d3a] leading-relaxed">
                    Cosa succede davvero nei progetti di digitalizzazione delle aziende italiane di
                    logistica e trasporti. Quello che ho visto funzionare, e quello che ho visto
                    fallire.
                </p>
                <p class="mt-5 inline-flex items-center gap-1.5 text-sm font-medium text-[#1a5490]">
                    Leggi su Substack
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H18v4.5M17.5 6.5 10 14M15 15v3.75A1.25 1.25 0 0 1 13.75 20H5.25A1.25 1.25 0 0 1 4 18.75v-8.5A1.25 1.25 0 0 1 5.25 9H9"/></svg>
                </p>
            </a>
        </section>

        <hr class="border-black/8">

        <footer class="flex flex-col gap-4 py-12 text-sm text-[#8a8a84] sm:flex-row sm:items-center sm:justify-between">
            <p>Giorgio Giotto · Brescia</p>
            <div class="flex gap-5">
                <a href="https://www.linkedin.com/in/giorgiogiotto/" target="_blank" rel="noopener" class="hover:text-[#1a5490]">LinkedIn</a>
                <a href="https://pmitlogisticatrasporti.substack.com/" target="_blank" rel="noopener" class="hover:text-[#1a5490]">Newsletter</a>
                <a href="https://g8labs.it" target="_blank" rel="noopener" class="hover:text-[#1a5490]">G8 Labs</a>
            </div>
        </footer>
    </div>
</body>
</html>
