<?php

namespace App\Services;

use App\Repositories\Contracts\CityRepositoryInterface;
use App\Repositories\Contracts\WeatherRepositoryInterface;
use App\Repositories\Contracts\DestinationRepositoryInterface;
use App\Repositories\Contracts\RecommendationRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherAdaptiveGuideService
{
    protected $cityRepo;
    protected $weatherRepo;
    protected $destinationRepo;
    protected $recommendationRepo;

    public function __construct(
        CityRepositoryInterface $cityRepo,
        WeatherRepositoryInterface $weatherRepo,
        DestinationRepositoryInterface $destinationRepo,
        RecommendationRepositoryInterface $recommendationRepo
    ) {
        $this->cityRepo = $cityRepo;
        $this->weatherRepo = $weatherRepo;
        $this->destinationRepo = $destinationRepo;
        $this->recommendationRepo = $recommendationRepo;
    }

    /**
     * Generate weather-adaptive recommendations for a given city using Google Places API.
     */
    public function getRecommendations($cityId, $userLat = null, $userLng = null, $date = null)
    {
        $city = $this->cityRepo->findById($cityId);
        if (!$city) {
            return [
                'is_error' => true,
                'error_message' => "Kota tidak ditemukan."
            ];
        }

        $weather = $this->weatherRepo->findByCityId($cityId);
        
        $openWeatherKey = config('services.openweather.api_key');
        
        $isFutureDate = false;
        if ($date) {
            $dateObj = \Carbon\Carbon::parse($date);
            if ($dateObj->isFuture() && !$dateObj->isToday()) {
                $isFutureDate = true;
            }
        }

        // Fetch live weather if we don't have it, or if it hasn't been updated in the last 15 minutes, or if future date is requested.
        if ($openWeatherKey && ($isFutureDate || !$weather || $weather->updated_at->diffInMinutes(now()) > 15)) {
            try {
                $cityCoords = [
                    'Jakarta' => [-6.2088, 106.8456],
                    'Malang' => [-7.9839, 112.6214],
                    'Bandung' => [-6.9175, 107.6191],
                    'Bogor' => [-6.5971, 106.8060],
                    'Batu' => [-7.8712, 112.5269],
                    'Bali' => [-8.4095, 115.1889],
                    'Yogyakarta' => [-7.7956, 110.3695]
                ];
                $coords = $cityCoords[$city->name] ?? null;
                
                if ($coords) {
                    if ($isFutureDate) {
                        $weatherUrl = "https://api.openweathermap.org/data/2.5/forecast";
                        $weatherRes = Http::withoutVerifying()->get($weatherUrl, [
                            'lat' => $coords[0],
                            'lon' => $coords[1],
                            'appid' => $openWeatherKey,
                            'units' => 'metric'
                        ]);
                    } else {
                        $weatherUrl = "https://api.openweathermap.org/data/2.5/weather";
                        $weatherRes = Http::withoutVerifying()->get($weatherUrl, [
                            'lat' => $coords[0],
                            'lon' => $coords[1],
                            'appid' => $openWeatherKey,
                            'units' => 'metric'
                        ]);
                    }
                    
                    if ($weatherRes->successful()) {
                        $wData = $weatherRes->json();
                        
                        if ($isFutureDate) {
                            $targetDateString = $dateObj->format('Y-m-d');
                            $targetTimestamp = $dateObj->timestamp;
                            $closestForecast = null;
                            $minDiff = PHP_INT_MAX;
                            
                            foreach ($wData['list'] as $forecast) {
                                if (str_starts_with($forecast['dt_txt'], $targetDateString)) {
                                    $forecastTimestamp = \Carbon\Carbon::parse($forecast['dt_txt'])->timestamp;
                                    $diff = abs($forecastTimestamp - $targetTimestamp);
                                    
                                    if ($diff < $minDiff) {
                                        $minDiff = $diff;
                                        $closestForecast = $forecast;
                                    }
                                }
                            }
                            
                            if ($closestForecast) {
                                $mainWeather = $closestForecast['weather'][0]['main'] ?? 'Clear';
                                $temp = $closestForecast['main']['temp'] ?? 28;
                                $hum = $closestForecast['main']['humidity'] ?? 70;
                                $wind = $closestForecast['wind']['speed'] ?? 10;
                            } else {
                                // Fallback to normal if date not in 5 days
                                $mainWeather = $wData['list'][0]['weather'][0]['main'] ?? 'Clear';
                                $temp = $wData['list'][0]['main']['temp'] ?? 28;
                                $hum = $wData['list'][0]['main']['humidity'] ?? 70;
                                $wind = $wData['list'][0]['wind']['speed'] ?? 10;
                            }
                        } else {
                            $mainWeather = $wData['weather'][0]['main'] ?? 'Clear';
                            $temp = $wData['main']['temp'] ?? 28;
                            $hum = $wData['main']['humidity'] ?? 70;
                            $wind = $wData['wind']['speed'] ?? 10;
                        }
                        
                        $weatherStatus = 'Cerah';
                        if (in_array(strtolower($mainWeather), ['rain', 'drizzle', 'thunderstorm'])) {
                            $weatherStatus = 'Hujan';
                        } elseif (in_array(strtolower($mainWeather), ['clouds', 'mist', 'haze', 'fog'])) {
                            $weatherStatus = 'Berawan';
                        }
                        
                        $weather = $this->weatherRepo->updateWeather($cityId, [
                            'status' => $weatherStatus,
                            'temperature' => $temp,
                            'humidity' => $hum,
                            'wind_speed' => $wind
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed fetching weather for city: " . $e->getMessage());
            }
        }

        if (!$weather) {
            $weather = $this->weatherRepo->updateWeather($cityId, [
                'status' => 'Cerah',
                'temperature' => 28,
                'humidity' => 70,
                'wind_speed' => 10
            ]);
        }

        $weatherStatus = $weather->status; // Cerah, Berawan, Hujan

        // City default coordinates mapping
        $cityCoords = [
            'Jakarta' => [-6.2088, 106.8456],
            'Malang' => [-7.9839, 112.6214],
            'Bandung' => [-6.9175, 107.6191],
            'Bogor' => [-6.5971, 106.8060],
            'Batu' => [-7.8712, 112.5269]
        ];

        $cityName = $city->name;
        $coords = $cityCoords[$cityName] ?? [-7.8712, 112.5269];

        $startingLat = $coords[0];
        $startingLng = $coords[1];

        // 1. Fetch live destinations using Google Places API or mock fallback
        $googleKey = config('services.google.maps_api_key');
        $rawPlaces = [];

        if ($googleKey) {
            try {
                $placesUrl = "https://maps.googleapis.com/maps/api/place/nearbysearch/json";
                $placesRes = Http::withoutVerifying()->get($placesUrl, [
                    'location' => "{$startingLat},{$startingLng}",
                    'radius' => 5000,
                    'keyword' => 'indoor|outdoor|museum|park|mall|attraction',
                    'key' => $googleKey
                ]);

                if ($placesRes->successful()) {
                    $rawPlaces = $placesRes->json()['results'] ?? [];
                }
            } catch (\Exception $e) {
                Log::error("Failed fetching Google Places for Dashboard: " . $e->getMessage());
            }
        }

        // Fallback mock data if API fails or is not configured
        if (empty($rawPlaces)) {
            $mockAlternatives = [
                'Batu' => [
                    [
                        'name' => 'Museum Angkut',
                        'lat' => -7.8794,
                        'lng' => 112.5186,
                        'category' => 'indoor',
                        'rating' => 4.8,
                        'description' => 'Museum transportasi terbesar di Asia dengan koleksi mobil antik dari berbagai penjuru dunia.',
                        'address' => 'Jl. Terusan Sultan Agung No.2, Ngaglik, Kec. Batu',
                        'open_now' => true,
                        'opening_hours' => '12.00 - 20.00',
                        'photo' => 'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?q=80&w=600'
                    ],
                    [
                        'name' => 'Lippo Plaza Batu',
                        'lat' => -7.8741,
                        'lng' => 112.5283,
                        'category' => 'indoor',
                        'rating' => 4.3,
                        'description' => 'Pusat perbelanjaan ber-AC yang nyaman dengan gerai ritel modern, restoran, bioskop, dan area bermain anak.',
                        'address' => 'Jl. Diponegoro No.1, Sisir, Kec. Batu',
                        'open_now' => true,
                        'opening_hours' => '10.00 - 22.00',
                        'photo' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=600'
                    ],
                    [
                        'name' => 'Alun-Alun Kota Batu',
                        'lat' => -7.8712,
                        'lng' => 112.5269,
                        'category' => 'outdoor',
                        'rating' => 4.6,
                        'description' => 'Taman bermain terbuka yang ramah keluarga dengan bianglala ikonik dan pasar kuliner malam hari.',
                        'address' => 'Jl. Diponegoro, Sisir, Kec. Batu, Kota Batu',
                        'open_now' => true,
                        'opening_hours' => 'Buka 24 jam',
                        'photo' => 'https://images.unsplash.com/photo-1490730141103-6cac27aaab94?q=80&w=600'
                    ],
                    [
                        'name' => 'Coban Rondo Waterfall',
                        'lat' => -7.8847,
                        'lng' => 112.4772,
                        'category' => 'outdoor',
                        'rating' => 4.5,
                        'description' => 'Wisata air terjun alam terbuka yang menawan di lereng gunung dengan labirin tanaman dan wahana outbound.',
                        'address' => 'Kec. Pujon, Kabupaten Malang',
                        'open_now' => true,
                        'opening_hours' => '07.00 - 17.00',
                        'photo' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?q=80&w=600'
                    ]
                ],
                'Jakarta' => [
                    [
                        'name' => 'Museum Nasional Indonesia',
                        'lat' => -6.1764,
                        'lng' => 106.8219,
                        'category' => 'indoor',
                        'rating' => 4.6,
                        'description' => 'Museum arkeologi, sejarah, etnografi, dan geografi yang menyimpan kekayaan sejarah budaya Indonesia.',
                        'address' => 'Jl. Medan Merdeka Barat No.12, Jakarta Pusat',
                        'open_now' => true,
                        'opening_hours' => '08.00 - 16.00',
                        'photo' => 'https://images.unsplash.com/photo-1580537659444-230f2c5f6eef?q=80&w=600'
                    ],
                    [
                        'name' => 'Grand Indonesia Mall',
                        'lat' => -6.1951,
                        'lng' => 106.8208,
                        'category' => 'indoor',
                        'rating' => 4.7,
                        'description' => 'Pusat belanja mewah terintegrasi di pusat kota Jakarta dengan zona restoran internasional.',
                        'address' => 'Jl. M.H. Thamrin No.1, Jakarta Pusat',
                        'open_now' => true,
                        'opening_hours' => '10.00 - 22.00',
                        'photo' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=600'
                    ],
                    [
                        'name' => 'Monumen Nasional (Monas)',
                        'lat' => -6.1754,
                        'lng' => 106.8272,
                        'category' => 'outdoor',
                        'rating' => 4.6,
                        'description' => 'Monumen peringatan setinggi 132 meter yang terletak di area taman terbuka hijau luas di Jakarta.',
                        'address' => 'Gambir, Kec. Gambir, Kota Jakarta Pusat',
                        'open_now' => true,
                        'opening_hours' => '06.00 - 16.00',
                        'photo' => 'https://images.unsplash.com/photo-1555899434-94d1368aa7af?q=80&w=600'
                    ]
                ],
                'Malang' => [
                    [
                        'name' => 'Museum Malang Tempo Doeloe',
                        'lat' => -7.9782,
                        'lng' => 112.6355,
                        'category' => 'indoor',
                        'rating' => 4.4,
                        'description' => 'Museum sejarah lokal yang menyimpan koleksi benda antik dan dokumentasi awal mula Kota Malang.',
                        'address' => 'Jl. Gajahmada No.2, Kiduldalem, Kec. Klojen, Malang',
                        'open_now' => true,
                        'opening_hours' => '08.00 - 17.00',
                        'photo' => 'https://images.unsplash.com/photo-1580537659444-230f2c5f6eef?q=80&w=600'
                    ],
                    [
                        'name' => 'Malang Town Square (Matos)',
                        'lat' => -7.9571,
                        'lng' => 112.6186,
                        'category' => 'indoor',
                        'rating' => 4.3,
                        'description' => 'Mall terpopuler bagi mahasiswa dan keluarga dengan beragam fasilitas bioskop, kuliner, dan belanja fashion.',
                        'address' => 'Jl. Veteran No.2, Penanggungan, Kec. Klojen, Malang',
                        'open_now' => true,
                        'opening_hours' => '10.00 - 22.00',
                        'photo' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=600'
                    ],
                    [
                        'name' => 'Alun-Alun Tugu Malang',
                        'lat' => -7.9774,
                        'lng' => 112.6341,
                        'category' => 'outdoor',
                        'rating' => 4.5,
                        'description' => 'Taman bundar ikonik dengan tugu lilin di tengah kolam teratai terbuka, sejuk untuk bersantai sore hari.',
                        'address' => 'Jl. Tugu, Kiduldalem, Kec. Klojen, Kota Malang',
                        'open_now' => true,
                        'opening_hours' => 'Buka 24 jam',
                        'photo' => 'https://images.unsplash.com/photo-1490730141103-6cac27aaab94?q=80&w=600'
                    ]
                ],
                'Bandung' => [
                    [
                        'name' => 'Museum Geologi Bandung',
                        'lat' => -6.9007,
                        'lng' => 107.6215,
                        'category' => 'indoor',
                        'rating' => 4.5,
                        'description' => 'Museum sejarah bumi dan kehidupan purba terlengkap di Indonesia dengan replika fosil dinosaurus.',
                        'address' => 'Jl. Diponegoro No.57, Cihaur Geulis, Kec. Cibeunying Kaler, Bandung',
                        'open_now' => true,
                        'opening_hours' => '09.00 - 15.00',
                        'photo' => 'https://images.unsplash.com/photo-1580537659444-230f2c5f6eef?q=80&w=600'
                    ],
                    [
                        'name' => 'Trans Studio Bandung',
                        'lat' => -6.9251,
                        'lng' => 107.6366,
                        'category' => 'indoor',
                        'rating' => 4.6,
                        'description' => 'Taman bermain indoor (dalam ruangan) berstandar internasional dengan puluhan wahana menantang.',
                        'address' => 'Jl. Gatot Subroto No.289A, Cibangkong, Bandung',
                        'open_now' => true,
                        'opening_hours' => '10.00 - 17.00',
                        'photo' => 'https://images.unsplash.com/photo-1498038432885-c6f3f1b912ee?q=80&w=600'
                    ],
                    [
                        'name' => 'Taman Hutan Raya Juanda',
                        'lat' => -6.8566,
                        'lng' => 107.6327,
                        'category' => 'outdoor',
                        'rating' => 4.6,
                        'description' => 'Kawasan konservasi alam terpadu bernuansa hutan pinus asri dengan gua bersejarah peninggalan Belanda dan Jepang.',
                        'address' => 'Kec. Cimenyan, Kabupaten Bandung',
                        'open_now' => true,
                        'opening_hours' => '08.00 - 18.00',
                        'photo' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?q=80&w=600'
                    ]
                ],
                'Bogor' => [
                    [
                        'name' => 'Museum Zoologi Bogor',
                        'lat' => -6.6025,
                        'lng' => 106.7972,
                        'category' => 'indoor',
                        'rating' => 4.6,
                        'description' => 'Museum yang menyimpan koleksi fosil dan replika hewan yang dikeringkan dari berbagai habitat di Indonesia.',
                        'address' => 'Jl. Ir. H. Juanda No.9, Paledang, Kec. Bogor Tengah, Bogor',
                        'open_now' => true,
                        'opening_hours' => '08.00 - 16.00',
                        'photo' => 'https://images.unsplash.com/photo-1580537659444-230f2c5f6eef?q=80&w=600'
                    ],
                    [
                        'name' => 'Botani Square Mall',
                        'lat' => -6.6015,
                        'lng' => 106.8058,
                        'category' => 'indoor',
                        'rating' => 4.5,
                        'description' => 'Pusat belanja modern terbesar di Bogor yang berdekatan dengan gerbang tol dan Kebun Raya Bogor.',
                        'address' => 'Jl. Raya Pajajaran No.3, Tegallega, Kec. Bogor Tengah, Bogor',
                        'open_now' => true,
                        'opening_hours' => '10.00 - 22.00',
                        'photo' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=600'
                    ],
                    [
                        'name' => 'Kebun Raya Bogor',
                        'lat' => -6.5976,
                        'lng' => 106.7996,
                        'category' => 'outdoor',
                        'rating' => 4.7,
                        'description' => 'Kebun botani legendaris peninggalan kolonial dengan danau teratai, pemakaman kuno, dan koleksi anggrek langka.',
                        'address' => 'Jl. Ir. H. Juanda No.13, Kota Bogor',
                        'open_now' => true,
                        'opening_hours' => '08.00 - 17.00',
                        'photo' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?q=80&w=600'
                    ]
                ],
                'Bali' => [
                    [
                        'name' => 'Pantai Kuta',
                        'lat' => -8.7185,
                        'lng' => 115.1686,
                        'category' => 'outdoor',
                        'rating' => 4.5,
                        'description' => 'Pantai berpasir putih legendaris yang sempurna untuk bersantai.',
                        'address' => 'Kuta, Kabupaten Badung, Bali',
                        'open_now' => true,
                        'opening_hours' => '24 Jam',
                        'photo' => 'https://images.unsplash.com/photo-1577717903315-1691ae25ab3f?q=80&w=600'
                    ],
                    [
                        'name' => 'Garuda Wisnu Kencana (GWK)',
                        'lat' => -8.8104,
                        'lng' => 115.1676,
                        'category' => 'outdoor',
                        'rating' => 4.7,
                        'description' => 'Taman budaya dengan patung raksasa GWK.',
                        'address' => 'Jl. Raya Uluwatu, Ungasan, Kuta Selatan',
                        'open_now' => true,
                        'opening_hours' => '08:00 - 21:00',
                        'photo' => 'https://images.unsplash.com/photo-1554481923-a6918bd997bc?q=80&w=600'
                    ]
                ],
                'Yogyakarta' => [
                    [
                        'name' => 'Candi Prambanan',
                        'lat' => -7.7520,
                        'lng' => 110.4914,
                        'category' => 'outdoor',
                        'rating' => 4.8,
                        'description' => 'Kompleks candi Hindu terbesar di Indonesia yang megah.',
                        'address' => 'Jl. Raya Solo - Yogyakarta, Prambanan',
                        'open_now' => true,
                        'opening_hours' => '06:00 - 17:00',
                        'photo' => 'https://images.unsplash.com/photo-1584810359583-96fc3448beaa?q=80&w=600'
                    ],
                    [
                        'name' => 'Museum Ullen Sentalu',
                        'lat' => -7.5976,
                        'lng' => 110.4234,
                        'category' => 'indoor',
                        'rating' => 4.9,
                        'description' => 'Museum budaya dan seni Jawa yang terletak di kawasan Kaliurang.',
                        'address' => 'Jl. Boyong, Kaliurang, Sleman',
                        'open_now' => true,
                        'opening_hours' => '08:30 - 16:00',
                        'photo' => 'https://images.unsplash.com/photo-1596401057633-54a8fe8ef647?q=80&w=600'
                    ]
                ]
            ];

            $rawPlaces = $mockAlternatives[$cityName] ?? $mockAlternatives['Batu'];
        }

        // Process places and calculate suitability score
        $destinationsList = [];
        foreach ($rawPlaces as $index => $item) {
            if (isset($item['place_id'])) {
                $lat = $item['geometry']['location']['lat'];
                $lng = $item['geometry']['location']['lng'];
                $name = $item['name'];
                $rating = $item['rating'] ?? 4.0;
                $address = $item['vicinity'] ?? $item['formatted_address'] ?? 'Alamat tidak tersedia';
                $openNow = $item['opening_hours']['open_now'] ?? true;
                $openingHours = $openNow ? 'Buka Sekarang' : 'Tutup';
                $description = "Destinasi terpopuler sekitar koordinat wilayah. Ditemukan menggunakan Google Places API.";
                
                $photoRef = $item['photos'][0]['photo_reference'] ?? null;
                $photo = $photoRef 
                    ? "https://maps.googleapis.com/maps/api/place/photo?maxwidth=600&photo_reference={$photoRef}&key={$googleKey}" 
                    : "https://placehold.co/600x400/1e293b/ffffff.png?text=" . urlencode($name);
                
                // Categorize based on keywords/types
                $types = $item['types'] ?? [];
                $category = 'outdoor';
                $indoorKeywords = ['shopping_mall', 'museum', 'art_gallery', 'cafe', 'restaurant', 'library', 'aquarium', 'bowling_alley'];
                foreach ($types as $type) {
                    if (in_array($type, $indoorKeywords)) {
                        $category = 'indoor';
                        break;
                    }
                }
            } else {
                $lat = $item['lat'];
                $lng = $item['lng'];
                $name = $item['name'];
                $rating = $item['rating'];
                $address = $item['address'];
                $openNow = $item['open_now'];
                $openingHours = $item['opening_hours'];
                $photo = $googleKey 
                    ? "https://maps.googleapis.com/maps/api/staticmap?center={$lat},{$lng}&zoom=15&size=600x400&markers=color:red%7C{$lat},{$lng}&key={$googleKey}" 
                    : "https://placehold.co/600x400/1e293b/ffffff.png?text=" . urlencode($name);
                $category = $item['category'];
                $description = $item['description'];
            }

            // Strict Filter for Hujan
            if ($weatherStatus === 'Hujan' && $category === 'outdoor') {
                continue;
            }

            // Calculate distance
            $distance = $this->calculateDistance($startingLat, $startingLng, $lat, $lng);

            // Calculate Suitability Score
            $catScore = ($category === 'indoor') ? 30 : 5;
            if ($weatherStatus !== 'Hujan') {
                $catScore = ($category === 'outdoor') ? 30 : 15;
            }

            $ratingScore = $rating * 6;
            $distScore = max(0, 30 - ($distance * 4));
            $openScore = $openNow ? 10 : 0;
            $suitabilityScore = round($catScore + $ratingScore + $distScore + $openScore);

            $destinationsList[] = [
                'id' => $index + 1,
                'name' => $name,
                'description' => $description,
                'category' => $category,
                'image_url' => $photo,
                'opening_hours' => $openingHours,
                'rating' => $rating,
                'lat' => $lat,
                'lng' => $lng,
                'distance' => $distance,
                'suitability_score' => $suitabilityScore,
                'city' => ['name' => $cityName]
            ];
        }

        // Sort by suitability score descending
        usort($destinationsList, function ($a, $b) {
            return $b['suitability_score'] <=> $a['suitability_score'];
        });

        // Limit list dynamically depending on weather
        if ($weatherStatus === 'Hujan') {
            // Rank and filter primarily to Indoor
            $categoryText = "indoor (dalam ruangan)";
        } elseif ($weatherStatus === 'Cerah') {
            $categoryText = "outdoor (luar ruangan)";
        } else {
            $categoryText = "indoor maupun outdoor";
        }

        // Generasikan reasoning cerdas dari agen
        $destNames = collect($destinationsList)->take(3)->pluck('name')->toArray();
        if (count($destNames) > 0) {
            if (count($destNames) === 1) {
                $destList = $destNames[0];
            } else {
                $lastDest = array_pop($destNames);
                $destList = implode(', ', $destNames) . ' dan ' . $lastDest;
            }
            $reason = "Karena cuaca di {$cityName} saat ini {$weatherStatus}, sistem Google Maps Places merekomendasikan {$destList} dengan kecocokan kenyamanan terbaik.";
        } else {
            $reason = "Cuaca di {$cityName} terpantau {$weatherStatus}. Kami menyarankan destinasi {$categoryText}.";
        }

        // Check if recommendation needs to be updated (and log it if status or reason changes)
        $oldRec = $this->recommendationRepo->getRecommendationByCity($cityId);
        $statusChanged = !$oldRec || $oldRec->weather_status !== $weatherStatus || $oldRec->reason !== $reason;

        if ($statusChanged) {
            $this->recommendationRepo->updateOrCreateRecommendation($cityId, [
                'weather_status' => $weatherStatus,
                'reason' => $reason
            ]);

            $this->recommendationRepo->createLog([
                'city_id' => $cityId,
                'weather_status' => $weatherStatus,
                'reason' => $reason
            ]);
        }

        return [
            'is_error' => false,
            'city' => $city,
            'weather' => $weather,
            'reason' => $reason,
            'destinations' => $destinationsList,
            'logs' => $this->recommendationRepo->getLogsByCity($cityId),
            'status_changed' => $statusChanged,
            'google_maps_api_key' => $googleKey ?: ''
        ];
    }

    /**
     * Update weather and trigger agentic recommendation update.
     */
    public function updateCityWeather($cityId, $status, $temp = 28, $humidity = 70, $windSpeed = 10)
    {
        $this->weatherRepo->updateWeather($cityId, [
            'status' => $status,
            'temperature' => $temp,
            'humidity' => $humidity,
            'wind_speed' => $windSpeed
        ]);

        return $this->getRecommendations($cityId);
    }

    /**
     * Get recommendations using absolute latitude and longitude coordinates.
     */
    public function getRecommendationsForCoordinates($lat, $lng, $locationName = null, $date = null)
    {
        $locationName = $locationName ?: 'Lokasi Pencarian';
        
        $isFutureDate = false;
        if ($date) {
            $dateObj = \Carbon\Carbon::parse($date);
            if ($dateObj->isFuture() && !$dateObj->isToday()) {
                $isFutureDate = true;
            }
        }

        // 1. Get live weather from OpenWeather API using coordinates
        $weatherStatus = 'Cerah';
        $weatherTemp = 28;
        $weatherHumidity = 70;
        $weatherWind = 10;
        
        // Allow weather simulation override
        if (request()->has('force_weather')) {
            $weatherStatus = request()->query('force_weather');
            $weatherTemp = $weatherStatus === 'Hujan' ? 22 : ($weatherStatus === 'Berawan' ? 25 : 29);
        } else {
            $openWeatherKey = config('services.openweather.api_key');
            if ($openWeatherKey) {
                try {
                    if ($isFutureDate) {
                        $weatherUrl = "https://api.openweathermap.org/data/2.5/forecast";
                    } else {
                        $weatherUrl = "https://api.openweathermap.org/data/2.5/weather";
                    }

                    $weatherRes = Http::withoutVerifying()->get($weatherUrl, [
                        'lat' => $lat,
                        'lon' => $lng,
                        'appid' => $openWeatherKey,
                        'units' => 'metric'
                    ]);
                    
                    if ($weatherRes->successful()) {
                        $wData = $weatherRes->json();
                        
                        if ($isFutureDate) {
                            $targetDateString = $dateObj->format('Y-m-d');
                            $targetTimestamp = $dateObj->timestamp;
                            $closestForecast = null;
                            $minDiff = PHP_INT_MAX;
                            
                            foreach ($wData['list'] as $forecast) {
                                if (str_starts_with($forecast['dt_txt'], $targetDateString)) {
                                    $forecastTimestamp = \Carbon\Carbon::parse($forecast['dt_txt'])->timestamp;
                                    $diff = abs($forecastTimestamp - $targetTimestamp);
                                    
                                    if ($diff < $minDiff) {
                                        $minDiff = $diff;
                                        $closestForecast = $forecast;
                                    }
                                }
                            }
                            
                            if ($closestForecast) {
                                $mainWeather = $closestForecast['weather'][0]['main'] ?? 'Clear';
                                $weatherTemp = $closestForecast['main']['temp'] ?? 28;
                                $weatherHumidity = $closestForecast['main']['humidity'] ?? 70;
                                $weatherWind = $closestForecast['wind']['speed'] ?? 10;
                            } else {
                                $mainWeather = $wData['list'][0]['weather'][0]['main'] ?? 'Clear';
                                $weatherTemp = $wData['list'][0]['main']['temp'] ?? 28;
                                $weatherHumidity = $wData['list'][0]['main']['humidity'] ?? 70;
                                $weatherWind = $wData['list'][0]['wind']['speed'] ?? 10;
                            }
                        } else {
                            $mainWeather = $wData['weather'][0]['main'] ?? 'Clear';
                            $weatherTemp = $wData['main']['temp'] ?? 28;
                            $weatherHumidity = $wData['main']['humidity'] ?? 70;
                            $weatherWind = $wData['wind']['speed'] ?? 10;
                        }

                        if (in_array(strtolower($mainWeather), ['rain', 'drizzle', 'thunderstorm'])) {
                            $weatherStatus = 'Hujan';
                        } elseif (in_array(strtolower($mainWeather), ['clouds', 'mist', 'haze', 'fog'])) {
                            $weatherStatus = 'Berawan';
                        } else {
                            $weatherStatus = 'Cerah';
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Failed fetching live weather for coordinates: " . $e->getMessage());
                }
            }
        }

        // Mock weather object to keep frontend structure happy
        $weather = (object)[
            'status' => $weatherStatus,
            'temperature' => $weatherTemp,
            'humidity' => $weatherHumidity,
            'wind_speed' => $weatherWind,
            'updated_at' => now()->toIso8601String()
        ];

        // 2. Fetch live destinations using Google Places API or mock fallback
        $googleKey = config('services.google.maps_api_key');
        $rawPlaces = [];

        if ($googleKey) {
            try {
                $placesUrl = "https://maps.googleapis.com/maps/api/place/nearbysearch/json";
                $placesRes = Http::withoutVerifying()->get($placesUrl, [
                    'location' => "{$lat},{$lng}",
                    'radius' => 5000,
                    'keyword' => 'indoor|outdoor|museum|park|mall|attraction',
                    'key' => $googleKey
                ]);

                if ($placesRes->successful()) {
                    $rawPlaces = $placesRes->json()['results'] ?? [];
                }
            } catch (\Exception $e) {
                Log::error("Failed fetching Google Places for coordinates: " . $e->getMessage());
            }
        }

        // Fallback mock data if API fails or is not configured
        if (empty($rawPlaces)) {
            $mockCities = [
                'Batu' => [-7.8712, 112.5269],
                'Jakarta' => [-6.2088, 106.8456],
                'Malang' => [-7.9839, 112.6214],
                'Bandung' => [-6.9175, 107.6191],
                'Bogor' => [-6.5971, 106.8060],
                'Bali' => [-8.4095, 115.1889],
                'Yogyakarta' => [-7.7956, 110.3695]
            ];
            
            $fallbackCity = null;
            $minDistance = 100; // max radius 100km
            
            foreach ($mockCities as $cName => $coords) {
                // Haversine formula
                $earthRadius = 6371; // km
                $latFrom = deg2rad($lat);
                $lonFrom = deg2rad($lng);
                $latTo = deg2rad($coords[0]);
                $lonTo = deg2rad($coords[1]);

                $latDelta = $latTo - $latFrom;
                $lonDelta = $lonTo - $lonFrom;

                $a = sin($latDelta / 2) * sin($latDelta / 2) +
                    cos($latFrom) * cos($latTo) *
                    sin($lonDelta / 2) * sin($lonDelta / 2);
                $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                
                $distance = $earthRadius * $c;
                
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $fallbackCity = $cName;
                }
            }

            $mockAlternatives = [
                'Batu' => [
                    [
                        'name' => 'Museum Angkut',
                        'lat' => -7.8794,
                        'lng' => 112.5186,
                        'category' => 'indoor',
                        'rating' => 4.8,
                        'description' => 'Museum transportasi terbesar di Asia dengan koleksi mobil antik dari berbagai penjuru dunia.',
                        'address' => 'Jl. Terusan Sultan Agung No.2, Ngaglik, Kec. Batu',
                        'open_now' => true,
                        'opening_hours' => '12.00 - 20.00',
                        'photo' => 'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?q=80&w=600'
                    ],
                    [
                        'name' => 'Lippo Plaza Batu',
                        'lat' => -7.8741,
                        'lng' => 112.5283,
                        'category' => 'indoor',
                        'rating' => 4.3,
                        'description' => 'Pusat perbelanjaan ber-AC yang nyaman dengan gerai ritel modern, restoran, bioskop, dan area bermain anak.',
                        'address' => 'Jl. Diponegoro No.1, Sisir, Kec. Batu',
                        'open_now' => true,
                        'opening_hours' => '10.00 - 22.00',
                        'photo' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=600'
                    ],
                    [
                        'name' => 'Alun-Alun Kota Batu',
                        'lat' => -7.8712,
                        'lng' => 112.5269,
                        'category' => 'outdoor',
                        'rating' => 4.6,
                        'description' => 'Taman bermain terbuka yang ramah keluarga dengan bianglala ikonik dan pasar kuliner malam hari.',
                        'address' => 'Jl. Diponegoro, Sisir, Kec. Batu, Kota Batu',
                        'open_now' => true,
                        'opening_hours' => 'Buka 24 jam',
                        'photo' => 'https://images.unsplash.com/photo-1490730141103-6cac27aaab94?q=80&w=600'
                    ],
                    [
                        'name' => 'Coban Rondo Waterfall',
                        'lat' => -7.8847,
                        'lng' => 112.4772,
                        'category' => 'outdoor',
                        'rating' => 4.5,
                        'description' => 'Wisata air terjun alam terbuka yang menawan di lereng gunung dengan labirin tanaman dan wahana outbound.',
                        'address' => 'Kec. Pujon, Kabupaten Malang',
                        'open_now' => true,
                        'opening_hours' => '07.00 - 17.00',
                        'photo' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?q=80&w=600'
                    ]
                ],
                'Jakarta' => [
                    [
                        'name' => 'Museum Nasional Indonesia',
                        'lat' => -6.1764,
                        'lng' => 106.8219,
                        'category' => 'indoor',
                        'rating' => 4.6,
                        'description' => 'Museum arkeologi, sejarah, etnografi, dan geografi yang menyimpan kekayaan sejarah budaya Indonesia.',
                        'address' => 'Jl. Medan Merdeka Barat No.12, Jakarta Pusat',
                        'open_now' => true,
                        'opening_hours' => '08.00 - 16.00',
                        'photo' => 'https://images.unsplash.com/photo-1580537659444-230f2c5f6eef?q=80&w=600'
                    ],
                    [
                        'name' => 'Grand Indonesia Mall',
                        'lat' => -6.1951,
                        'lng' => 106.8208,
                        'category' => 'indoor',
                        'rating' => 4.7,
                        'description' => 'Pusat belanja mewah terintegrasi di pusat kota Jakarta dengan zona restoran internasional.',
                        'address' => 'Jl. M.H. Thamrin No.1, Jakarta Pusat',
                        'open_now' => true,
                        'opening_hours' => '10.00 - 22.00',
                        'photo' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=600'
                    ],
                    [
                        'name' => 'Monumen Nasional (Monas)',
                        'lat' => -6.1754,
                        'lng' => 106.8272,
                        'category' => 'outdoor',
                        'rating' => 4.6,
                        'description' => 'Monumen peringatan setinggi 132 meter yang terletak di area taman terbuka hijau luas di Jakarta.',
                        'address' => 'Gambir, Kec. Gambir, Kota Jakarta Pusat',
                        'open_now' => true,
                        'opening_hours' => '06.00 - 16.00',
                        'photo' => 'https://images.unsplash.com/photo-1555899434-94d1368aa7af?q=80&w=600'
                    ]
                ],
                'Malang' => [
                    [
                        'name' => 'Museum Malang Tempo Doeloe',
                        'lat' => -7.9782,
                        'lng' => 112.6355,
                        'category' => 'indoor',
                        'rating' => 4.4,
                        'description' => 'Museum sejarah lokal yang menyimpan koleksi benda antik dan dokumentasi awal mula Kota Malang.',
                        'address' => 'Jl. Gajahmada No.2, Kiduldalem, Kec. Klojen, Malang',
                        'open_now' => true,
                        'opening_hours' => '08.00 - 17.00',
                        'photo' => 'https://images.unsplash.com/photo-1580537659444-230f2c5f6eef?q=80&w=600'
                    ],
                    [
                        'name' => 'Malang Town Square (Matos)',
                        'lat' => -7.9571,
                        'lng' => 112.6186,
                        'category' => 'indoor',
                        'rating' => 4.3,
                        'description' => 'Mall terpopuler bagi mahasiswa dan keluarga dengan beragam fasilitas bioskop, kuliner, dan belanja fashion.',
                        'address' => 'Jl. Veteran No.2, Penanggungan, Kec. Klojen, Malang',
                        'open_now' => true,
                        'opening_hours' => '10.00 - 22.00',
                        'photo' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=600'
                    ],
                    [
                        'name' => 'Alun-Alun Tugu Malang',
                        'lat' => -7.9774,
                        'lng' => 112.6341,
                        'category' => 'outdoor',
                        'rating' => 4.5,
                        'description' => 'Taman bundar ikonik dengan tugu lilin di tengah kolam teratai terbuka, sejuk untuk bersantai sore hari.',
                        'address' => 'Jl. Tugu, Kiduldalem, Kec. Klojen, Kota Malang',
                        'open_now' => true,
                        'opening_hours' => 'Buka 24 jam',
                        'photo' => 'https://images.unsplash.com/photo-1490730141103-6cac27aaab94?q=80&w=600'
                    ]
                ],
                'Bandung' => [
                    [
                        'name' => 'Museum Geologi Bandung',
                        'lat' => -6.9007,
                        'lng' => 107.6215,
                        'category' => 'indoor',
                        'rating' => 4.5,
                        'description' => 'Museum sejarah bumi dan kehidupan purba terlengkap di Indonesia dengan replika fosil dinosaurus.',
                        'address' => 'Jl. Diponegoro No.57, Cihaur Geulis, Kec. Cibeunying Kaler, Bandung',
                        'open_now' => true,
                        'opening_hours' => '09.00 - 15.00',
                        'photo' => 'https://images.unsplash.com/photo-1580537659444-230f2c5f6eef?q=80&w=600'
                    ],
                    [
                        'name' => 'Trans Studio Bandung',
                        'lat' => -6.9251,
                        'lng' => 107.6366,
                        'category' => 'indoor',
                        'rating' => 4.6,
                        'description' => 'Taman bermain indoor (dalam ruangan) berstandar internasional dengan puluhan wahana menantang.',
                        'address' => 'Jl. Gatot Subroto No.289A, Cibangkong, Bandung',
                        'open_now' => true,
                        'opening_hours' => '10.00 - 17.00',
                        'photo' => 'https://images.unsplash.com/photo-1498038432885-c6f3f1b912ee?q=80&w=600'
                    ],
                    [
                        'name' => 'Taman Hutan Raya Juanda',
                        'lat' => -6.8566,
                        'lng' => 107.6327,
                        'category' => 'outdoor',
                        'rating' => 4.6,
                        'description' => 'Kawasan konservasi alam terpadu bernuansa hutan pinus asri dengan gua bersejarah peninggalan Belanda dan Jepang.',
                        'address' => 'Kec. Cimenyan, Kabupaten Bandung',
                        'open_now' => true,
                        'opening_hours' => '08.00 - 18.00',
                        'photo' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?q=80&w=600'
                    ]
                ],
                'Bogor' => [
                    [
                        'name' => 'Museum Zoologi Bogor',
                        'lat' => -6.6025,
                        'lng' => 106.7972,
                        'category' => 'indoor',
                        'rating' => 4.6,
                        'description' => 'Museum yang menyimpan koleksi fosil dan replika hewan yang dikeringkan dari berbagai habitat di Indonesia.',
                        'address' => 'Jl. Ir. H. Juanda No.9, Paledang, Kec. Bogor Tengah, Bogor',
                        'open_now' => true,
                        'opening_hours' => '08.00 - 16.00',
                        'photo' => 'https://images.unsplash.com/photo-1580537659444-230f2c5f6eef?q=80&w=600'
                    ],
                    [
                        'name' => 'Botani Square Mall',
                        'lat' => -6.6015,
                        'lng' => 106.8058,
                        'category' => 'indoor',
                        'rating' => 4.5,
                        'description' => 'Pusat belanja modern terbesar di Bogor yang berdekatan dengan gerbang tol dan Kebun Raya Bogor.',
                        'address' => 'Jl. Raya Pajajaran No.3, Tegallega, Kec. Bogor Tengah, Bogor',
                        'open_now' => true,
                        'opening_hours' => '10.00 - 22.00',
                        'photo' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=600'
                    ],
                    [
                        'name' => 'Kebun Raya Bogor',
                        'lat' => -6.5976,
                        'lng' => 106.7996,
                        'category' => 'outdoor',
                        'rating' => 4.7,
                        'description' => 'Kebun botani legendaris peninggalan kolonial dengan danau teratai, pemakaman kuno, dan koleksi anggrek langka.',
                        'address' => 'Jl. Ir. H. Juanda No.13, Kota Bogor',
                        'open_now' => true,
                        'opening_hours' => '08.00 - 17.00',
                        'photo' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?q=80&w=600'
                    ]
                ],
                'Bali' => [
                    [
                        'name' => 'Pantai Kuta',
                        'lat' => -8.7185,
                        'lng' => 115.1686,
                        'category' => 'outdoor',
                        'rating' => 4.5,
                        'description' => 'Pantai berpasir putih legendaris yang sempurna untuk bersantai.',
                        'address' => 'Kuta, Kabupaten Badung, Bali',
                        'open_now' => true,
                        'opening_hours' => '24 Jam',
                        'photo' => 'https://images.unsplash.com/photo-1577717903315-1691ae25ab3f?q=80&w=600'
                    ],
                    [
                        'name' => 'Garuda Wisnu Kencana (GWK)',
                        'lat' => -8.8104,
                        'lng' => 115.1676,
                        'category' => 'outdoor',
                        'rating' => 4.7,
                        'description' => 'Taman budaya dengan patung raksasa GWK.',
                        'address' => 'Jl. Raya Uluwatu, Ungasan, Kuta Selatan',
                        'open_now' => true,
                        'opening_hours' => '08:00 - 21:00',
                        'photo' => 'https://images.unsplash.com/photo-1554481923-a6918bd997bc?q=80&w=600'
                    ]
                ],
                'Yogyakarta' => [
                    [
                        'name' => 'Candi Prambanan',
                        'lat' => -7.7520,
                        'lng' => 110.4914,
                        'category' => 'outdoor',
                        'rating' => 4.8,
                        'description' => 'Kompleks candi Hindu terbesar di Indonesia yang megah.',
                        'address' => 'Jl. Raya Solo - Yogyakarta, Prambanan',
                        'open_now' => true,
                        'opening_hours' => '06:00 - 17:00',
                        'photo' => 'https://images.unsplash.com/photo-1584810359583-96fc3448beaa?q=80&w=600'
                    ],
                    [
                        'name' => 'Museum Ullen Sentalu',
                        'lat' => -7.5976,
                        'lng' => 110.4234,
                        'category' => 'indoor',
                        'rating' => 4.9,
                        'description' => 'Museum budaya dan seni Jawa yang terletak di kawasan Kaliurang.',
                        'address' => 'Jl. Boyong, Kaliurang, Sleman',
                        'open_now' => true,
                        'opening_hours' => '08:30 - 16:00',
                        'photo' => 'https://images.unsplash.com/photo-1596401057633-54a8fe8ef647?q=80&w=600'
                    ]
                ]
            ];

            if ($fallbackCity && isset($mockAlternatives[$fallbackCity])) {
                $rawPlaces = $mockAlternatives[$fallbackCity];
            } else {
                $rawPlaces = [];
            }
        }

        // Process places and calculate suitability score
        $destinationsList = [];
        foreach ($rawPlaces as $index => $item) {
            if (isset($item['place_id'])) {
                $pLat = $item['geometry']['location']['lat'];
                $pLng = $item['geometry']['location']['lng'];
                $name = $item['name'];
                $rating = $item['rating'] ?? 4.0;
                $address = $item['vicinity'] ?? $item['formatted_address'] ?? 'Alamat tidak tersedia';
                $openNow = $item['opening_hours']['open_now'] ?? true;
                $openingHours = $openNow ? 'Buka Sekarang' : 'Tutup';
                $description = "Destinasi terpopuler sekitar koordinat wilayah. Ditemukan menggunakan Google Places API.";
                
                $photoRef = $item['photos'][0]['photo_reference'] ?? null;
                $photo = $photoRef 
                    ? "https://maps.googleapis.com/maps/api/place/photo?maxwidth=600&photo_reference={$photoRef}&key={$googleKey}" 
                    : ($googleKey ? "https://maps.googleapis.com/maps/api/staticmap?center={$pLat},{$pLng}&zoom=15&size=600x400&markers=color:red%7C{$pLat},{$pLng}&key={$googleKey}" : "https://placehold.co/600x400/1e293b/ffffff.png?text=" . urlencode($name));
                
                // Categorize based on keywords/types
                $types = $item['types'] ?? [];
                $category = 'outdoor';
                $indoorKeywords = ['shopping_mall', 'museum', 'art_gallery', 'cafe', 'restaurant', 'library', 'aquarium', 'bowling_alley'];
                foreach ($types as $type) {
                    if (in_array($type, $indoorKeywords)) {
                        $category = 'indoor';
                        break;
                    }
                }
            } else {
                $pLat = $item['lat'];
                $pLng = $item['lng'];
                $name = $item['name'];
                $rating = $item['rating'];
                $address = $item['address'];
                $openNow = $item['open_now'];
                $openingHours = $item['opening_hours'];
                $photo = $googleKey 
                    ? "https://maps.googleapis.com/maps/api/staticmap?center={$pLat},{$pLng}&zoom=15&size=600x400&markers=color:red%7C{$pLat},{$pLng}&key={$googleKey}" 
                    : "https://placehold.co/600x400/1e293b/ffffff.png?text=" . urlencode($name);
                $category = $item['category'];
                $description = $item['description'];
            }

            // Strict Filter for Hujan
            if ($weatherStatus === 'Hujan' && $category === 'outdoor') {
                continue;
            }

            // Calculate distance
            $distance = $this->calculateDistance($lat, $lng, $pLat, $pLng);

            // Calculate Suitability Score
            $catScore = ($category === 'indoor') ? 30 : 5;
            if ($weatherStatus !== 'Hujan') {
                $catScore = ($category === 'outdoor') ? 30 : 15;
            }

            $ratingScore = $rating * 6;
            $distScore = max(0, 30 - ($distance * 4));
            $openScore = $openNow ? 10 : 0;
            $suitabilityScore = round($catScore + $ratingScore + $distScore + $openScore);

            $destinationsList[] = [
                'id' => $index + 1,
                'name' => $name,
                'description' => $description,
                'category' => $category,
                'image_url' => $photo,
                'opening_hours' => $openingHours,
                'rating' => $rating,
                'lat' => $pLat,
                'lng' => $pLng,
                'distance' => $distance,
                'suitability_score' => $suitabilityScore,
                'city' => ['name' => $locationName]
            ];
        }

        // Sort by suitability score descending
        usort($destinationsList, function ($a, $b) {
            return $b['suitability_score'] <=> $a['suitability_score'];
        });

        // Limit list dynamically depending on weather
        if ($weatherStatus === 'Hujan') {
            $categoryText = "indoor (dalam ruangan)";
        } elseif ($weatherStatus === 'Cerah') {
            $categoryText = "outdoor (luar ruangan)";
        } else {
            $categoryText = "indoor maupun outdoor";
        }

        // Generasikan reasoning cerdas dari agen
        $destNames = collect($destinationsList)->take(3)->pluck('name')->toArray();
        if (count($destNames) > 0) {
            if (count($destNames) === 1) {
                $destList = $destNames[0];
            } else {
                $lastDest = array_pop($destNames);
                $destList = implode(', ', $destNames) . ' dan ' . $lastDest;
            }
            $reason = "Karena cuaca di {$locationName} saat ini {$weatherStatus}, sistem Google Maps Places merekomendasikan {$destList} dengan kecocokan kenyamanan terbaik.";
        } else {
            $reason = "Cuaca di {$locationName} terpantau {$weatherStatus}. Kami menyarankan destinasi {$categoryText}.";
        }

        return [
            'is_error' => false,
            'city' => (object)['name' => $locationName, 'country' => 'Indonesia'],
            'weather' => $weather,
            'reason' => $reason,
            'destinations' => $destinationsList,
            'user' => [
                'lat' => $lat,
                'lng' => $lng
            ],
            'logs' => [],
            'status_changed' => true,
            'google_maps_api_key' => $googleKey ?: ''
        ];
    }

    /**
     * Helper distance calculator (Haversine formula)
     * @deprecated Use the CalculatesDistance trait instead.
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // in km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 1);
    }
}
