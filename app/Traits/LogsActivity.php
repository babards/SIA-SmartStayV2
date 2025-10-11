<?php

namespace App\Traits;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    protected function logActivity($action, $description)
    {
        Log::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip()
        ]);
    }
} 