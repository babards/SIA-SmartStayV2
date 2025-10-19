<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\WeatherAlertController;
use App\Models\Property;
use Illuminate\Support\Facades\Log;

class SendWeatherAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weather:send-alerts {--property-id= : Send alert for specific property ID} {--test : Test mode - show what would be sent}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send proactive weather alerts to property owners about severe weather conditions';

    protected $weatherAlertController;

    /**
     * Create a new command instance.
     */
    public function __construct(WeatherAlertController $weatherAlertController)
    {
        parent::__construct();
        $this->weatherAlertController = $weatherAlertController;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🌤️  Starting weather alert system...');
        
        $propertyId = $this->option('property-id');
        $testMode = $this->option('test');

        if ($testMode) {
            $this->info('🧪 Running in TEST MODE - no emails will be sent');
        }

        try {
            if ($propertyId) {
                $this->handleSingleProperty($propertyId, $testMode);
            } else {
                $this->handleAllProperties($testMode);
            }
        } catch (\Exception $e) {
            $this->error('❌ Command failed: ' . $e->getMessage());
            Log::error('Weather alerts command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        $this->info('✅ Weather alert system completed successfully!');
        return 0;
    }

    /**
     * Handle weather alerts for a single property
     */
    private function handleSingleProperty($propertyId, $testMode)
    {
        $this->info("📍 Processing property ID: {$propertyId}");
        
        $property = Property::find($propertyId);
        if (!$property) {
            $this->error("❌ Property with ID {$propertyId} not found");
            return;
        }

        if (!$property->latitude || !$property->longitude) {
            $this->error("❌ Property {$property->propertyName} has no coordinates");
            return;
        }

        $this->info("🏠 Property: {$property->propertyName} at {$property->propertyLocation}");

        if ($testMode) {
            $this->showTestResults($property);
        } else {
            $result = $this->weatherAlertController->sendAlertForProperty($property);
            $this->displayResult($result, $property);
        }
    }

    /**
     * Handle weather alerts for all properties
     */
    private function handleAllProperties($testMode)
    {
        $this->info('🌍 Processing all properties...');
        
        $properties = Property::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('landlord')
            ->get();

        if ($properties->isEmpty()) {
            $this->warn('⚠️  No properties with coordinates found');
            return;
        }

        $this->info("📊 Found {$properties->count()} properties to process");

        $alertsSent = 0;
        $errors = 0;
        $noAlerts = 0;

        $progressBar = $this->output->createProgressBar($properties->count());
        $progressBar->start();

        foreach ($properties as $property) {
            try {
                if ($testMode) {
                    $this->showTestResults($property);
                } else {
                    $result = $this->weatherAlertController->sendAlertForProperty($property);
                    
                    if ($result['sent']) {
                        $alertsSent += $result['emails_sent'] ?? 1;
                    } elseif (isset($result['reason']) && str_contains($result['reason'], 'No alerts')) {
                        $noAlerts++;
                    } else {
                        $errors++;
                    }
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error('Weather alert error for property', [
                    'property_id' => $property->propertyID,
                    'property_name' => $property->propertyName,
                    'error' => $e->getMessage()
                ]);
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        if (!$testMode) {
            $this->displaySummary($alertsSent, $noAlerts, $errors, $properties->count());
        }
    }

    /**
     * Show test results for a property
     */
    private function showTestResults($property)
    {
        $this->newLine();
        $this->info("🧪 TEST MODE - Property: {$property->propertyName}");
        
        try {
            $response = $this->weatherAlertController->getWeatherAlertStatus($property->propertyID);
            $data = $response->getData(true)['data'] ?? [];
            
            if ($data['has_alerts']) {
                $this->warn("⚠️  ALERTS DETECTED:");
                foreach ($data['alerts'] as $alert) {
                    $this->line("   • {$alert['message']} (Severity: {$alert['severity']})");
                }
                $this->info("   📧 Email would be sent to: " . ($property->landlord->email ?: 'No email'));
            } else {
                $this->info("✅ No alerts detected - no email would be sent");
            }
            
            $this->line("   🌡️  Current: {$data['weather_data']['current']['temperature']}°C, {$data['weather_data']['current']['weather_description']}");
            
        } catch (\Exception $e) {
            $this->error("   ❌ Error checking alerts: " . $e->getMessage());
        }
    }

    /**
     * Display result for a single property
     */
    private function displayResult($result, $property)
    {
        $this->newLine();
        if ($result['sent']) {
            $emailsSent = $result['emails_sent'] ?? 1;
            $this->info("✅ Alert sent for {$property->propertyName} to {$emailsSent} recipient(s)");
            $this->line("   Type: {$result['alert_type']}, Severity: {$result['severity']}");
            if (!empty($result['errors'])) {
                $this->warn("   ⚠️  Some emails failed: " . implode(', ', $result['errors']));
            }
        } else {
            $this->warn("⚠️  No alert sent for {$property->propertyName}");
            $this->line("   Reason: {$result['reason']}");
        }
    }

    /**
     * Display summary of the batch operation
     */
    private function displaySummary($alertsSent, $noAlerts, $errors, $total)
    {
        $this->info('📊 SUMMARY:');
        $this->line("   Total properties processed: {$total}");
        $this->line("   Alerts sent: {$alertsSent}");
        $this->line("   No alerts detected: {$noAlerts}");
        $this->line("   Errors: {$errors}");
        
        if ($alertsSent > 0) {
            $this->info("✅ {$alertsSent} weather alert emails sent successfully!");
        } else {
            $this->info("ℹ️  No weather alerts were sent (no severe conditions detected)");
        }
        
        if ($errors > 0) {
            $this->warn("⚠️  {$errors} errors occurred - check logs for details");
        }
    }
}
