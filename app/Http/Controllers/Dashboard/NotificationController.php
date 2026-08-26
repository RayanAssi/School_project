<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Exception\MessagingException;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    protected $messaging;

    public function __construct()
    {
        // Initialize Firebase
        $factory = (new Factory)
            ->withServiceAccount(config('firebase.credentials.file'));
        
        $this->messaging = $factory->createMessaging();
    }

    /**
     * Display all notifications (for admin)
     */
    public function index()
    {
        $notifications = Notification::orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $notifications
        ]);
    }

    /**
     * Display a specific notification
     */
    public function show($id)
    {
        $notification = Notification::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $notification
        ]);
    }

    /**
     *  Save button - Create notification and send to all users
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            // 1. Create the notification
            $notification = Notification::create([
                'title' => $validated['title'],
                'message' => $validated['message']
            ]);

            // 2. Get all users
            $users = User::all();
            
            if ($users->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No users found to send notification'
                ], 404);
            }

            // 3. Link notification to all users (store in database)
            foreach ($users as $user) {
                UserNotification::create([
                    'user_id' => $user->id,
                    'notification_id' => $notification->id,
                    'is_read' => false
                ]);
            }

            // 4. Send Firebase notification via Topic (to all devices)
            try {
                $this->sendFirebaseNotificationToTopic(
                    $notification->title,
                    $notification->message
                );
                $firebaseSent = true;
            } catch (\Exception $e) {
                $firebaseSent = false;
                $firebaseError = $e->getMessage();
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Notification created and sent to all users successfully',
                'data' => [
                    'notification' => $notification,
                    'total_users' => $users->count(),
                    'firebase_sent' => $firebaseSent ?? false,
                    'firebase_error' => $firebaseError ?? null
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send Firebase notification to all devices via Topic
     */
    public function sendFirebaseNotificationToTopic($title, $body)
    {
        try {
            $message = CloudMessage::new()
                ->withNotification(FirebaseNotification::create($title, $body))
                ->withData([
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'sound' => 'default'
                ])
                ->withTopic('all_users'); // ✅ Send to all subscribers of this Topic

            return $this->messaging->send($message);
            
        } catch (MessagingException $e) {
            throw new \Exception('Firebase error: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception('Failed to send notification: ' . $e->getMessage());
        }
    }

    /**
     * Update a notification
     */
    public function update(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'message' => 'sometimes|string'
        ]);

        $notification->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification updated successfully',
            'data' => $notification
        ]);
    }

    /**
     * Delete a notification with all its relations
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $notification = Notification::findOrFail($id);
            
            UserNotification::where('notification_id', $id)->delete();
            $notification->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Notification deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the logged-in user's notifications
     */
    public function myNotifications(Request $request)
    {
        $user = $request->user();

        $notifications = UserNotification::where('user_id', $user->id)
            ->with('notification')
            ->orderBy('created_at', 'desc')
            ->get();

        $unreadCount = $notifications->where('is_read', false)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user->only(['id', 'user_name', 'email']),
                'unread_count' => $unreadCount,
                'notifications' => $notifications
            ]
        ]);
    }

    /**
     *  Display the logged-in user's unread notifications only
     */
    public function myUnreadNotifications(Request $request)
    {
        $user = $request->user();

        $notifications = UserNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->with('notification')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user->only(['id', 'user_name', 'email']),
                'unread_count' => $notifications->count(),
                'notifications' => $notifications
            ]
        ]);
    }

    /**
     *  Mark a notification as read for the logged-in user
     */
    public function markMyNotificationAsRead(Request $request, $notificationId)
    {
        $user = $request->user();

        $userNotification = UserNotification::where('user_id', $user->id)
            ->where('notification_id', $notificationId)
            ->first();

        if (!$userNotification) {
            return response()->json([
                'status' => 'error',
                'message' => 'Notification not found'
            ], 404);
        }

        $userNotification->update(['is_read' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read',
            'data' => $userNotification
        ]);
    }

    /**
     *  Mark all notifications as read for the logged-in user
     */
    public function markAllMyNotificationsAsRead(Request $request)
    {
        $user = $request->user();

        $updatedCount = UserNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read',
            'data' => [
                'updated_count' => $updatedCount
            ]
        ]);
    }

    /**
     *  Get statistics for the logged-in user's notifications
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        $total = UserNotification::where('user_id', $user->id)->count();
        $unread = UserNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
        $read = UserNotification::where('user_id', $user->id)
            ->where('is_read', true)
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user->only(['id', 'user_name', 'email']),
                'total' => $total,
                'unread' => $unread,
                'read' => $read
            ]
        ]);
    }
}