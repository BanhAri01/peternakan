<?php

namespace App\Audit;

use App\Models\ActivityLog;
use App\Models\Coop;
use App\Models\Customer;
use App\Models\DailyLog;
use App\Models\EggGrade;
use App\Models\Supplier;
use App\Models\EggPurchase;
use App\Models\EggSorting;
use App\Models\ExpenseLedger;
use App\Models\FeedPurchase;
use App\Models\FeedStock;
use App\Models\Invoice;
use App\Models\OtherIncome;
use App\Models\User;
use App\Models\Vaccination;
use App\Support\Format;
use App\Tenancy\FarmContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityRecorder
{
    private const ALWAYS_IGNORED = ['id', 'farm_id', 'created_at', 'updated_at', 'deleted_at'];

    private const MAX_VALUE_LENGTH = 200;

    private const REFERENCES = [
        'coop_id'       => [Coop::class, 'name'],
        'feed_stock_id' => [FeedStock::class, 'feed_name'],
        'customer_id'   => [Customer::class, 'name'],
        'supplier_id'   => [Supplier::class, 'name'],
        'egg_grade_id'  => [EggGrade::class, 'name'],
        'recorded_by'   => [User::class, 'name'],
        'created_by'    => [User::class, 'name'],
    ];

    private static bool $paused = false;

    public static function withoutRecording(callable $callback): mixed
    {
        $previous     = self::$paused;
        self::$paused = true;

        try {
            return $callback();
        } finally {
            self::$paused = $previous;
        }
    }

    public static function model(Model $model, string $event): void
    {
        $changes = match ($event) {
            'updated' => self::diff($model),
            default   => self::snapshot($model),
        };

        if ($event === 'updated' && $changes === []) {
            return;
        }

        self::write($model->farm_id ?? null, $event, class_basename($model), $model->getKey(), self::label($model), null, $changes);
    }

    public static function custom(?Model $subject, string $event, string $description, array $changes = [], ?int $farmId = null): void
    {
        self::write(
            $farmId ?? $subject?->farm_id,
            $event,
            $subject ? class_basename($subject) : 'User',
            $subject?->getKey(),
            $subject ? self::label($subject) : null,
            $description,
            $changes ?: null
        );
    }

    private static function write(?int $farmId, string $event, string $type, $subjectId, ?string $label, ?string $description, ?array $changes): void
    {
        $farmId ??= app(FarmContext::class)->id();

        if (self::$paused || $farmId === null) {
            return;
        }

        $user = auth()->user();

        ActivityLog::create([
            'farm_id'       => $farmId,
            'user_id'       => $user?->id,
            'user_name'     => $user ? Str::limit($user->name, 97) : 'Sistem',
            'event'         => $event,
            'subject_type'  => $type,
            'subject_id'    => $subjectId,
            'subject_label' => $label !== null ? Str::limit($label, 250) : null,
            'description'   => $description !== null ? Str::limit($description, 250) : null,
            'changes'       => $changes,
            'ip'            => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    private static function diff(Model $model): array
    {
        $changes = [];

        foreach (array_keys($model->getChanges()) as $field) {
            if (self::ignored($model, $field)) {
                continue;
            }

            $changes[$field] = [
                self::value($model, $field, $model->getRawOriginal($field)),
                self::value($model, $field, $model->getAttributes()[$field] ?? null),
            ];
        }

        return $changes;
    }

    private static function snapshot(Model $model): array
    {
        $values = [];

        foreach ($model->getAttributes() as $field => $value) {
            if (!self::ignored($model, $field) && $value !== null && $value !== '') {
                $values[$field] = self::value($model, $field, $value);
            }
        }

        return $values;
    }

    private static function ignored(Model $model, string $field): bool
    {
        $extra = method_exists($model, 'auditIgnoredFields') ? $model->auditIgnoredFields() : [];

        return in_array($field, self::ALWAYS_IGNORED, true) || in_array($field, $extra, true);
    }

    private static function value(Model $model, string $field, $value): ?string
    {
        $masked = method_exists($model, 'auditMaskedFields') ? $model->auditMaskedFields() : [];

        if (in_array($field, $masked, true)) {
            return $value === null || $value === '' ? null : '••••';
        }

        if ($value === null) {
            return null;
        }

        if (isset(self::REFERENCES[$field])) {
            [$class, $attribute] = self::REFERENCES[$field];
            $value = $class::query()->whereKey($value)->value($attribute) ?? $value;
        }

        return Str::limit((string) $value, self::MAX_VALUE_LENGTH);
    }

    private static function label(Model $model): string
    {
        return (string) match (true) {
            $model instanceof DailyLog      => trim(($model->coop?->name ?? 'Kandang') . ', ' . Format::date($model->log_date)),
            $model instanceof Invoice       => $model->number,
            $model instanceof EggSorting    => Format::date($model->sort_date),
            $model instanceof FeedPurchase,
            $model instanceof EggPurchase   => Format::date($model->purchase_date),
            $model instanceof ExpenseLedger,
            $model instanceof OtherIncome   => $model->item_name ?: $model->category,
            $model instanceof Vaccination   => $model->vaccine_name,
            $model instanceof FeedStock     => $model->feed_name,
            $model instanceof User          => $model->name,
            default                         => $model->name ?? '#' . $model->getKey(),
        };
    }
}
