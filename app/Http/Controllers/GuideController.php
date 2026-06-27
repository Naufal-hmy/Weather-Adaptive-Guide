<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\WeatherAdaptiveGuideService;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Repositories\Contracts\DestinationRepositoryInterface;
use App\Traits\CalculatesDistance;
use Inertia\Inertia;

class GuideController extends Controller
{
    use CalculatesDistance;
    protected $guideService;
    protected $cityRepo;
    protected $destinationRepo;

    public function __construct(
        WeatherAdaptiveGuideService $guideService,
        CityRepositoryInterface $cityRepo,
        DestinationRepositoryInterface $destinationRepo
    ) {
        $this->guideService = $guideService;
        $this->cityRepo = $cityRepo;
        $this->destinationRepo = $destinationRepo;
    }

    public function index(Request $request)
    {
        $cities = $this->cityRepo->all();
        
        $lat = $request->query('lat');
        $lng = $request->query('lng');
        $locationName = $request->query('q');

        if ($lat && $lng) {
            $guideData = $this->guideService->getRecommendationsForCoordinates($lat, $lng, $locationName);
            $selectedCityId = null;
        } else {
            if ($cities->isEmpty()) {
                return Inertia::render('Dashboard', [
                    'cities' => [],
                    'selectedCityId' => null,
                    'guideData' => [
                        'is_error' => true,
                        'error_message' => 'Belum ada kota yang terdaftar. Hubungi admin.'
                    ],
                    'stats' => ['indoor' => 0, 'outdoor' => 0]
                ]);
            }
            
            $selectedCityId = $request->query('city_id', $cities->first()->id);
            $guideData = $this->guideService->getRecommendations($selectedCityId);
        }

        // Fetch statistics (indoor vs outdoor destinations count)
        $allDestinations = $this->destinationRepo->all();
        $indoorCount = $allDestinations->where('category', 'indoor')->count();
        $outdoorCount = $allDestinations->where('category', 'outdoor')->count();

        return Inertia::render('Dashboard', [
            'cities' => $cities,
            'selectedCityId' => $selectedCityId ? (int) $selectedCityId : null,
            'guideData' => $guideData,
            'googleMapsApiKey' => config('services.google.maps_api_key') ?: '',
            'stats' => [
                'indoor' => $indoorCount,
                'outdoor' => $outdoorCount
            ]
        ]);
    }

    /**
     * Admin action to simulate / update weather status for a city.
     */
    public function updateWeather(Request $request, $cityId)
    {
        $validated = $request->validate([
            'status' => 'required|in:Cerah,Berawan,Hujan',
            'temperature' => 'required|integer|between:-50,60',
            'humidity' => 'required|integer|between:0,100',
            'wind_speed' => 'required|integer|between:0,150',
        ]);

        $this->guideService->updateCityWeather(
            $cityId,
            $validated['status'],
            $validated['temperature'],
            $validated['humidity'],
            $validated['wind_speed']
        );

        return redirect()->back()->with('success', 'Cuaca kota berhasil diperbarui secara manual. Rekomendasi telah diadaptasi!');
    }

    /**
     * Render the Smart Recommendation Map page.
     */
    public function smartMap()
    {
        $cities = $this->cityRepo->all();
        $googleMapsApiKey = config('services.google.maps_api_key') ?: '';

        return Inertia::render('SmartMap', [
            'cities' => $cities,
            'googleMapsApiKey' => $googleMapsApiKey,
        ]);
    }

