<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WeatherAlertSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'property_id',
        'enabled',
        'min_severity',
        'alert_types',
        'quiet_hours_start',
        'quiet_hours_end',
        'max_alerts_per_day',
        'last_alert_sent'
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'alert_types' => 'array',
        'quiet_hours_start' => 'datetime:H:i',
        'quiet_hours_end' => 'datetime:H:i',
        'last_alert_sent' => 'datetime'
    ];

    // Relationship to user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship to property
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id', 'propertyID');
    }

    /**
     * Check if alerts are currently allowed (not in quiet hours)
     */
    public function isAlertAllowed(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // Check quiet hours
        if ($this->quiet_hours_start && $this->quiet_hours_end) {
            $currentTime = now()->format('H:i');
            $startTime = $this->quiet_hours_start->format('H:i');
            $endTime = $this->quiet_hours_end->format('H:i');

            // Handle overnight quiet hours (e.g., 22:00 to 08:00)
            if ($startTime > $endTime) {
                if ($currentTime >= $startTime || $currentTime <= $endTime) {
                    return false;
                }
            } else {
                // Normal quiet hours (e.g., 22:00 to 08:00)
                if ($currentTime >= $startTime && $currentTime <= $endTime) {
                    return false;
                }
            }
        }

        // Check daily alert limit
        if ($this->last_alert_sent && $this->last_alert_sent->isToday()) {
            // This would need to be tracked separately, but for now we'll allow
            // In a full implementation, you'd track daily alert counts
        }

        return true;
    }

    /**
     * Check if a specific alert type is enabled
     */
    public function isAlertTypeEnabled(string $alertType): bool
    {
        if (!$this->alert_types) {
            return true; // All alert types enabled if none specified
        }

        return in_array($alertType, $this->alert_types);
    }

    /**
     * Check if severity meets minimum requirement
     */
    public function meetsSeverityRequirement(string $severity): bool
    {
        $severityLevels = ['minor' => 1, 'moderate' => 2, 'severe' => 3];
        $currentLevel = $severityLevels[$severity] ?? 1;
        $minLevel = $severityLevels[$this->min_severity] ?? 1;

        return $currentLevel >= $minLevel;
    }
}
