<?php

namespace App\Assistant;

final class LlmReply
{
    public function __construct(
        public readonly ?string $stopReason,
        public readonly string $text,
        public readonly array $toolCalls,
        public readonly mixed $content,
        public readonly array $usage,
        public readonly string $model,
    ) {}

    public function wantsTools(): bool
    {
        return $this->stopReason === 'tool_use' && $this->toolCalls !== [];
    }
}
