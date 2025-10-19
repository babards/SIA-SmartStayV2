<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\WeatherService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WeatherAlertTest extends TestCase
{
    use RefreshDatabase;

    protected $weatherService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->weatherService = new WeatherService();
    }

    /** @test */
    public function it_can_detect_thunderstorm_alerts()
    {
        // Test with Manila coordinates
        $latitude = 14.6042;
        $longitude = 120.9822;

        $alerts = $this->weatherService->checkWeatherAlerts($latitude, $longitude);

        // Check if any alerts are found
        $this->assertIsArray($alerts);

        // If alerts are found, check the structure
        if (!empty($alerts)) {
            foreach ($alerts as $alert) {
                $this->assertArrayHasKey('type', $alert);
                $this->assertArrayHasKey('severity', $alert);
                $this->assertArrayHasKey('message', $alert);
                $this->assertArrayHasKey('description', $alert);
                $this->assertArrayHasKey('weather_data', $alert);

                // Check severity is valid
                $this->assertContains($alert['severity'], ['minor', 'moderate', 'severe']);

                // Check alert types
                $validTypes = [
                    'thunderstorm', 'thunderstorm_forecast',
                    'moderate_rain', 'moderate_rain_forecast',
                    'light_rain', 'light_rain_forecast',
                    'strong_wind', 'strong_wind_forecast',
                    'extreme_heat', 'extreme_heat_forecast',
                    'high_temperature'
                ];
                $this->assertContains($alert['type'], $validTypes);
            }
        }

        echo "Weather Alert Test Results:\n";
        echo "==========================\n";
        echo "Alerts found: " . count($alerts) . "\n";
        
        foreach ($alerts as $index => $alert) {
            echo ($index + 1) . ". " . strtoupper($alert['severity']) . " ALERT\n";
            echo "   Type: " . $alert['type'] . "\n";
            echo "   Message: " . $alert['message'] . "\n";
            echo "   Description: " . $alert['description'] . "\n";
            if (isset($alert['forecast_day'])) {
                echo "   Forecast Day: " . $alert['forecast_day'] . "\n";
            }
            echo "\n";
        }
    }

    /** @test */
    public function it_can_get_weather_data()
    {
        $latitude = 14.6042;
        $longitude = 120.9822;

        $weatherData = $this->weatherService->getWeatherData($latitude, $longitude);

        $this->assertIsArray($weatherData);
        $this->assertArrayHasKey('current', $weatherData);
        $this->assertArrayHasKey('forecast', $weatherData);
        $this->assertArrayHasKey('location', $weatherData);

        // Check current weather data structure
        $current = $weatherData['current'];
        $this->assertArrayHasKey('temperature', $current);
        $this->assertArrayHasKey('weather_code', $current);
        $this->assertArrayHasKey('weather_description', $current);
        $this->assertArrayHasKey('precipitation', $current);
        $this->assertArrayHasKey('precipitation_probability', $current);
        $this->assertArrayHasKey('wind_speed', $current);

        echo "Weather Data Test Results:\n";
        echo "=========================\n";
        echo "Current Weather:\n";
        echo "- Temperature: " . ($current['temperature'] ?? 'N/A') . "°C\n";
        echo "- Weather Code: " . ($current['weather_code'] ?? 'N/A') . "\n";
        echo "- Description: " . ($current['weather_description'] ?? 'N/A') . "\n";
        echo "- Precipitation: " . ($current['precipitation'] ?? 'N/A') . "mm\n";
        echo "- Precipitation Probability: " . ($current['precipitation_probability'] ?? 'N/A') . "%\n";
        echo "- Wind Speed: " . ($current['wind_speed'] ?? 'N/A') . " km/h\n\n";

        echo "Forecast (Next 4 Days):\n";
        $forecast = array_slice($weatherData['forecast'] ?? [], 0, 4);
        foreach ($forecast as $index => $day) {
            echo ($index + 1) . ". " . ($day['day_name'] ?? 'Unknown') . "\n";
            echo "   Weather Code: " . ($day['weather_code'] ?? 'N/A') . "\n";
            echo "   Description: " . ($day['weather_description'] ?? 'N/A') . "\n";
            echo "   Precipitation: " . ($day['precipitation'] ?? 'N/A') . "mm\n";
            echo "   Precipitation Probability: " . ($day['precipitation_probability'] ?? 'N/A') . "%\n";
            echo "   Max Temp: " . ($day['temp_max'] ?? 'N/A') . "°C\n";
            echo "   Wind Speed: " . ($day['wind_speed'] ?? 'N/A') . " km/h\n\n";
        }
    }

    /** @test */
    public function it_can_get_alert_recommendations()
    {
        $recommendations = $this->weatherService->getAlertRecommendations('thunderstorm', 'severe');
        
        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);

        echo "Alert Recommendations Test:\n";
        echo "===========================\n";
        echo "Thunderstorm (SEVERE) recommendations:\n";
        foreach ($recommendations as $index => $recommendation) {
            echo ($index + 1) . ". " . $recommendation . "\n";
        }
        echo "\n";
    }
}
