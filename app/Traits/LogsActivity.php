<?php

namespace App\Traits;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    protected function logActivity($action, $description, $userId = null)
    {
        // Use provided userId, or fall back to Auth::id(), or null if neither is available
        $userId = $userId ?? Auth::id();
        
        Log::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
        ]);
    }
} 