<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    private const API_BASE_URL = 'https://api.open-meteo.com/v1/forecast';
    private const CACHE_DURATION = 300; // 5 minutes cache
    private const TIMEZONE = 'Asia/Manila';
    private const TIMEZONE_ABBREV = 'PST';

    /**
     * Get current weather data for given coordinates
     */
    public function getCurrentWeather(float $latitude, float $longitude): array
    {
        $cacheKey = "weather_current_{$latitude}_{$longitude}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($latitude, $longitude) {
            try {
                $response = Http::timeout(10)->get(self::API_BASE_URL, [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'current' => 'temperature_2m,relative_humidity_2m,precipitation,wind_speed_10m,wind_direction_10m,weather_code,cloud_cover,precipitation_probability',
                    'timezone' => self::TIMEZONE,
                    'timezone_abbreviation' => self::TIMEZONE_ABBREV
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $this->formatCurrentWeatherData($data);
                }

                Log::error('Weather API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'coordinates' => [$latitude, $longitude]
                ]);

                return $this->getDefaultWeatherData();
            } catch (\Exception $e) {
                Log::error('Weather API exception', [
                    'message' => $e->getMessage(),
                    'coordinates' => [$latitude, $longitude]
                ]);

                return $this->getDefaultWeatherData();
            }
        });
    }

    /**
     * Get 7-day forecast for given coordinates
     */
    public function getForecast(float $latitude, float $longitude): array
    {
        $cacheKey = "weather_forecast_{$latitude}_{$longitude}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($latitude, $longitude) {
            try {
                $response = Http::timeout(10)->get(self::API_BASE_URL, [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_sum,wind_speed_10m_max,weather_code,precipitation_probability_max',
                    'timezone' => self::TIMEZONE,
                    'timezone_abbreviation' => self::TIMEZONE_ABBREV,
                    'forecast_days' => 7
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $this->formatForecastData($data);
                }

                Log::error('Weather forecast API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'coordinates' => [$latitude, $longitude]
                ]);

                return $this->getDefaultForecastData();
            } catch (\Exception $e) {
                Log::error('Weather forecast API exception', [
                    'message' => $e->getMessage(),
                    'coordinates' => [$latitude, $longitude]
                ]);

                return $this->getDefaultForecastData();
            }
        });
    }

    /**
     * Get complete weather data (current + forecast)
     */
    public function getWeatherData(float $latitude, float $longitude): array
    {
        $current = $this->getCurrentWeather($latitude, $longitude);
        $forecast = $this->getForecast($latitude, $longitude);

        return [
            'current' => $current,
            'forecast' => $forecast,
            'location' => [
                'latitude' => $latitude,
                'longitude' => $longitude
            ],
            'last_updated' => now()->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Format current weather data from API response
     */
    private function formatCurrentWeatherData(array $data): array
    {
        $current = $data['current'] ?? [];
        
        return [
            'temperature' => round($current['temperature_2m'] ?? 0),
            'humidity' => round($current['relative_humidity_2m'] ?? 0),
            'precipitation' => round($current['precipitation'] ?? 0, 1),
            'precipitation_probability' => round($current['precipitation_probability'] ?? 0),
            'wind_speed' => round($current['wind_speed_10m'] ?? 0),
            'wind_direction' => $current['wind_direction_10m'] ?? 0,
            'weather_code' => $current['weather_code'] ?? 0,
            'cloud_cover' => round($current['cloud_cover'] ?? 0),
            'weather_description' => $this->getWeatherDescription($current['weather_code'] ?? 0),
            'weather_icon' => $this->getWeatherIcon($current['weather_code'] ?? 0),
            'weather_color' => $this->getWeatherColor($current['weather_code'] ?? 0)
        ];
    }

    /**
     * Format forecast data from API response
     */
    private function formatForecastData(array $data): array
    {
        $daily = $data['daily'] ?? [];
        $forecast = [];

        if (isset($daily['time']) && is_array($daily['time'])) {
            for ($i = 0; $i < min(7, count($daily['time'])); $i++) {
                $forecast[] = [
                    'date' => $daily['time'][$i] ?? '',
                    'day_name' => $this->getDayName($daily['time'][$i] ?? ''),
                    'temp_max' => round($daily['temperature_2m_max'][$i] ?? 0),
                    'temp_min' => round($daily['temperature_2m_min'][$i] ?? 0),
                    'precipitation' => round($daily['precipitation_sum'][$i] ?? 0, 1),
                    'wind_speed' => round($daily['wind_speed_10m_max'][$i] ?? 0),
                    'weather_code' => $daily['weather_code'][$i] ?? 0,
                    'precipitation_probability' => round($daily['precipitation_probability_max'][$i] ?? 0),
                    'weather_description' => $this->getWeatherDescription($daily['weather_code'][$i] ?? 0),
                    'weather_icon' => $this->getWeatherIcon($daily['weather_code'][$i] ?? 0),
                    'weather_color' => $this->getWeatherColor($daily['weather_code'][$i] ?? 0)
                ];
            }
        }

        return $forecast;
    }

    /**
     * Get weather description based on WMO weather code
     */
    private function getWeatherDescription(int $code): string
    {
        $descriptions = [
            0 => 'Clear sky',
            1 => 'Mainly clear',
            2 => 'Partly cloudy',
            3 => 'Overcast',
            45 => 'Fog',
            48 => 'Depositing rime fog',
            51 => 'Light drizzle',
            53 => 'Moderate drizzle',
            55 => 'Dense drizzle',
            56 => 'Light freezing drizzle',
            57 => 'Dense freezing drizzle',
            61 => 'Slight rain',
            63 => 'Moderate rain',
            65 => 'Heavy rain',
            66 => 'Light freezing rain',
            67 => 'Heavy freezing rain',
            71 => 'Slight snow fall',
            73 => 'Moderate snow fall',
            75 => 'Heavy snow fall',
            77 => 'Snow grains',
            80 => 'Slight rain showers',
            81 => 'Moderate rain showers',
            82 => 'Violent rain showers',
            85 => 'Slight snow showers',
            86 => 'Heavy snow showers',
            95 => 'Thunderstorm',
            96 => 'Thunderstorm with slight hail',
            99 => 'Thunderstorm with heavy hail'
        ];

        return $descriptions[$code] ?? 'Unknown';
    }

    /**
     * Get weather icon based on WMO weather code
     */
    private function getWeatherIcon(int $code): string
    {
        $icons = [
            0 => '☀️',
            1 => '⛅',
            2 => '⛅',
            3 => '☁️',
            45 => '🌫️',
            48 => '🌫️',
            51 => '🌧️',
            53 => '🌧️',
            55 => '🌧️',
            56 => '🌧️',
            57 => '🌧️',
            61 => '🌧️',
            63 => '🌧️',
            65 => '🌧️',
            66 => '🌧️',
            67 => '🌧️',
            71 => '❄️',
            73 => '❄️',
            75 => '❄️',
            77 => '❄️',
            80 => '🌧️',
            81 => '🌧️',
            82 => '🌧️',
            85 => '❄️',
            86 => '❄️',
            95 => '⚡',
            96 => '⚡',
            99 => '⚡'
        ];

        return $icons[$code] ?? '❓';
    }

    /**
     * Get weather color based on WMO weather code
     */
    private function getWeatherColor(int $code): string
    {
        $colors = [
            0 => '#FFA500', // Orange for clear
            1 => '#87CEEB', // Light blue for mainly clear
            2 => '#808080', // Grey for partly cloudy
            3 => '#808080', // Grey for overcast
            45 => '#808080', // Grey for fog
            48 => '#808080', // Grey for fog
            51 => '#4169E1', // Blue for drizzle
            53 => '#4169E1', // Blue for drizzle
            55 => '#4169E1', // Blue for drizzle
            56 => '#4169E1', // Blue for freezing drizzle
            57 => '#4169E1', // Blue for freezing drizzle
            61 => '#4169E1', // Blue for rain
            63 => '#4169E1', // Blue for rain
            65 => '#4169E1', // Blue for rain
            66 => '#4169E1', // Blue for freezing rain
            67 => '#4169E1', // Blue for freezing rain
            71 => '#B0E0E6', // Light blue for snow
            73 => '#B0E0E6', // Light blue for snow
            75 => '#B0E0E6', // Light blue for snow
            77 => '#B0E0E6', // Light blue for snow grains
            80 => '#4169E1', // Blue for rain showers
            81 => '#4169E1', // Blue for rain showers
            82 => '#4169E1', // Blue for rain showers
            85 => '#B0E0E6', // Light blue for snow showers
            86 => '#B0E0E6', // Light blue for snow showers
            95 => '#FF4500', // Deep orange for thunderstorm
            96 => '#FF4500', // Deep orange for thunderstorm
            99 => '#FF4500'  // Deep orange for thunderstorm
        ];

        return $colors[$code] ?? '#808080';
    }

    /**
     * Get day name from date string
     */
    private function getDayName(string $date): string
    {
        try {
            $timestamp = strtotime($date);
            return date('D j', $timestamp);
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Get default weather data when API fails
     */
    private function getDefaultWeatherData(): array
    {
        return [
            'temperature' => 25,
            'humidity' => 70,
            'precipitation' => 0.0,
            'precipitation_probability' => 10,
            'wind_speed' => 5,
            'wind_direction' => 180,
            'weather_code' => 1,
            'cloud_cover' => 25,
            'weather_description' => 'Mainly clear',
            'weather_icon' => '⛅',
            'weather_color' => '#87CEEB'
        ];
    }

    /**
     * Get default forecast data when API fails
     */
    private function getDefaultForecastData(): array
    {
        $forecast = [];
        for ($i = 0; $i < 7; $i++) {
            $date = now()->addDays($i)->format('Y-m-d');
            $forecast[] = [
                'date' => $date,
                'day_name' => $this->getDayName($date),
                'temp_max' => 28 + rand(-3, 5),
                'temp_min' => 20 + rand(-2, 3),
                'precipitation' => rand(0, 5) / 10,
                'wind_speed' => rand(3, 8),
                'weather_code' => rand(0, 3),
                'precipitation_probability' => rand(0, 30),
                'weather_description' => $this->getWeatherDescription(rand(0, 3)),
                'weather_icon' => $this->getWeatherIcon(rand(0, 3)),
                'weather_color' => $this->getWeatherColor(rand(0, 3))
            ];
        }
        return $forecast;
    }
}
