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
