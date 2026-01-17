<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Log an activity
     */
    public function log(array $data)
    {
        $logData = array_merge([
            'ip_address' => Request::ip(),
            'system_source' => 'SIP',
        ], $data);

        ActivityLog::create($logData);
    }

    /**
     * Log model changes
     */
    public function logModelChange($model, $action, $oldValue = null, $newValue = null, $userId = null)
    {
        $this->log([
            'user_id' => $userId ?? auth()->id(),
            'role' => auth()->user()->role ?? 'system',
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'old_value' => $oldValue ? json_encode($oldValue) : null,
            'new_value' => $newValue ? json_encode($newValue) : null,
            'description' => ucfirst($action) . ' ' . class_basename($model) . " #{$model->id}",
        ]);
    }
}

