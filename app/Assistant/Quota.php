<?php

namespace App\Assistant;

use App\Models\AssistantMessage;
use App\Models\AssistantTopup;
use App\Models\Farm;
use App\Tenancy\FarmScope;
use Carbon\Carbon;

class Quota
{
    public function __construct(private readonly Farm $farm) {}

    public static function for(Farm $farm): self
    {
        return new self($farm);
    }

    public function base(): float
    {
        return (float) config($this->farm->status === 'trial' ? 'hefam.assistant.trial_budget' : 'hefam.assistant.budget');
    }

    public function topups(): float
    {
        return (float) AssistantTopup::withoutGlobalScope(FarmScope::class)
            ->where('farm_id', $this->farm->id)
            ->where('period', Carbon::today()->format('Y-m'))
            ->sum('amount');
    }

    public function budget(): float
    {
        return $this->base() + $this->topups();
    }

    public function used(): float
    {
        return $this->spentSince(Carbon::today()->startOfMonth());
    }

    public function usedToday(): float
    {
        return $this->spentSince(Carbon::today());
    }

    public function dailyCap(): float
    {
        return max($this->base() * (float) config('hefam.assistant.daily_share'), (float) config('hefam.assistant.modes.deep.reserve'));
    }

    public function remaining(): float
    {
        return max(0.0, $this->budget() - $this->used());
    }

    public function percentLeft(): int
    {
        $budget = $this->budget();

        return $budget > 0 ? (int) floor($this->remaining() / $budget * 100) : 0;
    }

    public function questionsLeft(): int
    {
        return (int) floor($this->remaining() / max(1, (float) config('hefam.assistant.question_estimate')));
    }

    public function check(string $mode): ?string
    {
        $reserve = (float) config("hefam.assistant.modes.{$mode}.reserve");

        if ($this->remaining() < $reserve) {
            return $mode === 'deep' && $this->remaining() >= (float) config('hefam.assistant.modes.quick.reserve') ? 'deep' : 'month';
        }
        if ($this->dailyCap() - $this->usedToday() < $reserve) {
            return 'day';
        }

        return null;
    }

    private function spentSince(Carbon $from): float
    {
        return (float) AssistantMessage::withoutGlobalScope(FarmScope::class)
            ->where('farm_id', $this->farm->id)
            ->where('created_at', '>=', $from)
            ->sum('cost');
    }
}
