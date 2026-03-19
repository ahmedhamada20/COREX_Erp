<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

/**
 * Base controller for all admin controllers.
 * Provides tenant resolution and shared utilities.
 */
abstract class AdminBaseController extends Controller
{
    /**
     * Returns the tenant owner ID for the authenticated user.
     * Sub-users resolve to their owner; owners resolve to themselves.
     */
    protected function ownerId(): int
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return (int) ($user->owner_user_id ?? $user->id);
    }
}
