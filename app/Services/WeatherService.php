<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    private const API_BASE_URL = 'https://api.open-meteo.com/v1/forecast';
    private const CACHE_DURATION = 900; // 15 minutes cache (improved from 5 minutes)
    private const TIMEZONE = 'Asia/Manila';
    private const TIMEZONE_ABBREV = 'PST';
    
    // Philippines coordinate boundaries (project scope)
    // Philippines is located approximately between 4°23' and 21°7' North latitude,
    // and 116°55' and 126°37' East longitude
    private const PH_LAT_MIN = 4.0;
    private const PH_LAT_MAX = 21.0;
    private const PH_LON_MIN = 116.0;
    private const PH_LON_MAX = 127.0;

    /**
     * Get current weather data for given coordinates
     */
    public function getCurrentWeather(float $latitude, float $longitude): array
    {
        // Validate coordinates are within Philippines boundaries
        if (!$this->validateCoordinates($latitude, $longitude)) {
            Log::error('Invalid coordinates provided for weather data - outside Philippines', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'ph_bounds' => [
                    'lat_min' => self::PH_LAT_MIN,
                    'lat_max' => self::PH_LAT_MAX,
                    'lon_min' => self::PH_LON_MIN,
                    'lon_max' => self::PH_LON_MAX
                ]
            ]);
            return $this->getDefaultWeatherData($latitude, $longitude);
        }

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

                return $this->getDefaultWeatherData($latitude, $longitude);
            } catch (\Exception $e) {
                Log::error('Weather API exception', [
                    'message' => $e->getMessage(),
                    'coordinates' => [$latitude, $longitude]
                ]);

                return $this->getDefaultWeatherData($latitude, $longitude);
            }
        });
    }

    /**
     * Get 7-day forecast for given coordinates
     */
    public function getForecast(float $latitude, float $longitude): array
    {
        // Validate coordinates are within Philippines boundaries
        if (!$this->validateCoordinates($latitude, $longitude)) {
            Log::error('Invalid coordinates provided for weather forecast - outside Philippines', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'ph_bounds' => [
                    'lat_min' => self::PH_LAT_MIN,
                    'lat_max' => self::PH_LAT_MAX,
                    'lon_min' => self::PH_LON_MIN,
                    'lon_max' => self::PH_LON_MAX
                ]
            ]);
            return $this->getDefaultForecastData($latitude, $longitude);
        }

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

                return $this->getDefaultForecastData($latitude, $longitude);
            } catch (\Exception $e) {
                Log::error('Weather forecast API exception', [
                    'message' => $e->getMessage(),
                    'coordinates' => [$latitude, $longitude]
                ]);

                return $this->getDefaultForecastData($latitude, $longitude);
            }
        });
    }

    /**
     * Get historical weather data for past days
     */
    public function getHistoricalWeather(float $latitude, float $longitude, int $days = 4): array
    {
        // Validate coordinates are within Philippines boundaries
        if (!$this->validateCoordinates($latitude, $longitude)) {
            Log::error('Invalid coordinates provided for historical weather data - outside Philippines', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'days' => $days
            ]);
            return $this->getDefaultHistoricalData($latitude, $longitude, $days);
        }

        $cacheKey = "weather_historical_{$latitude}_{$longitude}_{$days}";
        
        return Cache::remember($cacheKey, 3600, function () use ($latitude, $longitude, $days) {
            try {
                $response = Http::timeout(10)->get(self::API_BASE_URL, [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_sum,weather_code',
                    'start_date' => now()->subDays($days)->format('Y-m-d'),
                    'end_date' => now()->subDay()->format('Y-m-d'),
                    'timezone' => self::TIMEZONE
                ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    return $this->formatHistoricalData($data);
                }
                
                Log::warning('Historical weather API returned non-successful response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'coordinates' => [$latitude, $longitude]
                ]);
                
                return $this->getDefaultHistoricalData($latitude, $longitude, $days);
            } catch (\Exception $e) {
                Log::error('Historical weather API exception', [
                    'message' => $e->getMessage(),
                    'coordinates' => [$latitude, $longitude]
                ]);
                
                return $this->getDefaultHistoricalData($latitude, $longitude, $days);
            }
        });
    }

    /**
     * Get complete weather data (current + forecast + historical)
     */
    public function getWeatherData(float $latitude, float $longitude): array
    {
        $current = $this->getCurrentWeather($latitude, $longitude);
        $forecast = $this->getForecast($latitude, $longitude);
        $historical = $this->getHistoricalWeather($latitude, $longitude, 4);
        $isWithinPhilippines = $this->validateCoordinates($latitude, $longitude);

        return [
            'current' => $current,
            'forecast' => $forecast,
            'historical' => $historical,
            'location' => [
                'latitude' => $latitude,
                'longitude' => $longitude
            ],
            'scope' => [
                'within_philippines' => $isWithinPhilippines,
                'scope_status' => $isWithinPhilippines ? 'within_scope' : 'outside_scope',
                'scope_message' => $isWithinPhilippines 
                    ? 'Property is within Philippines scope' 
                    : 'Property is outside Philippines scope - default weather data provided'
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
     * Validate coordinates are within Philippines boundaries
     */
    private function validateCoordinates(float $latitude, float $longitude): bool
    {
        return $latitude >= self::PH_LAT_MIN && 
               $latitude <= self::PH_LAT_MAX && 
               $longitude >= self::PH_LON_MIN && 
               $longitude <= self::PH_LON_MAX;
    }

    /**
     * Get location-specific default weather data when API fails
     */
    private function getDefaultWeatherData(float $latitude, float $longitude): array
    {
        // Determine climate zone based on coordinates
        $climateData = $this->getClimateZoneData($latitude, $longitude);
        
        return [
            'temperature' => $climateData['temperature'],
            'humidity' => $climateData['humidity'],
            'precipitation' => $climateData['precipitation'],
            'precipitation_probability' => $climateData['precipitation_probability'],
            'wind_speed' => $climateData['wind_speed'],
            'wind_direction' => $climateData['wind_direction'],
            'weather_code' => $climateData['weather_code'],
            'cloud_cover' => $climateData['cloud_cover'],
            'weather_description' => $climateData['weather_description'],
            'weather_icon' => $climateData['weather_icon'],
            'weather_color' => $climateData['weather_color']
        ];
    }

    /**
     * Get location-specific default forecast data when API fails
     */
    private function getDefaultForecastData(float $latitude, float $longitude): array
    {
        $climateData = $this->getClimateZoneData($latitude, $longitude);
        $forecast = [];
        
        for ($i = 0; $i < 7; $i++) {
            $date = now()->addDays($i)->format('Y-m-d');
            $forecast[] = [
                'date' => $date,
                'day_name' => $this->getDayName($date),
                'temp_max' => $climateData['temperature'] + rand(-2, 3),
                'temp_min' => $climateData['temperature'] - rand(3, 6),
                'precipitation' => rand(0, $climateData['precipitation'] * 2) / 10,
                'wind_speed' => $climateData['wind_speed'] + rand(-2, 3),
                'weather_code' => $climateData['weather_code'],
                'precipitation_probability' => $climateData['precipitation_probability'] + rand(-10, 20),
                'weather_description' => $climateData['weather_description'],
                'weather_icon' => $climateData['weather_icon'],
                'weather_color' => $climateData['weather_color']
            ];
        }
        return $forecast;
    }

    /**
     * Get Philippines climate zone data based on coordinates
     * Philippines has a tropical climate with regional variations
     * Enhanced with more granular location-specific data
     */
    private function getClimateZoneData(float $latitude, float $longitude): array
    {
        // Add some variation based on longitude and micro-climate factors
        $longitudeVariation = ($longitude - 120) * 0.1; // Small variation based on longitude
        $latitudeVariation = ($latitude - 10) * 0.2;    // Small variation based on latitude
        
        // Northern Philippines (Luzon) - generally cooler, more seasonal variation
        if ($latitude >= 12.0) {
            $baseTemp = 26;
            $baseHumidity = 75;
            $basePrecip = 0.2;
            
            // Add micro-climate variations
            $tempVariation = round($longitudeVariation + $latitudeVariation, 1);
            $humidityVariation = round($longitudeVariation * 2, 0);
            $precipVariation = round($longitudeVariation * 0.1, 1);
            
            return [
                'temperature' => max(22, min(30, $baseTemp + $tempVariation)),
                'humidity' => max(65, min(85, $baseHumidity + $humidityVariation)),
                'precipitation' => max(0.0, min(1.0, $basePrecip + $precipVariation)),
                'precipitation_probability' => max(15, min(40, 25 + round($precipVariation * 20))),
                'wind_speed' => max(5, min(12, 8 + round($longitudeVariation))),
                'wind_direction' => 180 + round($longitudeVariation * 10),
                'weather_code' => $this->getVariedWeatherCode(2, $longitude, $latitude),
                'cloud_cover' => max(30, min(60, 40 + round($longitudeVariation * 5))),
                'weather_description' => $this->getVariedWeatherDescription(2, $longitude, $latitude),
                'weather_icon' => $this->getVariedWeatherIcon(2, $longitude, $latitude),
                'weather_color' => $this->getVariedWeatherColor(2, $longitude, $latitude)
            ];
        }
        
        // Central Philippines (Visayas) - moderate tropical climate
        if ($latitude >= 8.0) {
            $baseTemp = 28;
            $baseHumidity = 80;
            $basePrecip = 0.5;
            
            // Add micro-climate variations
            $tempVariation = round($longitudeVariation + $latitudeVariation, 1);
            $humidityVariation = round($longitudeVariation * 2, 0);
            $precipVariation = round($longitudeVariation * 0.15, 1);
            
            return [
                'temperature' => max(24, min(32, $baseTemp + $tempVariation)),
                'humidity' => max(70, min(90, $baseHumidity + $humidityVariation)),
                'precipitation' => max(0.1, min(1.5, $basePrecip + $precipVariation)),
                'precipitation_probability' => max(25, min(50, 35 + round($precipVariation * 15))),
                'wind_speed' => max(4, min(10, 6 + round($longitudeVariation))),
                'wind_direction' => 200 + round($longitudeVariation * 8),
                'weather_code' => $this->getVariedWeatherCode(3, $longitude, $latitude),
                'cloud_cover' => max(50, min(75, 60 + round($longitudeVariation * 3))),
                'weather_description' => $this->getVariedWeatherDescription(3, $longitude, $latitude),
                'weather_icon' => $this->getVariedWeatherIcon(3, $longitude, $latitude),
                'weather_color' => $this->getVariedWeatherColor(3, $longitude, $latitude)
            ];
        }
        
        // Southern Philippines (Mindanao) - warmer and more humid, more consistent weather
        $baseTemp = 30;
        $baseHumidity = 85;
        $basePrecip = 1.0;
        
        // Add micro-climate variations
        $tempVariation = round($longitudeVariation + $latitudeVariation, 1);
        $humidityVariation = round($longitudeVariation * 2, 0);
        $precipVariation = round($longitudeVariation * 0.2, 1);
        
        return [
            'temperature' => max(26, min(34, $baseTemp + $tempVariation)),
            'humidity' => max(75, min(95, $baseHumidity + $humidityVariation)),
            'precipitation' => max(0.5, min(2.0, $basePrecip + $precipVariation)),
            'precipitation_probability' => max(35, min(60, 45 + round($precipVariation * 10))),
            'wind_speed' => max(3, min(8, 5 + round($longitudeVariation))),
            'wind_direction' => 220 + round($longitudeVariation * 6),
            'weather_code' => $this->getVariedWeatherCode(95, $longitude, $latitude),
            'cloud_cover' => max(70, min(90, 80 + round($longitudeVariation * 2))),
            'weather_description' => $this->getVariedWeatherDescription(95, $longitude, $latitude),
            'weather_icon' => $this->getVariedWeatherIcon(95, $longitude, $latitude),
            'weather_color' => $this->getVariedWeatherColor(95, $longitude, $latitude)
        ];
    }
    
    /**
     * Get varied weather code based on coordinates for more realistic fallback data
     */
    private function getVariedWeatherCode(int $baseCode, float $longitude, float $latitude): int
    {
        // Create variation based on coordinates
        $variation = round(($longitude + $latitude) * 10) % 4;
        
        $weatherCodes = [
            0 => 0,   // Clear sky
            1 => 1,   // Mainly clear
            2 => 2,   // Partly cloudy
            3 => 3,   // Overcast
            45 => 45, // Fog
            48 => 48, // Depositing rime fog
            51 => 51, // Light drizzle
            53 => 53, // Moderate drizzle
            55 => 55, // Dense drizzle
            61 => 61, // Slight rain
            63 => 63, // Moderate rain
            65 => 65, // Heavy rain
            71 => 71, // Slight snow fall
            73 => 73, // Moderate snow fall
            75 => 75, // Heavy snow fall
            77 => 77, // Snow grains
            80 => 80, // Slight rain showers
            81 => 81, // Moderate rain showers
            82 => 82, // Violent rain showers
            85 => 85, // Slight snow showers
            86 => 86, // Heavy snow showers
            95 => 95, // Thunderstorm
            96 => 96, // Thunderstorm with slight hail
            99 => 99  // Thunderstorm with heavy hail
        ];
        
        $codes = array_values($weatherCodes);
        $index = array_search($baseCode, $codes);
        
        if ($index !== false) {
            $newIndex = ($index + $variation) % count($codes);
            return $codes[$newIndex];
        }
        
        return $baseCode;
    }
    
    /**
     * Get varied weather description based on coordinates
     */
    private function getVariedWeatherDescription(int $baseCode, float $longitude, float $latitude): string
    {
        $variation = round(($longitude + $latitude) * 10) % 3;
        
        $descriptions = [
            0 => ['Clear sky', 'Sunny', 'Bright'],
            1 => ['Mainly clear', 'Mostly sunny', 'Fair'],
            2 => ['Partly cloudy', 'Some clouds', 'Mixed clouds'],
            3 => ['Overcast', 'Cloudy', 'Dull'],
            45 => ['Fog', 'Misty', 'Hazy'],
            48 => ['Fog', 'Rime fog', 'Freezing fog'],
            51 => ['Light drizzle', 'Drizzle', 'Light rain'],
            53 => ['Moderate drizzle', 'Drizzle', 'Light rain'],
            55 => ['Dense drizzle', 'Heavy drizzle', 'Light rain'],
            61 => ['Light rain', 'Slight rain', 'Drizzle'],
            63 => ['Moderate rain', 'Rain', 'Showers'],
            65 => ['Heavy rain', 'Heavy showers', 'Downpour'],
            71 => ['Light snow', 'Snow flurries', 'Light snow fall'],
            73 => ['Moderate snow', 'Snow', 'Snow fall'],
            75 => ['Heavy snow', 'Heavy snow fall', 'Blizzard'],
            77 => ['Snow grains', 'Snow pellets', 'Ice pellets'],
            80 => ['Light showers', 'Rain showers', 'Light rain'],
            81 => ['Moderate showers', 'Rain showers', 'Showers'],
            82 => ['Heavy showers', 'Violent showers', 'Heavy rain'],
            85 => ['Light snow showers', 'Snow showers', 'Light snow'],
            86 => ['Heavy snow showers', 'Snow showers', 'Heavy snow'],
            95 => ['Thunderstorm', 'Thunder', 'Storm'],
            96 => ['Thunderstorm with hail', 'Thunder and hail', 'Storm with hail'],
            99 => ['Severe thunderstorm', 'Heavy thunderstorm', 'Violent storm']
        ];
        
        if (isset($descriptions[$baseCode])) {
            $descArray = $descriptions[$baseCode];
            return $descArray[$variation % count($descArray)];
        }
        
        return $this->getWeatherDescription($baseCode);
    }
    
    /**
     * Get varied weather icon based on coordinates
     */
    private function getVariedWeatherIcon(int $baseCode, float $longitude, float $latitude): string
    {
        $variation = round(($longitude + $latitude) * 10) % 2;
        
        $icons = [
            0 => ['☀️', '🌞'],
            1 => ['🌤️', '⛅'],
            2 => ['⛅', '🌤️'],
            3 => ['☁️', '🌫️'],
            45 => ['🌫️', '🌁'],
            48 => ['🌫️', '🌁'],
            51 => ['🌦️', '🌧️'],
            53 => ['🌧️', '🌦️'],
            55 => ['🌧️', '🌦️'],
            61 => ['🌧️', '🌦️'],
            63 => ['🌧️', '🌦️'],
            65 => ['🌧️', '⛈️'],
            71 => ['🌨️', '❄️'],
            73 => ['🌨️', '❄️'],
            75 => ['🌨️', '❄️'],
            77 => ['🌨️', '❄️'],
            80 => ['🌦️', '🌧️'],
            81 => ['🌧️', '🌦️'],
            82 => ['⛈️', '🌧️'],
            85 => ['🌨️', '❄️'],
            86 => ['🌨️', '❄️'],
            95 => ['⛈️', '🌩️'],
            96 => ['⛈️', '🌩️'],
            99 => ['⛈️', '🌩️']
        ];
        
        if (isset($icons[$baseCode])) {
            $iconArray = $icons[$baseCode];
            return $iconArray[$variation % count($iconArray)];
        }
        
        return $this->getWeatherIcon($baseCode);
    }
    
    /**
     * Get varied weather color based on coordinates
     */
    private function getVariedWeatherColor(int $baseCode, float $longitude, float $latitude): string
    {
        $variation = round(($longitude + $latitude) * 10) % 3;
        
        $colors = [
            0 => ['#FFD700', '#FFA500', '#FF8C00'],
            1 => ['#87CEEB', '#B0E0E6', '#ADD8E6'],
            2 => ['#808080', '#A9A9A9', '#C0C0C0'],
            3 => ['#696969', '#778899', '#708090'],
            45 => ['#D3D3D3', '#DCDCDC', '#F5F5F5'],
            48 => ['#D3D3D3', '#DCDCDC', '#F5F5F5'],
            51 => ['#4682B4', '#5F9EA0', '#6495ED'],
            53 => ['#4682B4', '#5F9EA0', '#6495ED'],
            55 => ['#4682B4', '#5F9EA0', '#6495ED'],
            61 => ['#4169E1', '#0000CD', '#0000FF'],
            63 => ['#0000CD', '#0000FF', '#191970'],
            65 => ['#0000FF', '#191970', '#000080'],
            71 => ['#F0F8FF', '#E6E6FA', '#F5F5DC'],
            73 => ['#E6E6FA', '#F5F5DC', '#FFF8DC'],
            75 => ['#F5F5DC', '#FFF8DC', '#F0E68C'],
            77 => ['#F5F5DC', '#FFF8DC', '#F0E68C'],
            80 => ['#4682B4', '#5F9EA0', '#6495ED'],
            81 => ['#4169E1', '#0000CD', '#0000FF'],
            82 => ['#0000CD', '#0000FF', '#191970'],
            85 => ['#F0F8FF', '#E6E6FA', '#F5F5DC'],
            86 => ['#E6E6FA', '#F5F5DC', '#FFF8DC'],
            95 => ['#FF4500', '#FF6347', '#FF7F50'],
            96 => ['#FF4500', '#FF6347', '#FF7F50'],
            99 => ['#DC143C', '#B22222', '#8B0000']
        ];
        
        if (isset($colors[$baseCode])) {
            $colorArray = $colors[$baseCode];
            return $colorArray[$variation % count($colorArray)];
        }
        
        return $this->getWeatherColor($baseCode);
    }

    /**
     * Format historical weather data from API response
     */
    private function formatHistoricalData(array $data): array
    {
        $daily = $data['daily'] ?? [];
        $historical = [];

        if (isset($daily['time']) && is_array($daily['time'])) {
            for ($i = 0; $i < count($daily['time']); $i++) {
                $historical[] = [
                    'date' => $daily['time'][$i] ?? '',
                    'day_name' => $this->getDayName($daily['time'][$i] ?? ''),
                    'temp_max' => round($daily['temperature_2m_max'][$i] ?? 0),
                    'temp_min' => round($daily['temperature_2m_min'][$i] ?? 0),
                    'precipitation' => round($daily['precipitation_sum'][$i] ?? 0, 1),
                    'weather_code' => $daily['weather_code'][$i] ?? 0,
                    'weather_description' => $this->getWeatherDescription($daily['weather_code'][$i] ?? 0),
                    'weather_icon' => $this->getWeatherIcon($daily['weather_code'][$i] ?? 0),
                    'weather_color' => $this->getWeatherColor($daily['weather_code'][$i] ?? 0)
                ];
            }
        }

        return $historical;
    }

    /**
     * Get default historical weather data when API fails
     */
    private function getDefaultHistoricalData(float $latitude, float $longitude, int $days): array
    {
        $climateData = $this->getClimateZoneData($latitude, $longitude);
        $historical = [];
        
        for ($i = $days; $i >= 1; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayName = now()->subDays($i)->format('D j');
            
            // Add some variation to make historical data more realistic
            $tempVariation = ($i % 3) * 2; // Vary temperature based on day
            $precipVariation = ($i % 4) * 0.2; // Vary precipitation
            
            $historical[] = [
                'date' => $date,
                'day_name' => $dayName,
                'temp_max' => max(20, min(35, $climateData['temperature'] + $tempVariation)),
                'temp_min' => max(18, min(30, $climateData['temperature'] - 3 + $tempVariation)),
                'precipitation' => max(0.0, min(2.0, $climateData['precipitation'] + $precipVariation)),
                'weather_code' => $climateData['weather_code'],
                'weather_description' => $climateData['weather_description'],
                'weather_icon' => $climateData['weather_icon'],
                'weather_color' => $climateData['weather_color']
            ];
        }
        
        return $historical;
    }
}
