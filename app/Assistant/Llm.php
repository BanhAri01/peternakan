<?php

namespace App\Assistant;

interface Llm
{
    public function available(): bool;

    public function send(string $model, string $effort, int $maxTokens, array $system, array $tools, array $messages): LlmReply;
}
