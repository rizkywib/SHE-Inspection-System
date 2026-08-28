<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait AuthorizesOwnerOrAdmin
{
    protected function authorizeOwnerOrAdmin(
        Request $request,
        $record,
        string $ownerColumn
    ): ?JsonResponse {
        $user = $request->user();

        if ($user->isAdmin() || (int) $record->{$ownerColumn} === (int) $user->id) {
            return null;
        }

        return response()->json([
            'message' => 'Anda tidak memiliki izin untuk mengubah data ini.',
        ], 403);
    }
}
