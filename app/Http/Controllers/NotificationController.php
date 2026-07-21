<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * NotificationController
 * In-app notification management.
 */
class NotificationController extends Controller
{
    /**
     * Show notification center.
     */
    public function index(): View
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->recent()
            ->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark single notification as read.
     */
    public function markAsRead(Notification $notification): RedirectResponse
    {
        $this->authorizeAccess($notification);

        $notification->markAsRead();

        if ($notification->action_url) {
            return redirect()->to($notification->action_url);
        }

        return back();
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(): RedirectResponse
    {
        Notification::where('user_id', auth()->id())
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Get unread count (for AJAX polling).
     */
    public function unreadCount(): JsonResponse
    {
        $count = Notification::where('user_id', auth()->id())
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Delete notification.
     */
    public function destroy(Notification $notification): RedirectResponse
    {
        $this->authorizeAccess($notification);

        $notification->delete();

        return back();
    }

    private function authorizeAccess(Notification $notification): void
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
