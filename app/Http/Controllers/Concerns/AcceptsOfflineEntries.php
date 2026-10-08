<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait AcceptsOfflineEntries
{
    protected function clientUuid(Request $request): ?string
    {
        $uuid = $request->input('client_uuid');

        return is_string($uuid) && Str::isUuid($uuid) ? strtolower($uuid) : null;
    }

    protected function alreadyReceived(Request $request, string $model, string $redirect)
    {
        $uuid = $this->clientUuid($request);

        if ($uuid === null || !$model::withTrashed()->where('client_uuid', $uuid)->exists()) {
            return null;
        }

        return $this->entrySaved($request, 'Catatan ini sudah diterima sebelumnya.', $redirect);
    }

    protected function entrySaved(Request $request, string $message, string $redirect, ?string $warning = null)
    {
        if (!$request->expectsJson()) {
            $response = redirect($redirect)->with('success', $message);

            return $warning ? $response->with('warning', $warning) : $response;
        }

        if (!$request->hasHeader('X-Hefam-Queue')) {
            session()->flash('success', $message);
            if ($warning) {
                session()->flash('warning', $warning);
            }
        }

        return response()->json(['message' => $message, 'warning' => $warning, 'redirect' => $redirect], 201);
    }
}
