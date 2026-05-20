<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // GET /api/notifications — ambil notif milik user login
    public function index(Request $request)
    {
        $notifs = UserNotification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();
        return response()->json($notifs);
    }

    // GET /api/notifications/unread-count
    public function unreadCount(Request $request)
    {
        $count = UserNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();
        return response()->json(['count' => $count]);
    }

    // PATCH /api/notifications/{id}/read
    public function markRead(Request $request, $id)
    {
        $notif = UserNotification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        $notif->update(['is_read' => true]);
        return response()->json(['ok' => true]);
    }

    // PATCH /api/notifications/read-all
    public function markAllRead(Request $request)
    {
        UserNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
        return response()->json(['ok' => true]);
    }

    // Helper statis — panggil dari controller lain saat status klaim berubah
    public static function send(int $userId, string $type, string $title, string $body, array $data = []): void
    {
        UserNotification::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'data'    => $data,
            'is_read' => false,
        ]);
    }
}