<?php

namespace App\Assistant;

/**
 * Hasil satu pertanyaan ke Asisten Kandang.
 *
 * status:
 *  ok        dijawab oleh AI memakai data alat
 *  command   perintah gratis (menu, pulsa, salam), tanpa AI
 *  quota     pulsa habis / batas harian, dijawab tanpa AI bila bisa
 *  offline   AI belum disetel, dijawab tanpa AI bila bisa
 *  error     AI gagal dihubungi, dijawab tanpa AI bila bisa
 *  limit     terlalu banyak putaran alat
 *  plan      paket belum termasuk asisten
 *  blocked   langganan habis / dibekukan
 *  empty     pertanyaan kosong
 */
final class AgentAnswer
{
    public function __construct(
        public readonly string $text,
        public readonly string $status,
        public readonly ?string $mode = null,
        public readonly float $cost = 0.0,
        public readonly array $tools = [],
        public readonly ?string $model = null,
    ) {}

    public function usedAi(): bool
    {
        return in_array($this->status, ['ok', 'limit'], true);
    }

    public function toArray(): array
    {
        return [
            'text'   => $this->text,
            'status' => $this->status,
            'mode'   => $this->mode,
            'cost'   => $this->cost,
            'tools'  => $this->tools,
        ];
    }
}
