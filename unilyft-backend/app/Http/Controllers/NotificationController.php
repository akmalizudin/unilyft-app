<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->query('user_id');
        // $notifications = Notification::with('carpool')->where('user_id', $userId)->orderBy('created_at', 'desc')->get();
        $notifications = Notification::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
        $unreadCount = Notification::where('user_id', $userId)->where('is_read', false)->count();
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAsRead($id)
    {
        $notification = Notification::find($id);
        if ($notification) {
            $notification->is_read = true;
            $notification->save();
            return response()->json(['message' => 'Notification marked as read']);
        }
        return response()->json(['message' => 'Notification not found'], 404);
    }
}
