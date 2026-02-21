<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Kiểm tra user đã đăng nhập chưa
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $notifications = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($notifications);
    }

public function markAsRead($id)
{
    $userId = auth()->id();
    
    if (!$userId) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    
    // Ép kiểu $id thành số
    $notification = Notification::where('user_id', $userId)
        ->where('id', (int) $id)
        ->firstOrFail();

    $notification->update(['is_read' => true]);
    return response()->json(['message' => 'Marked as read']);
}
}