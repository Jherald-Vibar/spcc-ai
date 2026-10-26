<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Login;

/**
 * Laravel auto-discovers this listener (handle() is type-hinted with Login).
 * One "login" row per user per day feeds the admin "System Usage" chart.
 */
class RecordLogin
{
    public function handle(Login $event): void
    {
        $exists = ActivityLog::where('type', 'login')
            ->where('user_id', $event->user->getAuthIdentifier())
            ->whereDate('created_at', today())
            ->exists();

        if (! $exists) {
            ActivityLog::create([
                'user_id' => $event->user->getAuthIdentifier(),
                'type' => 'login',
                'title' => 'Signed in',
            ]);
        }
    }
}
