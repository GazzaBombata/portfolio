<?php

namespace App\Assistant;

/** Quello che uno strumento restituisce al modello, e quello che si mostra a schermo. */
class ToolResult
{
    private function __construct(
        public readonly string $content,
        public readonly ?string $summary = null,
        public readonly bool $isError = false,
    ) {}

    public static function ok(string $content, ?string $summary = null): self
    {
        return new self($content, $summary);
    }

    public static function error(string $message): self
    {
        return new self($message, $message, true);
    }
}
