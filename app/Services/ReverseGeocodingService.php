<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReverseGeocodingService
{
    /**
     * Get city/municipality from coordinates using reverse geocoding
     */
    public function getCityFromCoordinates($latitude, $longitude)
    {
        if (empty($latitude) || empty($longitude)) {
            return null;
        }

        // Create cache key for this coordinate
        $cacheKey = "reverse_geocode_{$latitude}_{$longitude}";
        
        // Check cache first (cache for 24 hours)
        return Cache::remember($cacheKey, 86400, function () use ($latitude, $longitude) {
            try {
                // Use Nominatim (OpenStreetMap) for reverse geocoding
                $url = "https://nominatim.openstreetmap.org/reverse?lat={$latitude}&lon={$longitude}&format=json&addressdetails=1";
                
                $context = stream_context_create([
                    'http' => [
                        'header' => "User-Agent: SmartStay/1.0\r\n",
                        'timeout' => 10
                    ]
                ]);
                
                $response = file_get_contents($url, false, $context);
                
                if ($response === false) {
                    Log::warning("Failed to fetch reverse geocoding data for coordinates: {$latitude}, {$longitude}");
                    return null;
                }
                
                $data = json_decode($response, true);
                
                if (!$data || isset($data['error'])) {
                    Log::warning("Reverse geocoding API error for coordinates: {$latitude}, {$longitude}", ['response' => $data]);
                    return null;
                }
                
                // Extract city/municipality from the address components
                $city = $this->extractCityFromAddress($data['address'] ?? []);
                
                if ($city) {
                    Log::info("Successfully extracted city: {$city} from coordinates: {$latitude}, {$longitude}");
                }
                
                return $city;
                
            } catch (\Exception $e) {
                Log::error("Reverse geocoding error for coordinates: {$latitude}, {$longitude}", [
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }
    
    /**
     * Extract city/municipality from address components
     */
    private function extractCityFromAddress($address)
    {
        // Priority order for city/municipality extraction
        $cityFields = [
            'city',
            'municipality', 
            'town',
            'village',
            'hamlet',
            'suburb',
            'county',
            'state_district',
            'region'
        ];
        
        foreach ($cityFields as $field) {
            if (isset($address[$field]) && !empty($address[$field])) {
                $city = trim($address[$field]);
                
                // Clean up the city name
                $city = $this->cleanCityName($city);
                
                if ($this->isValidCityName($city)) {
                    return $city;
                }
            }
        }
        
        // If no specific city field found, try to extract from display_name
        if (isset($address['display_name'])) {
            return $this->extractCityFromDisplayName($address['display_name']);
        }
        
        return null;
    }
    
    /**
     * Extract city from display name as fallback
     */
    private function extractCityFromDisplayName($displayName)
    {
        // Common patterns for Philippine addresses
        $patterns = [
            // Pattern for "City Name, Province, Philippines"
            '/([^,]+(?:City|Municipality|Town)),\s*[^,]+,\s*Philippines/i',
            // Pattern for "City Name, Region, Philippines" 
            '/([^,]+(?:City|Municipality|Town)),\s*[^,]+,\s*Philippines/i',
            // Pattern for specific known Philippine cities
            '/(Manila|Davao City|Cagayan de Oro|Cebu City|Quezon City|Makati|Taguig|Pasig|Marikina|Parañaque|Las Piñas|Muntinlupa|Caloocan|Malabon|Navotas|Valenzuela|San Juan|Mandaluyong|Pasay|Pateros|Malaybalay City|Valencia City|Baungon|Cabanglasan|Damulog|Dangcagan|Don Carlos|Impasug-ong|Kadingilan|Kalilangan|Kibawe|Kitaotao|Lantapan|Libona|Malitbog|Manolo Fortich|Maramag|Pangantucan|Quezon|San Fernando|Sumilao|Talakag)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $displayName, $matches)) {
                $city = trim($matches[1]);
                $city = $this->cleanCityName($city);
                
                if ($this->isValidCityName($city)) {
                    return $city;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Clean up city name
     */
    private function cleanCityName($city)
    {
        // Remove extra spaces and normalize
        $city = preg_replace('/\s+/', ' ', trim($city));
        
        // Ensure proper capitalization for "City" and "Municipality"
        $city = preg_replace('/\s+City$/', ' City', $city);
        $city = preg_replace('/\s+Municipality$/', ' Municipality', $city);
        
        return $city;
    }
    
    /**
     * Check if city name is valid
     */
    private function isValidCityName($city)
    {
        // Must be at least 3 characters and not just numbers
        if (strlen($city) < 3 || is_numeric($city)) {
            return false;
        }
        
        // Exclude common non-city terms
        $excludeTerms = ['province', 'region', 'philippines', 'country', 'state'];
        $cityLower = strtolower($city);
        
        foreach ($excludeTerms as $term) {
            if (strpos($cityLower, $term) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get unique cities from all properties
     */
    public function getUniqueCitiesFromProperties()
    {
        $properties = \App\Models\Property::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->where('longitude', '!=', 0)
            ->select('latitude', 'longitude')
            ->get();
        
        $cities = [];
        
        foreach ($properties as $property) {
            $city = $this->getCityFromCoordinates($property->latitude, $property->longitude);
            
            if ($city && !in_array($city, $cities)) {
                $cities[] = $city;
            }
        }
        
        sort($cities);
        return $cities;
    }
}
