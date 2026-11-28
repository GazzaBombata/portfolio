<?php

use App\Ai\WritingStyle;
use App\Assistant\Topic;

// Lo stile non è un'impostazione: sta nel prompt statico di ogni assistente
// che scrive in italiano a una persona.
it('mette lo stile di scrittura nel prompt di ogni assistente', function (Topic $topic) {
    expect($topic->staticPrompt())->toContain(WritingStyle::block());
})->with(Topic::cases());
