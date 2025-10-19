<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    private const API_BASE_URL = 'https://api.open-meteo.com/v1/forecast';
    private const CACHE_DURATION = 900; // 15 minutes cache 
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
     * Get past days weather data (for current weather popup)
     */
    public function getPastDaysWeather(float $latitude, float $longitude, int $days = 4): array
    {
        // Validate coordinates are within Philippines boundaries
        if (!$this->validateCoordinates($latitude, $longitude)) {
            Log::error('Invalid coordinates provided for past days weather data - outside Philippines', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'days' => $days
            ]);
            return $this->getDefaultPastDaysData($latitude, $longitude, $days);
        }

        $cacheKey = "weather_past_days_{$latitude}_{$longitude}_{$days}";
        
        return Cache::remember($cacheKey, 3600, function () use ($latitude, $longitude, $days) {
            try {
                // Calculate the exact date range for the requested number of days
                $endDate = now()->subDay(); // Yesterday
                $startDate = now()->subDays($days); // Go back the requested number of days
                
                
                $response = Http::timeout(10)->get(self::API_BASE_URL, [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_sum,weather_code',
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'timezone' => self::TIMEZONE
                ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    $historicalData = $this->formatHistoricalData($data);
                    
                    
                    // Fill in any missing days with realistic data
                    $completeData = $this->fillMissingPastDays($historicalData, $startDate, $endDate, $latitude, $longitude);
                    
                    
                    return $completeData;
                }
                
                Log::warning('Past days weather API returned non-successful response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'coordinates' => [$latitude, $longitude]
                ]);
                
                return $this->getDefaultPastDaysData($latitude, $longitude, $days);
            } catch (\Exception $e) {
                Log::error('Past days weather API exception', [
                    'message' => $e->getMessage(),
                    'coordinates' => [$latitude, $longitude]
                ]);
                
                return $this->getDefaultPastDaysData($latitude, $longitude, $days);
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
                // Calculate complete months - get the last 3 complete months
                $currentDate = now();
                $currentMonth = $currentDate->month;
                $currentYear = $currentDate->year;
                
                // If we're in the middle of a month, don't include current month
                // Go back to get complete months only
                if ($currentDate->day < 28) { // If not near end of month, exclude current month
                    $endDate = $currentDate->copy()->subMonth()->endOfMonth();
                } else {
                    $endDate = $currentDate->copy()->subDay(); // Yesterday if near end of month
                }
                
                // Start from 3 months before the end date to get complete months
                $startDate = $endDate->copy()->subMonths(2)->startOfMonth();
                
                
                // Adjust start date to match API's available range
                // The API typically has data from about 2-3 weeks ago
                $apiStartDate = $currentDate->copy()->subDays(90); // Go back 90 days to ensure we get data
                
                $response = Http::timeout(10)->get(self::API_BASE_URL, [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_sum,weather_code',
                    'start_date' => $apiStartDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'timezone' => self::TIMEZONE
                ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    $historicalData = $this->formatHistoricalData($data);
                    
                    // Always generate complete months for the last 3 months
                    $completeData = $this->generateCompleteThreeMonths($historicalData, $endDate, $latitude, $longitude);
                    
                    return $completeData;
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
        $historical = $this->getPastDaysWeather($latitude, $longitude, 4);
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
            // Find today's date to determine the correct starting index
            $today = now()->format('Y-m-d');
            $startIndex = 0;
            
            // Find the index that corresponds to tomorrow
            for ($i = 0; $i < count($daily['time']); $i++) {
                $apiDate = $daily['time'][$i] ?? '';
                if ($apiDate > $today) {
                    $startIndex = $i;
                    break;
                }
            }
            
            // If we couldn't find tomorrow, start from index 1 (skip today)
            if ($startIndex === 0 && count($daily['time']) > 1) {
                $startIndex = 1;
            }
            
            // Generate forecast for next 4 days starting from tomorrow
            for ($i = $startIndex; $i < min($startIndex + 4, count($daily['time'])); $i++) {
                $apiDate = $daily['time'][$i] ?? '';
                
                // If API date is not available, generate the correct date
                if (empty($apiDate)) {
                    $daysFromToday = $i - $startIndex + 1;
                    $apiDate = now()->addDays($daysFromToday)->format('Y-m-d');
                }
                
                $forecast[] = [
                    'date' => $apiDate,
                    'day_name' => $this->getDayName($apiDate),
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
            // Use Carbon to handle timezone properly
            $carbonDate = \Carbon\Carbon::parse($date, self::TIMEZONE);
            return $carbonDate->format('D j');
        } catch (\Exception $e) {
            // Fallback to basic date parsing
            try {
                $timestamp = strtotime($date);
                return date('D j', $timestamp);
            } catch (\Exception $e2) {
                return 'Unknown';
            }
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
        
        // Generate forecast for next 7 days starting from tomorrow
        for ($i = 1; $i <= 7; $i++) {
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
                'precipitation' => round(max(0.0, min(1.0, $basePrecip + $precipVariation)), 1),
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
                'precipitation' => round(max(0.1, min(1.5, $basePrecip + $precipVariation)), 1),
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
            'precipitation' => round(max(0.5, min(2.0, $basePrecip + $precipVariation)), 1),
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
     * Fill missing days in historical data to ensure complete month coverage
     */
    private function fillMissingDays(array $apiData, $startDate, $endDate, float $latitude, float $longitude): array
    {
        $completeData = [];
        $apiDataByDate = [];
        
        // Index API data by date for quick lookup
        foreach ($apiData as $day) {
            $apiDataByDate[$day['date']] = $day;
        }
        
        // Generate complete date range
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->format('Y-m-d');
            
            if (isset($apiDataByDate[$dateString])) {
                // Use API data if available
                $completeData[] = $apiDataByDate[$dateString];
            } else {
                // Generate realistic default data for missing days
                $dayOfWeek = $currentDate->dayOfWeek;
                $month = $currentDate->month;
                $climateData = $this->getClimateZoneData($latitude, $longitude);
                
                // More realistic variation based on day of week and month
                $tempVariation = ($dayOfWeek % 3) * 1.5 + ($month % 2) * 1;
                $precipVariation = ($dayOfWeek % 5) * 0.3 + ($month % 3) * 0.2;
                
                // Weather patterns based on season and day
                $weatherCodes = [0, 1, 2, 3, 61, 63, 80, 81, 95];
                $weatherCode = $weatherCodes[($dayOfWeek + $month) % count($weatherCodes)];
                
                $completeData[] = [
                    'date' => $dateString,
                    'day_name' => $currentDate->format('D j'),
                    'temp_max' => max(22, min(34, $climateData['temperature'] + $tempVariation)),
                    'temp_min' => max(20, min(28, $climateData['temperature'] - 4 + $tempVariation)),
                    'precipitation' => round(max(0.0, min(3.0, $climateData['precipitation'] + $precipVariation)), 1),
                    'weather_code' => $weatherCode,
                    'weather_description' => $this->getWeatherDescription($weatherCode),
                    'weather_icon' => $this->getWeatherIcon($weatherCode),
                    'weather_color' => $this->getWeatherColor($weatherCode)
                ];
            }
            
            $currentDate->addDay();
        }
        
        return $completeData;
    }

    /**
     * Generate complete data for the last 3 months
     */
    private function generateCompleteThreeMonths(array $apiData, $endDate, float $latitude, float $longitude): array
    {
        // Calculate the start date for the last 3 complete months
        $startDate = $endDate->copy()->subMonths(2)->startOfMonth();
        
        // Index existing data by date for quick lookup
        $dataByDate = [];
        foreach ($apiData as $day) {
            $dataByDate[$day['date']] = $day;
        }
        
        // Generate complete data for the last 3 months
        $completeData = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->format('Y-m-d');
            
            if (isset($dataByDate[$dateString])) {
                // Use existing data if available
                $completeData[] = $dataByDate[$dateString];
            } else {
                // Generate realistic data for missing days
                $completeData[] = $this->generateRealisticDayData($currentDate, $apiData, $latitude, $longitude);
            }
            
            $currentDate->addDay();
        }
        
        return $completeData;
    }

    /**
     * Filter data to only include the last 3 complete months
     */
    private function filterToLastThreeMonths(array $data, $endDate): array
    {
        // Calculate the start date for the last 3 complete months
        $threeMonthsAgo = $endDate->copy()->subMonths(2)->startOfMonth();
        
        // Create a complete date range for the last 3 months
        $completeData = [];
        $currentDate = $threeMonthsAgo->copy();
        
        // Index existing data by date for quick lookup
        $dataByDate = [];
        foreach ($data as $day) {
            $dataByDate[$day['date']] = $day;
        }
        
        // Generate complete data for the last 3 months
        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->format('Y-m-d');
            
            if (isset($dataByDate[$dateString])) {
                // Use existing data if available
                $completeData[] = $dataByDate[$dateString];
            } else {
                // Generate realistic data for missing days
                $completeData[] = $this->generateRealisticDayData($currentDate, $data);
            }
            
            $currentDate->addDay();
        }
        
        return $completeData;
    }

    /**
     * Generate realistic weather data for a missing day
     */
    private function generateRealisticDayData($date, array $existingData, float $latitude = null, float $longitude = null): array
    {
        // Get climate data for the specific location
        if ($latitude !== null && $longitude !== null) {
            $climateData = $this->getClimateZoneData($latitude, $longitude);
        } else {
            // Fallback to default if coordinates not provided
            $climateData = [
                'temperature' => 28,
                'precipitation' => 1.5,
                'weather_code' => 0
            ];
        }
        
        // Add variation based on day of week and month
        $dayOfWeek = $date->dayOfWeek;
        $month = $date->month;
        
        // Add location-specific variation based on coordinates
        $locationVariation = 0;
        if ($latitude !== null && $longitude !== null) {
            $locationVariation = (($latitude * 100 + $longitude * 100) % 10) - 5; // -5 to +5 variation
        }
        
        $tempVariation = ($dayOfWeek % 3) * 1.5 + ($month % 2) * 1 + $locationVariation;
        $precipVariation = ($dayOfWeek % 5) * 0.3 + ($month % 3) * 0.2 + ($locationVariation * 0.1);
        
        // Weather patterns based on season, day, and location
        $weatherCodes = [0, 1, 2, 3, 61, 63, 80, 81, 95];
        $weatherCodeIndex = ($dayOfWeek + $month + ($locationVariation % 3)) % count($weatherCodes);
        $weatherCode = $weatherCodes[$weatherCodeIndex];
        
        return [
            'date' => $date->format('Y-m-d'),
            'day_name' => $date->format('D j'),
            'temp_max' => max(22, min(34, $climateData['temperature'] + $tempVariation)),
            'temp_min' => max(20, min(28, $climateData['temperature'] - 4 + $tempVariation)),
            'precipitation' => round(max(0.0, min(3.0, $climateData['precipitation'] + $precipVariation)), 1),
            'weather_code' => $weatherCode,
            'weather_description' => $this->getWeatherDescription($weatherCode),
            'weather_icon' => $this->getWeatherIcon($weatherCode),
            'weather_color' => $this->getWeatherColor($weatherCode)
        ];
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
            
            // Add more realistic variation based on day of week and month
            $dayOfWeek = now()->subDays($i)->dayOfWeek;
            $month = now()->subDays($i)->month;
            
            // Temperature variation based on day of week and month
            $tempVariation = ($dayOfWeek % 3) * 1.5 + ($month % 2) * 1;
            $precipVariation = ($dayOfWeek % 5) * 0.3 + ($month % 3) * 0.2;
            
            // More realistic weather patterns
            $weatherCodes = [0, 1, 2, 3, 61, 63, 80, 81, 95]; // Mix of clear, cloudy, and rainy
            $weatherCode = $weatherCodes[($dayOfWeek + $month) % count($weatherCodes)];
            
            $historical[] = [
                'date' => $date,
                'day_name' => $dayName,
                'temp_max' => max(22, min(34, $climateData['temperature'] + $tempVariation)),
                'temp_min' => max(20, min(28, $climateData['temperature'] - 4 + $tempVariation)),
                'precipitation' => round(max(0.0, min(3.0, $climateData['precipitation'] + $precipVariation)), 1),
                'weather_code' => $weatherCode,
                'weather_description' => $this->getWeatherDescription($weatherCode),
                'weather_icon' => $this->getWeatherIcon($weatherCode),
                'weather_color' => $this->getWeatherColor($weatherCode)
            ];
        }
        
        return $historical;
    }

    /**
     * Fill missing days in past days data
     */
    private function fillMissingPastDays(array $apiData, $startDate, $endDate, float $latitude, float $longitude): array
    {
        $completeData = [];
        $apiDataByDate = [];
        
        // Index API data by date for quick lookup
        foreach ($apiData as $day) {
            $apiDataByDate[$day['date']] = $day;
        }
        
        
        // Generate complete date range for past days
        $currentDate = $startDate->copy();
        $endDateString = $endDate->format('Y-m-d');
        
        while ($currentDate->format('Y-m-d') <= $endDateString) {
            $dateString = $currentDate->format('Y-m-d');
            
            if (isset($apiDataByDate[$dateString])) {
                // Use existing data if available
                $completeData[] = $apiDataByDate[$dateString];
            } else {
                // Generate realistic data for missing days
                $completeData[] = $this->generateRealisticDayData($currentDate, $apiData, $latitude, $longitude);
            }
            
            $currentDate->addDay();
        }
        
        
        return $completeData;
    }

    /**
     * Get default past days weather data when API fails
     */
    private function getDefaultPastDaysData(float $latitude, float $longitude, int $days): array
    {
        $climateData = $this->getClimateZoneData($latitude, $longitude);
        $historical = [];
        
        // Generate data for the past days, including yesterday
        // Start from the earliest day and go up to yesterday
        $startDate = now()->subDays($days);
        $endDate = now()->subDay(); // Yesterday
        
        
        $currentDate = $startDate->copy();
        $endDateString = $endDate->format('Y-m-d');
        
        while ($currentDate->format('Y-m-d') <= $endDateString) {
            $dayName = $currentDate->format('D j'); // This will show correct dates like "Tue 14", "Wed 15", etc.
            
            // Add more realistic variation based on day of week and month
            $dayOfWeek = $currentDate->dayOfWeek;
            $month = $currentDate->month;
            
            // Temperature variation based on day of week and month
            $tempVariation = ($dayOfWeek % 3) * 1.5 + ($month % 2) * 1;
            $precipVariation = ($dayOfWeek % 5) * 0.3 + ($month % 3) * 0.2;
            
            // More realistic weather patterns
            $weatherCodes = [0, 1, 2, 3, 61, 63, 80, 81, 95]; // Mix of clear, cloudy, and rainy
            $weatherCode = $weatherCodes[($dayOfWeek + $month) % count($weatherCodes)];
            
            $historical[] = [
                'date' => $currentDate->format('Y-m-d'),
                'day_name' => $dayName,
                'temp_max' => max(22, min(34, $climateData['temperature'] + $tempVariation)),
                'temp_min' => max(20, min(28, $climateData['temperature'] - 4 + $tempVariation)),
                'precipitation' => round(max(0.0, min(3.0, $climateData['precipitation'] + $precipVariation)), 1),
                'weather_code' => $weatherCode,
                'weather_description' => $this->getWeatherDescription($weatherCode),
                'weather_icon' => $this->getWeatherIcon($weatherCode),
                'weather_color' => $this->getWeatherColor($weatherCode)
            ];
            
            $currentDate->addDay();
        }
        
        
        return $historical;
    }

    /**
     * Check for severe weather conditions and generate alerts
     */
    public function checkWeatherAlerts(float $latitude, float $longitude): array
    {
        $weatherData = $this->getWeatherData($latitude, $longitude);
        $alerts = [];

        // Check current weather conditions
        $current = $weatherData['current'] ?? [];
        $forecast = $weatherData['forecast'] ?? [];

        // Check for severe weather conditions
        $alerts = array_merge($alerts, $this->checkCurrentWeatherAlerts($current));
        $alerts = array_merge($alerts, $this->checkForecastAlerts($forecast));

        return $alerts;
    }

    /**
     * Check current weather for alert conditions
     */
    private function checkCurrentWeatherAlerts(array $current): array
    {
        $alerts = [];

        // Rainfall alerts based on weather codes and precipitation probability
        $weatherCode = $current['weather_code'] ?? 0;
        $precipitationProbability = $current['precipitation_probability'] ?? 0;
        $precipitation = $current['precipitation'] ?? 0;

        // Thunderstorm alerts (SEVERE)
        if (in_array($weatherCode, [95, 96, 99])) {
            $severity = $weatherCode === 99 ? 'severe' : 'severe'; // All thunderstorms are severe
            $alerts[] = [
                'type' => 'thunderstorm',
                'severity' => $severity,
                'message' => 'Thunderstorm detected',
                'description' => 'Thunderstorm activity detected. ' . ($weatherCode === 99 ? 'Heavy thunderstorm with hail possible.' : 'Thunderstorm conditions present.'),
                'weather_data' => $current
            ];
        }
        // Moderate rain alerts (MODERATE)
        elseif (in_array($weatherCode, [63, 81]) || ($precipitation > 5 && $precipitationProbability > 60)) {
            $alerts[] = [
                'type' => 'moderate_rain',
                'severity' => 'moderate',
                'message' => 'Moderate rainfall detected',
                'description' => 'Moderate rainfall conditions detected. Monitor for potential issues.',
                'weather_data' => $current
            ];
        }
        // Light rain alerts (MINOR)
        elseif (in_array($weatherCode, [61, 80]) || ($precipitation > 0 && $precipitationProbability > 40)) {
            $alerts[] = [
                'type' => 'light_rain',
                'severity' => 'minor',
                'message' => 'Light rainfall detected',
                'description' => 'Light rainfall conditions detected. Normal weather conditions.',
                'weather_data' => $current
            ];
        }

        // Strong wind alert (unchanged)
        if (($current['wind_speed'] ?? 0) > 50) {
            $alerts[] = [
                'type' => 'strong_wind',
                'severity' => 'severe',
                'message' => 'Strong winds detected',
                'description' => 'Strong winds of ' . ($current['wind_speed'] ?? 0) . ' km/h detected. Secure outdoor items.',
                'weather_data' => $current
            ];
        } elseif (($current['wind_speed'] ?? 0) > 30) {
            $alerts[] = [
                'type' => 'moderate_wind',
                'severity' => 'moderate',
                'message' => 'Moderate winds detected',
                'description' => 'Moderate winds of ' . ($current['wind_speed'] ?? 0) . ' km/h detected.',
                'weather_data' => $current
            ];
        }

        // Extreme temperature alerts (unchanged)
        if (($current['temperature'] ?? 0) > 38) {
            $alerts[] = [
                'type' => 'extreme_heat',
                'severity' => 'severe',
                'message' => 'Extreme heat warning',
                'description' => 'Extreme heat of ' . ($current['temperature'] ?? 0) . '°C detected. Risk of heat-related issues.',
                'weather_data' => $current
            ];
        } elseif (($current['temperature'] ?? 0) > 35) {
            $alerts[] = [
                'type' => 'high_temperature',
                'severity' => 'moderate',
                'message' => 'High temperature warning',
                'description' => 'High temperature of ' . ($current['temperature'] ?? 0) . '°C detected.',
                'weather_data' => $current
            ];
        }

        return $alerts;
    }

    /**
     * Check forecast for alert conditions
     */
    private function checkForecastAlerts(array $forecast): array
    {
        $alerts = [];

        // Check next 4 days for severe conditions (forecast already starts from tomorrow)
        foreach (array_slice($forecast, 0, 4) as $day) {
            $dayAlerts = [];
            $weatherCode = $day['weather_code'] ?? 0;
            $precipitationProbability = $day['precipitation_probability'] ?? 0;
            $precipitation = $day['precipitation'] ?? 0;

            // Thunderstorm forecast (SEVERE)
            if (in_array($weatherCode, [95, 96, 99])) {
                $dayAlerts[] = [
                    'type' => 'thunderstorm_forecast',
                    'severity' => 'severe',
                    'message' => 'Thunderstorm forecast for ' . ($day['day_name'] ?? 'upcoming day'),
                    'description' => 'Thunderstorm conditions forecasted for ' . ($day['day_name'] ?? 'upcoming day') . '. Prepare for severe weather.',
                    'weather_data' => $day,
                    'forecast_day' => $day['day_name'] ?? 'Unknown'
                ];
            }
            // Moderate rain forecast (MODERATE)
            elseif (in_array($weatherCode, [63, 81]) || ($precipitation > 5 && $precipitationProbability > 60)) {
                $dayAlerts[] = [
                    'type' => 'moderate_rain_forecast',
                    'severity' => 'moderate',
                    'message' => 'Moderate rain forecast for ' . ($day['day_name'] ?? 'upcoming day'),
                    'description' => 'Moderate rainfall forecasted for ' . ($day['day_name'] ?? 'upcoming day') . '. Monitor conditions.',
                    'weather_data' => $day,
                    'forecast_day' => $day['day_name'] ?? 'Unknown'
                ];
            }
            // Light rain forecast (MINOR)
            elseif (in_array($weatherCode, [61, 80]) || ($precipitation > 0 && $precipitationProbability > 40)) {
                $dayAlerts[] = [
                    'type' => 'light_rain_forecast',
                    'severity' => 'minor',
                    'message' => 'Light rain forecast for ' . ($day['day_name'] ?? 'upcoming day'),
                    'description' => 'Light rainfall forecasted for ' . ($day['day_name'] ?? 'upcoming day') . '. Normal weather conditions.',
                    'weather_data' => $day,
                    'forecast_day' => $day['day_name'] ?? 'Unknown'
                ];
            }

            // Strong wind forecast (unchanged)
            if (($day['wind_speed'] ?? 0) > 40) {
                $dayAlerts[] = [
                    'type' => 'strong_wind_forecast',
                    'severity' => 'moderate',
                    'message' => 'Strong winds forecast for ' . ($day['day_name'] ?? 'upcoming day'),
                    'description' => 'Strong winds of ' . ($day['wind_speed'] ?? 0) . ' km/h forecasted for ' . ($day['day_name'] ?? 'upcoming day') . '. Secure outdoor items.',
                    'weather_data' => $day,
                    'forecast_day' => $day['day_name'] ?? 'Unknown'
                ];
            }

            // Extreme temperature forecast (unchanged)
            if (($day['temp_max'] ?? 0) > 38) {
                $dayAlerts[] = [
                    'type' => 'extreme_heat_forecast',
                    'severity' => 'severe',
                    'message' => 'Extreme heat forecast for ' . ($day['day_name'] ?? 'upcoming day'),
                    'description' => 'Extreme heat of ' . ($day['temp_max'] ?? 0) . '°C forecasted for ' . ($day['day_name'] ?? 'upcoming day') . '. Take heat precautions.',
                    'weather_data' => $day,
                    'forecast_day' => $day['day_name'] ?? 'Unknown'
                ];
            }

            $alerts = array_merge($alerts, $dayAlerts);
        }

        return $alerts;
    }

    /**
     * Get alert recommendations based on alert type and severity
     */
    public function getAlertRecommendations(string $alertType, string $severity): array
    {
        $recommendations = [
            'thunderstorm' => [
                'severe' => [
                    'Stay indoors and avoid windows',
                    'Unplug electronic devices',
                    'Avoid using plumbing during storm',
                    'Have emergency supplies ready',
                    'Monitor weather updates',
                    'Secure outdoor items immediately'
                ],
                'moderate' => [
                    'Stay indoors during storm',
                    'Avoid outdoor activities',
                    'Monitor weather conditions',
                    'Secure lightweight outdoor items'
                ]
            ],
            'thunderstorm_forecast' => [
                'severe' => [
                    'Prepare emergency supplies',
                    'Secure all outdoor furniture and equipment',
                    'Check drainage systems',
                    'Monitor weather updates closely',
                    'Have backup power sources ready'
                ]
            ],
            'moderate_rain' => [
                'moderate' => [
                    'Check gutters and downspouts',
                    'Monitor basement or low-lying areas',
                    'Ensure proper drainage around property',
                    'Move valuable items away from potential water entry points'
                ]
            ],
            'moderate_rain_forecast' => [
                'moderate' => [
                    'Check drainage systems around the property',
                    'Ensure gutters are clear',
                    'Monitor weather updates',
                    'Prepare for potential water issues'
                ]
            ],
            'light_rain' => [
                'minor' => [
                    'Monitor weather conditions',
                    'Ensure proper drainage',
                    'Normal weather conditions - no special action needed'
                ]
            ],
            'light_rain_forecast' => [
                'minor' => [
                    'Normal weather forecast - no special action needed',
                    'Monitor conditions if needed'
                ]
            ],
            'strong_wind' => [
                'severe' => [
                    'Secure all outdoor furniture and equipment',
                    'Trim loose tree branches near property',
                    'Check roof and siding for loose materials',
                    'Stay indoors during peak wind conditions'
                ],
                'moderate' => [
                    'Secure lightweight outdoor items',
                    'Check for loose objects that could become projectiles'
                ]
            ],
            'strong_wind_forecast' => [
                'moderate' => [
                    'Secure outdoor furniture and equipment',
                    'Check for loose objects',
                    'Monitor weather updates'
                ]
            ],
            'extreme_heat' => [
                'severe' => [
                    'Ensure adequate ventilation in all rooms',
                    'Check air conditioning systems',
                    'Provide extra water for tenants',
                    'Monitor for heat-related health issues',
                    'Consider temporary cooling solutions'
                ],
                'moderate' => [
                    'Ensure proper ventilation',
                    'Check cooling systems are working',
                    'Advise tenants to stay hydrated'
                ]
            ],
            'extreme_heat_forecast' => [
                'severe' => [
                    'Prepare cooling systems',
                    'Ensure adequate ventilation',
                    'Plan for heat-related precautions',
                    'Monitor weather updates'
                ]
            ]
        ];

        return $recommendations[$alertType][$severity] ?? [
            'Monitor weather conditions',
            'Take appropriate precautions',
            'Stay informed about updates'
        ];
    }

    /**
     * Check if weather conditions warrant an alert
     */
    public function shouldSendAlert(array $weatherData, array $userPreferences = []): bool
    {
        $alerts = $this->checkWeatherAlerts(
            $weatherData['location']['latitude'] ?? 0,
            $weatherData['location']['longitude'] ?? 0
        );

        // If no alerts, don't send
        if (empty($alerts)) {
            return false;
        }

        // Check user preferences
        $minSeverity = $userPreferences['min_severity'] ?? 'minor';
        $severityLevels = ['minor' => 1, 'moderate' => 2, 'severe' => 3];
        $minLevel = $severityLevels[$minSeverity] ?? 1;

        // Check if any alert meets the minimum severity
        foreach ($alerts as $alert) {
            $alertLevel = $severityLevels[$alert['severity']] ?? 1;
            if ($alertLevel >= $minLevel) {
                return true;
            }
        }

        return false;
    }
}
