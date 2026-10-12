<?php

namespace App\Assistant;

use Anthropic\Client;
use Anthropic\Messages\ToolUseBlock;
use GuzzleHttp\Client as GuzzleClient;
use RuntimeException;

class ClaudeLlm implements Llm
{
    private ?Client $client = null;

    public function available(): bool
    {
        return (string) config('hefam.assistant.api_key') !== '';
    }

    public function send(string $model, string $effort, int $maxTokens, array $system, array $tools, array $messages): LlmReply
    {
        $message = $this->client()->messages->create(
            maxTokens: $maxTokens,
            messages: $messages,
            model: $model,
            outputConfig: ['effort' => $effort],
            system: $system,
            tools: $tools,
        );

        $text = '';
        $calls = [];
        foreach ($message->content as $block) {
            if ($block instanceof ToolUseBlock) {
                $calls[] = ['id' => $block->id, 'name' => $block->name, 'input' => (array) $block->input];
            } elseif ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        return new LlmReply(
            stopReason: $message->stopReason,
            text: trim($text),
            toolCalls: $calls,
            content: $message->content,
            usage: [
                'input'       => $message->usage->inputTokens,
                'output'      => $message->usage->outputTokens,
                'cache_read'  => (int) $message->usage->cacheReadInputTokens,
                'cache_write' => (int) $message->usage->cacheCreationInputTokens,
            ],
            model: $model,
        );
    }

    private function client(): Client
    {
        if (!$this->available()) {
            throw new RuntimeException('ANTHROPIC_API_KEY belum diisi.');
        }

        return $this->client ??= new Client(
            apiKey: (string) config('hefam.assistant.api_key'),
            requestOptions: [
                'maxRetries'  => 2,
                'transporter' => new GuzzleClient(['timeout' => (float) config('hefam.assistant.timeout'), 'connect_timeout' => 10]),
            ],
        );
    }
}
