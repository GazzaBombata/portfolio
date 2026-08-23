<?php

namespace App\Assistant;

interface Tool
{
    public function name(): string;

    public function description(): string;

    /** @return array<string, mixed> */
    public function schema(): array;

    /** @param array<string, mixed> $input */
    public function run(array $input): ToolResult;
}