    /**
     * Get nearby recommendations using Google Places API and OpenWeather API
     */
    public function getNearbyRecommendations(Request $request)
    {
        $userLat = $request->query('user_lat');
        $userLng = $request->query('user_lng');
        $cityId = $request->query('city_id');
        $forceWeather = $request->query('force_weather'); // Cerah, Berawan, Hujan

        $city = $this->cityRepo->findById($cityId) ?: $this->cityRepo->all()->first();
        if (!$city) {
            return response()->json(['error' => 'No cities available.'], 404);
        }

        // City default coordinates (fallback for user location)
        $cityCoords = [
            'Jakarta' => [-6.2088, 106.8456],
            'Malang' => [-7.9839, 112.6214],
            'Bandung' => [-6.9175, 107.6191],
            'Bogor' => [-6.5971, 106.8060],
            'Batu' => [-7.8712, 112.5269]
        ];

        $cityName = $city->name;
        $coords = $cityCoords[$cityName] ?? [-7.8712, 112.5269]; // default Batu

        if (!$userLat || !$userLng) {
            $userLat = $coords[0] + 0.01; // Slightly offset user position for nice map visualization
            $userLng = $coords[1] - 0.01;
        }

        // Define starting/planned destination based on selected city (usually outdoor destinations)
        $startingDestinations = [
            'Batu' => [
                'name' => 'Alun-Alun Kota Batu',
                'lat' => -7.8712,
                'lng' => 112.5269,
                'category' => 'outdoor',
                'description' => 'Taman kota terbuka yang ramai di jantung Kota Batu.',
                'address' => 'Jl. Diponegoro, Sisir, Kec. Batu, Kota Batu',
                'rating' => 4.6
            ],
            'Jakarta' => [
                'name' => 'Monumen Nasional (Monas)',
                'lat' => -6.1754,
                'lng' => 106.8272,
                'category' => 'outdoor',
                'description' => 'Monumen peringatan setinggi 132 meter di area terbuka.',
                'address' => 'Gambir, Kec. Gambir, Kota Jakarta Pusat',
                'rating' => 4.6
            ],
            'Malang' => [
                'name' => 'Alun-Alun Tugu Malang',
                'lat' => -7.9774,
                'lng' => 112.6341,
                'category' => 'outdoor',
                'description' => 'Taman bundar bersejarah dengan kolam teratai terbuka.',
                'address' => 'Jl. Tugu, Kiduldalem, Kec. Klojen, Kota Malang',
                'rating' => 4.5
            ],
            'Bandung' => [
                'name' => 'Taman Hutan Raya Juanda',
                'lat' => -6.8566,
                'lng' => 107.6327,
                'category' => 'outdoor',
                'description' => 'Kawasan konservasi alam terpadu bernuansa hutan pinus.',
                'address' => 'Kec. Cimenyan, Kabupaten Bandung',
                'rating' => 4.6
            ],
            'Bogor' => [
                'name' => 'Kebun Raya Bogor',
                'lat' => -6.5976,
                'lng' => 106.7996,
                'category' => 'outdoor',
                'description' => 'Kebun botani tertua di Asia Tenggara dengan ribuan koleksi pohon.',
                'address' => 'Jl. Ir. H. Juanda No.13, Kota Bogor',
                'rating' => 4.7
            ]
        ];

        $starting = $startingDestinations[$cityName] ?? $startingDestinations['Batu'];

        // 1. Ambil data cuaca dari OpenWeather API
        $weatherStatus = 'Cerah';
        $weatherTemp = 28;
        $weatherHumidity = 70;
        $weatherWind = 10;

        if ($forceWeather) {
            $weatherStatus = $forceWeather;
            $weatherTemp = $forceWeather === 'Hujan' ? 22 : ($forceWeather === 'Berawan' ? 25 : 29);
        } else {
            $openWeatherKey = config('services.openweather.api_key');
            if ($openWeatherKey) {
                try {
                    $weatherUrl = "https://api.openweathermap.org/data/2.5/weather";
                    $weatherRes = Http::withoutVerifying()->get($weatherUrl, [
                        'lat' => $starting['lat'],
                        'lon' => $starting['lng'],
                        'appid' => $openWeatherKey,
                        'units' => 'metric'
                    ]);
                    if ($weatherRes->successful()) {
                        $wData = $weatherRes->json();
                        $mainWeather = $wData['weather'][0]['main'] ?? 'Clear';
                        $weatherTemp = $wData['main']['temp'] ?? 28;
                        $weatherHumidity = $wData['main']['humidity'] ?? 70;
                        $weatherWind = $wData['wind']['speed'] ?? 10;

                        // Map OpenWeather main status to Indo standard
                        if (in_array(strtolower($mainWeather), ['rain', 'drizzle', 'thunderstorm'])) {
                            $weatherStatus = 'Hujan';
                        } elseif (in_array(strtolower($mainWeather), ['clouds', 'mist', 'haze', 'fog'])) {
                            $weatherStatus = 'Berawan';
                        } else {
                            $weatherStatus = 'Cerah';
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Failed fetching weather in SmartMap: " . $e->getMessage());
                }
            } else {
                $weatherStatus = 'Hujan'; 
                $weatherTemp = 23;
            }
        }

        // Determine if Starting Destination is Recommended
        $startingRecommended = true;
        if ($weatherStatus === 'Hujan' && $starting['category'] === 'outdoor') {
            $startingRecommended = false;
        }

        // 2. Cari destinasi indoor/alternatif terdekat menggunakan Google Places API / Fallback
        $googleKey = config('services.google.maps_api_key');
        $rawPlaces = [];

        if ($googleKey) {
            try {
                $placesUrl = "https://maps.googleapis.com/maps/api/place/nearbysearch/json";
                $placesRes = Http::withoutVerifying()->get($placesUrl, [
                    'location' => "{$starting['lat']},{$starting['lng']}",
                    'radius' => 5000,
                    'keyword' => 'indoor|museum|mall|gallery|arcade|library',
                    'key' => $googleKey
                ]);

                if ($placesRes->successful()) {
                    $rawPlaces = $placesRes->json()['results'] ?? [];
                }
            } catch (\Exception $e) {
                Log::error("Failed fetching Google Places: " . $e->getMessage());
            }
        }

        // If Google API failed or has no results or isn't configured, use rich mock data
        if (empty($rawPlaces)) {
            $mockAlternatives = [
                'Batu' => [
                    [
                        'name' => 'Museum Angkut',
                        'lat' => -7.8794,
                        'lng' => 112.5186,
                        'category' => 'indoor',
                        'rating' => 4.8,
                        'address' => 'Jl. Terusan Sultan Agung No.2, Ngaglik, Kec. Batu',
                        'open_now' => true,
                        'opening_hours' => '12.00 - 20.00',
                        'photo' => 'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?q=80&w=400'
                    ],
                    [
                        'name' => 'Lippo Plaza Batu',
                        'lat' => -7.8741,
                        'lng' => 112.5283,
                        'category' => 'indoor',
                        'rating' => 4.3,
                        'address' => 'Jl. Diponegoro No.1, Sisir, Kec. Batu',
                        'open_now' => true,
                        'opening_hours' => '10.00 - 22.00',
                        'photo' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=400'
                    ],
                    [
                        'name' => 'Batu Night Spectacular (Indoor Arcade)',
                        'lat' => -7.8967,
                        'lng' => 112.5358,
                        'category' => 'indoor',
                        'rating' => 4.5,
                        'address' => 'Jl. Hayam Wuruk No.1, Oro-Oro Ombo, Kec. Batu',
                        'open_now' => true,
                        'opening_hours' => '15.00 - 23.00',
                        'photo' => 'https://images.unsplash.com/photo-1498038432885-c6f3f1b912ee?q=80&w=400'
                    ],
                    [
                        'name' => 'Eco Green Park (Indoor Science Center)',
                        'lat' => -7.8906,
                        'lng' => 112.5273,
                        'category' => 'indoor',
                        'rating' => 4.4,
                        'address' => 'Jl. Oro-Oro Ombo No.9A, Sisir, Kec. Batu',
                        'open_now' => false,
                        'opening_hours' => '08.30 - 16.30',
                        'photo' => 'https://images.unsplash.com/photo-1534447677768-be436bb09401?q=80&w=400'
                    ]
                ],
                'Jakarta' => [
                    [
                        'name' => 'Museum Nasional Indonesia',
                        'lat' => -6.1764,
                        'lng' => 106.8219,
                        'category' => 'indoor',
                        'rating' => 4.6,
                        'address' => 'Jl. Medan Merdeka Barat No.12, Jakarta Pusat',
                        'open_now' => true,
                        'opening_hours' => '08.00 - 16.00',
                        'photo' => 'https://images.unsplash.com/photo-1580537659444-230f2c5f6eef?q=80&w=400'
                    ],
                    [
                        'name' => 'Grand Indonesia Mall',
                        'lat' => -6.1951,
                        'lng' => 106.8208,
                        'category' => 'indoor',
                        'rating' => 4.7,
                        'address' => 'Jl. M.H. Thamrin No.1, Jakarta Pusat',
                        'open_now' => true,
                        'opening_hours' => '10.00 - 22.00',
                        'photo' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=400'
                    ],
                    [
                        'name' => 'Galeri Nasional Indonesia',
                        'lat' => -6.1788,
                        'lng' => 106.8329,
                        'category' => 'indoor',
                        'rating' => 4.5,
                        'address' => 'Jl. Medan Merdeka Timur No.14, Jakarta Pusat',
                        'open_now' => true,
                        'opening_hours' => '09.00 - 16.00',
                        'photo' => 'https://images.unsplash.com/photo-1498038432885-c6f3f1b912ee?q=80&w=400'
                    ]
                ],
                'Malang' => [
                    [
                        'name' => 'Museum Malang Tempo Doeloe',
                        'lat' => -7.9782,
                        'lng' => 112.6355,
                        'category' => 'indoor',
                        'rating' => 4.4,
                        'address' => 'Jl. Gajahmada No.2, Kiduldalem, Kec. Klojen, Malang',
                        'open_now' => true,
                        'opening_hours' => '08.00 - 17.00',
                        'photo' => 'https://images.unsplash.com/photo-1580537659444-230f2c5f6eef?q=80&w=400'
                    ],
                    [
                        'name' => 'Malang Town Square (Matos)',
                        'lat' => -7.9571,
                        'lng' => 112.6186,
                        'category' => 'indoor',
                        'rating' => 4.3,
                        'address' => 'Jl. Veteran No.2, Penanggungan, Kec. Klojen, Malang',
                        'open_now' => true,
                        'opening_hours' => '10.00 - 22.00',
                        'photo' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=400'
                    ],
                    [
                        'name' => 'Museum Brawijaya',
                        'lat' => -7.9719,
                        'lng' => 112.6212,
                        'category' => 'indoor',
                        'rating' => 4.5,
                        'address' => 'Jl. Ijen No.25a, Gading Kasri, Kec. Klojen, Malang',
                        'open_now' => true,
                        'opening_hours' => '08.00 - 15.00',
                        'photo' => 'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?q=80&w=400'
                    ]
                ],
                'Bandung' => [
                    [
                        'name' => 'Saung Angklung Udjo',
                        'lat' => -6.8979,
                        'lng' => 107.6558,
                        'category' => 'indoor',
                        'rating' => 4.8,
                        'address' => 'Jl. Padasuka No.118, Pasirlayung, Kec. Cibeunying Kidul, Bandung',
                        'open_now' => true,
                        'opening_hours' => '08.00 - 17.00',
                        'photo' => 'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?q=80&w=400'
                    ],
                    [
                        'name' => 'Trans Studio Bandung',
                        'lat' => -6.9251,
                        'lng' => 107.6366,
                        'category' => 'indoor',
                        'rating' => 4.6,
                        'address' => 'Jl. Gatot Subroto No.289A, Cibangkong, Bandung',
                        'open_now' => true,
                        'opening_hours' => '10.00 - 17.00',
                        'photo' => 'https://images.unsplash.com/photo-1498038432885-c6f3f1b912ee?q=80&w=400'
                    ],
                    [
                        'name' => 'Museum Geologi Bandung',
                        'lat' => -6.9007,
                        'lng' => 107.6215,
                        'category' => 'indoor',
                        'rating' => 4.5,
                        'address' => 'Jl. Diponegoro No.57, Cihaur Geulis, Kec. Cibeunying Kaler, Bandung',
                        'open_now' => true,
                        'opening_hours' => '09.00 - 15.00',
                        'photo' => 'https://images.unsplash.com/photo-1580537659444-230f2c5f6eef?q=80&w=400'
                    ]
                ],
                'Bogor' => [
                    [
                        'name' => 'Museum Zoologi Bogor',
                        'lat' => -6.6025,
                        'lng' => 106.7972,
                        'category' => 'indoor',
                        'rating' => 4.6,
                        'address' => 'Jl. Ir. H. Juanda No.9, Paledang, Kec. Bogor Tengah, Bogor',
                        'open_now' => true,
                        'opening_hours' => '08.00 - 16.00',
                        'photo' => 'https://images.unsplash.com/photo-1580537659444-230f2c5f6eef?q=80&w=400'
                    ],
                    [
                        'name' => 'Botani Square Mall',
                        'lat' => -6.6015,
                        'lng' => 106.8058,
                        'category' => 'indoor',
                        'rating' => 4.5,
                        'address' => 'Jl. Raya Pajajaran No.3, Tegallega, Kec. Bogor Tengah, Bogor',
                        'open_now' => true,
                        'opening_hours' => '10.00 - 22.00',
                        'photo' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=400'
                    ],
                    [
                        'name' => 'Museum Kepresidenan Balai Kirti',
                        'lat' => -6.5989,
                        'lng' => 106.7947,
                        'category' => 'indoor',
                        'rating' => 4.7,
                        'address' => 'Kawasan Istana Kepresidenan Bogor, Jl. Ir. H. Juanda No.1, Bogor',
                        'open_now' => true,
                        'opening_hours' => '09.00 - 15.00',
                        'photo' => 'https://images.unsplash.com/photo-1498038432885-c6f3f1b912ee?q=80&w=400'
                    ]
                ]
            ];

            $rawPlaces = $mockAlternatives[$cityName] ?? $mockAlternatives['Batu'];
        }

        // Process places and calculate suitability score
        $processed = [];
        foreach ($rawPlaces as $index => $item) {
            if (isset($item['place_id'])) {
                $lat = $item['geometry']['location']['lat'];
                $lng = $item['geometry']['location']['lng'];
                $name = $item['name'];
                $rating = $item['rating'] ?? 4.0;
                $address = $item['vicinity'] ?? $item['formatted_address'] ?? 'Alamat tidak tersedia';
                $openNow = $item['opening_hours']['open_now'] ?? true;
                $openingHours = $openNow ? 'Buka Sekarang' : 'Tutup';
                
                $photoRef = $item['photos'][0]['photo_reference'] ?? null;
                $photo = $photoRef 
                    ? "https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photo_reference={$photoRef}&key={$googleKey}" 
                    : 'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?q=80&w=400';
                $category = 'indoor';
            } else {
                $lat = $item['lat'];
                $lng = $item['lng'];
                $name = $item['name'];
                $rating = $item['rating'];
                $address = $item['address'];
                $openNow = $item['open_now'];
                $openingHours = $item['opening_hours'];
                $photo = $item['photo'];
                $category = $item['category'];
            }

            $distance = $this->calculateDistance($starting['lat'], $starting['lng'], $lat, $lng);

            // Calculate Suitability Score
            $catScore = ($category === 'indoor') ? 30 : 5;
            if ($weatherStatus !== 'Hujan') {
                $catScore = ($category === 'outdoor') ? 30 : 15;
            }

            $ratingScore = $rating * 6;
            $distScore = max(0, 30 - ($distance * 4));
            $openScore = $openNow ? 10 : 0;

            $suitabilityScore = round($catScore + $ratingScore + $distScore + $openScore);

            $processed[] = [
                'name' => $name,
                'lat' => $lat,
                'lng' => $lng,
                'rating' => $rating,
                'address' => $address,
                'open_now' => $openNow,
                'opening_hours' => $openingHours,
                'photo' => $photo,
                'category' => $category,
                'distance' => $distance,
                'suitability_score' => $suitabilityScore,
                'marker_color' => $weatherStatus === 'Hujan' ? 'green' : 'blue'
            ];
        }

        // Sort by suitability score descending
        usort($processed, function ($a, $b) {
            return $b['suitability_score'] <=> $a['suitability_score'];
        });

        // Take top 3
        $topRecommendations = array_slice($processed, 0, 3);

        // Generate reasoning message
        if ($weatherStatus === 'Hujan') {
            if (!empty($topRecommendations)) {
                $best = $topRecommendations[0];
                $reasoning = "Karena cuaca hujan di {$cityName}, kami merekomendasikan {$best['name']} yang berjarak {$best['distance']} km, memiliki rating {$best['rating']}, dan sedang " . ($best['open_now'] ? 'buka.' : 'tutup.');
            } else {
                $reasoning = "Cuaca hujan di {$cityName}. Silakan hindari area luar ruangan dan kunjungi pusat perbelanjaan atau museum terdekat.";
            }
        } else {
            $reasoning = "Cuaca di {$cityName} terpantau {$weatherStatus} (bagus). Anda dapat melanjutkan rencana perjalanan ke {$starting['name']} atau mengunjungi alternatif terdekat.";
        }

        return response()->json([
            'weather' => [
                'status' => $weatherStatus,
                'temperature' => $weatherTemp,
                'humidity' => $weatherHumidity,
                'wind_speed' => $weatherWind
            ],
            'user' => [
                'lat' => (float)$userLat,
                'lng' => (float)$userLng
            ],
            'starting' => [
                'name' => $starting['name'],
                'lat' => $starting['lat'],
                'lng' => $starting['lng'],
                'category' => $starting['category'],
                'description' => $starting['description'],
                'address' => $starting['address'],
                'rating' => $starting['rating'],
                'recommended' => $startingRecommended,
                'marker_color' => $startingRecommended ? 'blue' : 'red'
            ],
            'recommendations' => $topRecommendations,
            'reasoning' => $reasoning
        ]);
    }

}
