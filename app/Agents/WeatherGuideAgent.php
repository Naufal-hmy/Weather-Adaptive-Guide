<?php

namespace App\Agents;

use App\Models\Destination;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherGuideAgent
{
    /**
     * Get weather-adaptive recommendations for a given city.
     * 
     * The agentic logic:
     * 1. Fetch live weather data from OpenWeather API (if key is set).
     * 2. If it is raining (or simulated rain), it filters to indoor destinations.
     * 3. Otherwise, it prefers outdoor destinations.
     */
    public function getRecommendations(?string $city, ?float $lat = null, ?float $lon = null)
    {
        try {
            $weatherData = $this->fetchCurrentWeather($city, $lat, $lon);
            // In case we used lat/lon, update city to the real city name from API
            if (!$city && isset($weatherData['name'])) {
                $city = $weatherData['name'];
            }

        } catch (\Exception $e) {
            return [
                'is_error' => true,
                'error_message' => $e->getMessage()
            ];
        }
        
        $isRaining = $this->evaluateWeather($weatherData);

        $country = $weatherData['sys']['country'] ?? '';
        $timezoneOffset = $weatherData['timezone'] ?? 0;
        $localTime = gmdate('H:i', time() + $timezoneOffset);
        
        $weatherData['formatted_time'] = $localTime;
        $weatherData['country_code'] = $country ? \Locale::getDisplayRegion('-' . $country, 'id') : '';

        $currentTemp = $weatherData['main']['temp'] ?? null;

        // Base query with temperature checks if available
        $query = Destination::query()->where('city', 'like', "%$city%");
        
        if ($currentTemp !== null) {
            $query->where(function ($q) use ($currentTemp) {
                $q->whereNull('min_temp')
                  ->orWhere('min_temp', '<=', $currentTemp);
            })->where(function ($q) use ($currentTemp) {
                $q->whereNull('max_temp')
                  ->orWhere('max_temp', '>=', $currentTemp);
            });
        }

        if ($isRaining) {
            $recommendations = (clone $query)->where('category', 'indoor')->get();
            $reason = "Cuaca di $city saat ini sedang buruk/hujan. Kami sangat merekomendasikan destinasi dalam ruangan (indoor) ini agar kamu tetap aman dan nyaman!";
        } else {
            $recommendations = (clone $query)->where('category', 'outdoor')->get();
            $reason = "Cuaca di $city sedang cerah! Ini adalah waktu yang sangat tepat untuk menjelajahi destinasi luar ruangan (outdoor) berikut.";
        }

        // If no destinations match the exact temp criteria, fallback to any matching category
        if ($recommendations->isEmpty()) {
            if ($isRaining) {
                $recommendations = Destination::indoor()->where('city', 'like', "%$city%")->get();
            } else {
                $recommendations = Destination::outdoor()->where('city', 'like', "%$city%")->get();
            }
        }

        return [
            'is_error' => false,
            'weather' => $weatherData,
            'is_raining' => $isRaining,
            'reason' => $reason,
            'destinations' => $recommendations
        ];
    }

    /**
     * Fetch weather data. Throws exception if API fails or city is not found.
     */
    private function fetchCurrentWeather(?string $city, ?float $lat = null, ?float $lon = null)
    {
        // Option A: If user types a known country, map it to its capital city
        $countryToCapitalMap = [
            'indonesia' => 'Jakarta',
            'amerika serikat' => 'Washington D.C.',
            'amerika' => 'Washington D.C.',
            'usa' => 'Washington D.C.',
            'jepang' => 'Tokyo',
            'japan' => 'Tokyo',
            'inggris' => 'London',
            'uk' => 'London',
            'prancis' => 'Paris',
            'france' => 'Paris',
            'korea selatan' => 'Seoul',
            'korea' => 'Seoul',
            'singapura' => 'Singapore',
            'malaysia' => 'Kuala Lumpur',
            'australia' => 'Canberra'
        ];

        $lowerCity = strtolower(trim($city));
        if (array_key_exists($lowerCity, $countryToCapitalMap)) {
            $city = $countryToCapitalMap[$lowerCity];
        }
        // Allow explicit simulation for demo purposes
        if (request()->has('force_weather')) {
            $main = request()->query('force_weather');
            return [
                'name' => $city,
                'weather' => [['main' => $main, 'description' => strtolower($main) . ' (simulated)']],
                'main' => ['temp' => 28]
            ];
        }

        $apiKey = env('OPENWEATHER_API_KEY');
        
        if (empty($apiKey)) {
            throw new \Exception("OpenWeather API key is missing. Please configure OPENWEATHER_API_KEY in .env");
        }

        $params = [
            'appid' => $apiKey,
            'units' => 'metric'
        ];

        if ($lat !== null && $lon !== null) {
            $params['lat'] = $lat;
            $params['lon'] = $lon;
        } else {
            $params['q'] = $city;
        }

        $response = Http::withoutVerifying()->get('https://api.openweathermap.org/data/2.5/weather', $params);

        if ($response->status() === 404) {
            throw new \Exception("Kota '" . ($city ?: 'koordinat ini') . "' tidak ditemukan di database cuaca.");
        }

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch weather data: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Rule-engine to evaluate if weather implies rain.
     */
    private function evaluateWeather(array $weatherData): bool
    {
        if (!isset($weatherData['weather'][0]['main'])) {
            return false;
        }

        $condition = strtolower($weatherData['weather'][0]['main']);
        
        // Define bad weather conditions
        $rainyConditions = ['rain', 'drizzle', 'thunderstorm', 'snow'];
        
        return in_array($condition, $rainyConditions);
    }
}
