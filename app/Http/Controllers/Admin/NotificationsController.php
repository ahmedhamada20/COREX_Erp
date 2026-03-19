<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationsController extends AdminBaseController
{
    public function index(): \Illuminate\View\View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $notifications = $user->notifications()->latest()->paginate(20);

        $user->unreadNotifications()->update(['read_at' => now()]);

        return view('admin.notifications.index', compact('notifications'));
    }

    public function unreadCount(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return response()->json([
            'count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function destroy(string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $user->notifications()->where('id', $id)->delete();

        return response()->json(['success' => true]);
    }
}
