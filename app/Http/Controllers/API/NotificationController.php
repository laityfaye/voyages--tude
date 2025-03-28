<?php
// app/Http/Controllers/API/NotificationController.php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($notifications);
    }

    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $user = auth::user();
        
        if ($notification->utilisateur_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        
        $notification->estLue = true;
        $notification->save();
        
        return response()->json(['message' => 'Notification marquée comme lue']);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->notifications()->update(['estLue' => true]);
        
        return response()->json(['message' => 'Toutes les notifications ont été marquées comme lues']);
    }
}