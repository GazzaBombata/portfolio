<?php

it('mostra chi sei e cosa fai', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Giorgio Giotto')
        ->assertSee('logistica e supply chain');
});

/*
 * I due link sono il motivo per cui la pagina esiste: chi arriva qui deve
 * poter andare dove Giorgio pubblica davvero.
 */
it('porta a LinkedIn e alla newsletter', function () {
    $this->get('/')
        ->assertSee('linkedin.com/in/giorgiogiotto', escape: false)
        ->assertSee('pmitlogisticatrasporti.substack.com', escape: false)
        ->assertSee('g8labs.it', escape: false);
});

it('si presenta con un\'anteprima decente quando viene condivisa', function () {
    $this->get('/')
        ->assertSee('og:title', escape: false)
        ->assertSee('og:description', escape: false);
});

it('non espone niente del pannello', function () {
    $risposta = $this->get('/')->getContent();

    expect($risposta)->not->toContain('/admin');
});

/*
 * Le icone: quello che si vede nella scheda del browser e sulla home del
 * telefono. Un test perché sono file statici referenziati a mano — il tipo di
 * cosa che si rompe rinominando una cartella e che nessuno nota per mesi.
 */
it('dichiara le icone per browser, iOS e Android', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toContain('/favicon.ico')
        ->and($html)->toContain('apple-touch-icon')
        ->and($html)->toContain('/site.webmanifest')
        ->and($html)->toContain('theme-color');
});

it('serve davvero i file delle icone', function () {
    foreach ([
        'favicon.ico',
        'icons/apple-touch-icon.png',
        'icons/icon-192.png',
        'icons/icon-512.png',
        'site.webmanifest',
    ] as $file) {
        expect(public_path($file))->toBeFile();
    }
});

/*
 * iOS non gestisce la trasparenza in apple-touch-icon: dove c'è alpha
 * disegna nero, e l'icona sulla home diventa un rettangolo scuro.
 */
it('non lascia trasparenza nell\'icona per iOS', function () {
    $img = imagecreatefrompng(public_path('icons/apple-touch-icon.png'));
    $angolo = imagecolorat($img, 2, 2);
    $alpha = ($angolo >> 24) & 0x7F;

    expect($alpha)->toBe(0);
});

it('il manifest indica un\'icona maskable, per i lanciatori Android', function () {
    $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true);

    expect(collect($manifest['icons'])->pluck('purpose')->filter())->toContain('maskable')
        ->and($manifest['theme_color'])->toBe('#1a5490');
});
