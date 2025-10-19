<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\User;
use App\Services\WeatherService;
use App\Mail\WeatherAlertMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WeatherAlertController extends Controller
{
    protected $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * Send weather alerts for all properties
     */
    public function sendWeatherAlerts()
    {
        try {
            $properties = Property::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->with('landlord')
                ->get();

            $alertsSent = 0;
            $errors = [];

            foreach ($properties as $property) {
                try {
                    $result = $this->sendAlertForProperty($property);
                    if ($result['sent']) {
                        $alertsSent++;
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'property_id' => $property->propertyID,
                        'property_name' => $property->propertyName,
                        'error' => $e->getMessage()
                    ];
                    Log::error('Weather alert error for property', [
                        'property_id' => $property->propertyID,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Weather alerts batch completed', [
                'total_properties' => $properties->count(),
                'alerts_sent' => $alertsSent,
                'errors' => count($errors)
            ]);

            return response()->json([
                'success' => true,
                'message' => "Weather alerts processed. {$alertsSent} alerts sent.",
                'data' => [
                    'total_properties' => $properties->count(),
                    'alerts_sent' => $alertsSent,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Weather alerts batch failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process weather alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send weather alert for a specific property
     */
    public function sendAlertForProperty(Property $property)
    {
        // Daily limits removed for testing phase - admin has full freedom to send alerts

        // Get weather data and check for alerts
        $weatherData = $this->weatherService->getWeatherData(
            $property->latitude,
            $property->longitude
        );

        $alerts = $this->weatherService->checkWeatherAlerts(
            $property->latitude,
            $property->longitude
        );

        if (empty($alerts)) {
            return ['sent' => false, 'reason' => 'No alerts detected'];
        }

        // Get the highest severity alert
        $highestSeverityAlert = $this->getHighestSeverityAlert($alerts);
        
        // Generate severity summary
        $severitySummary = $this->generateSeveritySummary($alerts);
        
        // Enhance forecast with severity indicators
        $enhancedForecast = $this->enhanceForecastWithSeverity($weatherData['forecast'] ?? [], $alerts);
        
        // Prepare alert data
        $alertData = array_merge($highestSeverityAlert, [
            'weather_description' => $weatherData['current']['weather_description'] ?? 'Unknown',
            'weather_icon' => $weatherData['current']['weather_icon'] ?? '❓',
            'temperature' => $weatherData['current']['temperature'] ?? 0,
            'precipitation' => $weatherData['current']['precipitation'] ?? 0,
            'precipitation_probability' => $weatherData['current']['precipitation_probability'] ?? 0,
            'wind_speed' => $weatherData['current']['wind_speed'] ?? 0,
            'humidity' => $weatherData['current']['humidity'] ?? 0,
            'forecast' => array_slice($enhancedForecast, 0, 4),
            'severity_summary' => $severitySummary,
            'all_alerts' => $alerts
        ]);

        // Send emails to both landlord and tenants
        $emailsSent = 0;
        $errors = [];

        // Send email to landlord
        if ($property->landlord && $property->landlord->email) {
            try {
                Mail::to($property->landlord->email)->send(
                    new WeatherAlertMail($alertData, $property, $property->landlord, 'landlord')
                );
                $emailsSent++;

                Log::info('Weather alert sent to landlord', [
                    'property_id' => $property->propertyID,
                    'property_name' => $property->propertyName,
                    'landlord_email' => $property->landlord->email,
                    'alert_type' => $highestSeverityAlert['type'],
                    'severity' => $highestSeverityAlert['severity']
                ]);

            } catch (\Exception $e) {
                $errors[] = 'Landlord email failed: ' . $e->getMessage();
                Log::error('Failed to send weather alert email to landlord', [
                    'property_id' => $property->propertyID,
                    'landlord_email' => $property->landlord->email,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Send emails to active tenants
        $activeTenants = $property->boarders()
            ->where('status', 'active')
            ->with('tenant')
            ->get();

        foreach ($activeTenants as $boarder) {
            if ($boarder->tenant && $boarder->tenant->email) {
                try {
                    Mail::to($boarder->tenant->email)->send(
                        new WeatherAlertMail($alertData, $property, $boarder->tenant, 'tenant')
                    );
                    $emailsSent++;

                    Log::info('Weather alert sent to tenant', [
                        'property_id' => $property->propertyID,
                        'property_name' => $property->propertyName,
                        'tenant_email' => $boarder->tenant->email,
                        'tenant_name' => $boarder->tenant->first_name . ' ' . $boarder->tenant->last_name,
                        'alert_type' => $highestSeverityAlert['type'],
                        'severity' => $highestSeverityAlert['severity']
                    ]);

                } catch (\Exception $e) {
                    $errors[] = 'Tenant email failed: ' . $e->getMessage();
                    Log::error('Failed to send weather alert email to tenant', [
                        'property_id' => $property->propertyID,
                        'tenant_email' => $boarder->tenant->email,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Daily limits removed for testing phase - no cache restrictions

        if ($emailsSent > 0) {
            return [
                'sent' => true,
                'alert_type' => $highestSeverityAlert['type'],
                'severity' => $highestSeverityAlert['severity'],
                'message' => "Alert sent successfully to {$emailsSent} recipient(s)",
                'emails_sent' => $emailsSent,
                'errors' => $errors
            ];
        }

        return [
            'sent' => false,
            'reason' => 'No valid email addresses found for landlord or tenants',
            'errors' => $errors
        ];
    }

    /**
     * Get the highest severity alert from a list of alerts
     */
    private function getHighestSeverityAlert(array $alerts): array
    {
        $severityLevels = ['minor' => 1, 'moderate' => 2, 'severe' => 3];
        $highestSeverity = 'minor';
        $highestAlert = $alerts[0] ?? [];

        foreach ($alerts as $alert) {
            $currentLevel = $severityLevels[$alert['severity']] ?? 1;
            $highestLevel = $severityLevels[$highestSeverity] ?? 1;

            if ($currentLevel > $highestLevel) {
                $highestSeverity = $alert['severity'];
                $highestAlert = $alert;
            }
        }

        return $highestAlert;
    }

    /**
     * Generate severity summary from all alerts
     */
    private function generateSeveritySummary(array $alerts): array
    {
        $summary = [
            'severe' => [],
            'moderate' => [],
            'minor' => []
        ];

        foreach ($alerts as $alert) {
            $severity = $alert['severity'] ?? 'minor';
            $type = $alert['type'] ?? 'unknown';
            $forecastDay = $alert['forecast_day'] ?? null;

            if (!isset($summary[$severity][$type])) {
                $summary[$severity][$type] = [];
            }

            if ($forecastDay) {
                $summary[$severity][$type][] = $forecastDay;
            } else {
                $summary[$severity][$type][] = 'current';
            }
        }

        return $summary;
    }

    /**
     * Enhance forecast with severity indicators
     */
    private function enhanceForecastWithSeverity(array $forecast, array $alerts): array
    {
        // Create a map of forecast days to their severities
        $daySeverities = [];
        foreach ($alerts as $alert) {
            if (isset($alert['forecast_day'])) {
                $daySeverities[$alert['forecast_day']] = $alert['severity'];
            }
        }

        // Enhance each forecast day with severity information
        foreach ($forecast as &$day) {
            $dayName = $day['day_name'] ?? '';
            $day['severity'] = $daySeverities[$dayName] ?? 'normal';
            $day['severity_icon'] = $this->getSeverityIcon($day['severity']);
            $day['severity_color'] = $this->getSeverityColor($day['severity']);
        }

        return $forecast;
    }

    /**
     * Get severity icon
     */
    private function getSeverityIcon(string $severity): string
    {
        return match($severity) {
            'severe' => '🔴',
            'moderate' => '🟡',
            'minor' => '🔵',
            default => '⚪'
        };
    }

    /**
     * Get severity color
     */
    private function getSeverityColor(string $severity): string
    {
        return match($severity) {
            'severe' => '#f44336',
            'moderate' => '#ff9800',
            'minor' => '#2196f3',
            default => '#666666'
        };
    }

    /**
     * Test weather alert for a specific property (for testing purposes)
     */
    public function testWeatherAlert(Request $request, $propertyId)
    {
        try {
            $property = Property::findOrFail($propertyId);
            
            if (!$property->latitude || !$property->longitude) {
                return response()->json([
                    'success' => false,
                    'message' => 'Property coordinates not available'
                ], 400);
            }

            $result = $this->sendAlertForProperty($property);

            return response()->json([
                'success' => true,
                'message' => 'Test alert processed',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Test weather alert failed', [
                'property_id' => $propertyId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test alert failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get weather alert status for a property
     */
    public function getWeatherAlertStatus($propertyId)
    {
        try {
            $property = Property::findOrFail($propertyId);
            
            if (!$property->latitude || !$property->longitude) {
                return response()->json([
                    'success' => false,
                    'message' => 'Property coordinates not available'
                ], 400);
            }

            $alerts = $this->weatherService->checkWeatherAlerts(
                $property->latitude,
                $property->longitude
            );

            $weatherData = $this->weatherService->getWeatherData(
                $property->latitude,
                $property->longitude
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'property' => [
                        'id' => $property->propertyID,
                        'name' => $property->propertyName,
                        'location' => $property->propertyLocation
                    ],
                    'alerts' => $alerts,
                    'weather_data' => $weatherData,
                    'alert_count' => count($alerts),
                    'has_alerts' => !empty($alerts),
                    'last_checked' => now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Weather alert status check failed', [
                'property_id' => $propertyId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check weather alert status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get weather alert recommendations
     */
    public function getWeatherAlertRecommendations(Request $request)
    {
        $request->validate([
            'alert_type' => 'required|string',
            'severity' => 'required|string|in:minor,moderate,severe'
        ]);

        try {
            $recommendations = $this->weatherService->getAlertRecommendations(
                $request->input('alert_type'),
                $request->input('severity')
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'alert_type' => $request->input('alert_type'),
                    'severity' => $request->input('severity'),
                    'recommendations' => $recommendations
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Weather alert recommendations failed', [
                'alert_type' => $request->input('alert_type'),
                'severity' => $request->input('severity'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get recommendations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin method to manually trigger weather alerts for all properties
     */
    public function sendAllWeatherAlerts()
    {
        try {
            $properties = Property::with(['landlord', 'boarders.tenant'])->get();
            $results = [];
            $totalAlertsSent = 0;
            $totalEmailsSent = 0;
            $errors = 0;

            foreach ($properties as $property) {
                $result = $this->sendAlertForProperty($property);
                $results[] = [
                    'property_id' => $property->propertyID,
                    'property_name' => $property->propertyName,
                    'result' => $result
                ];

                if ($result['sent']) {
                    $totalAlertsSent++;
                    $totalEmailsSent += $result['emails_sent'] ?? 1;
                } elseif (!isset($result['reason']) || !str_contains($result['reason'], 'No alerts')) {
                    $errors++;
                }
            }

            Log::info('Admin manually triggered weather alerts', [
                'total_properties' => $properties->count(),
                'alerts_sent' => $totalAlertsSent,
                'emails_sent' => $totalEmailsSent,
                'errors' => $errors
            ]);

            return response()->json([
                'success' => true,
                'message' => "Weather alerts processed successfully",
                'summary' => [
                    'total_properties' => $properties->count(),
                    'alerts_sent' => $totalAlertsSent,
                    'emails_sent' => $totalEmailsSent,
                    'errors' => $errors
                ],
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Admin failed to trigger weather alerts', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send weather alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}
