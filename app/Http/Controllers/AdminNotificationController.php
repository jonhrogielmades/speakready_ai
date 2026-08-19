<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Models\Announcement;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Auth;

class AdminNotificationController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('sender', 'user')
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'announcements_page')
            ->withQueryString();

        $activities = ActivityLog::with('user')
            ->orderBy('id', 'desc')
            ->paginate(25, ['*'], 'activities_page')
            ->withQueryString();

        $activityCount = ActivityLog::count();
        $unreadActivityCount = ActivityLog::whereNull('read_at')->count();
        $globalBroadcastCount = Announcement::where('target', 'all')->count();
        $directAlertCount = Announcement::where('target', 'specific')->count();
        $announcementCount = Announcement::count();
            
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        
        return $this->mobileView('admin.notifications', compact(
            'announcements',
            'users',
            'activities',
            'activityCount',
            'unreadActivityCount',
            'globalBroadcastCount',
            'directAlertCount',
            'announcementCount'
        ));
    }

    public function store(Request $request)
    {
        if (! Setting::enabled('notif_sys')) {
            return redirect()
                ->route('admin.notifications.index')
                ->with('error', 'System notifications are disabled in admin settings.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:info,success,warning,danger',
            'target' => 'required|in:all,specific',
            'user_id' => 'required_if:target,specific|nullable|exists:users,id'
        ]);

        $announcement = Announcement::create([
            'title' => $request->title,
            'message' => $request->message,
            'type' => $request->type,
            'target' => $request->target,
            'user_id' => $request->target === 'specific' ? $request->user_id : null,
            'sent_by' => Auth::id()
        ]);

        $notification = new SystemNotification($request->title, $request->message, $request->type);

        if ($request->target === 'all') {
            $users = User::all();
            foreach ($users as $user) {
                $user->notify($notification);
            }
        } else {
            $user = User::find($request->user_id);
            if ($user) {
                $user->notify($notification);
            }
        }

        return redirect()->route('admin.notifications.index')->with('success', 'Notification sent successfully.');
    }

    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();
        
        return redirect()->route('admin.notifications.index')->with('success', 'Announcement record deleted.');
    }
}
