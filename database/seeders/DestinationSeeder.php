<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\WeatherCondition;
use App\Models\Destination;
use Illuminate\Database\Seeder;

class DestinationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Cities
        $cities = [
            ['name' => 'Batu', 'country' => 'Indonesia'],
            ['name' => 'Jakarta', 'country' => 'Indonesia'],
            ['name' => 'Malang', 'country' => 'Indonesia'],
            ['name' => 'Bandung', 'country' => 'Indonesia'],
            ['name' => 'Bogor', 'country' => 'Indonesia'],
        ];

        $cityModels = [];
        foreach ($cities as $city) {
            $cityModels[$city['name']] = City::updateOrCreate(['name' => $city['name']], $city);
        }

        // 2. Seed Default Weather Conditions for each city
        $weatherData = [
            'Batu' => ['status' => 'Cerah', 'temperature' => 24, 'humidity' => 70, 'wind_speed' => 8],
            'Jakarta' => ['status' => 'Cerah', 'temperature' => 31, 'humidity' => 65, 'wind_speed' => 12],
            'Malang' => ['status' => 'Hujan', 'temperature' => 22, 'humidity' => 85, 'wind_speed' => 8],
            'Bandung' => ['status' => 'Berawan', 'temperature' => 24, 'humidity' => 75, 'wind_speed' => 10],
            'Bogor' => ['status' => 'Hujan', 'temperature' => 23, 'humidity' => 90, 'wind_speed' => 6],
        ];

        foreach ($weatherData as $cityName => $weather) {
            $city = $cityModels[$cityName];
            WeatherCondition::updateOrCreate(
                ['city_id' => $city->id],
                [
                    'status' => $weather['status'],
                    'temperature' => $weather['temperature'],
                    'humidity' => $weather['humidity'],
                    'wind_speed' => $weather['wind_speed'],
                ]
            );
        }

        // 3. Seed Destinations linked via city_id
        $destinations = [
            // Batu
            [
                'name' => 'Museum Angkut',
                'description' => 'Museum transportasi terbesar di Asia dengan koleksi mobil antik dari berbagai penjuru dunia. Sangat nyaman dikunjungi saat hujan karena seluruh area indoor.',
                'category' => 'indoor',
                'city_name' => 'Batu',
                'image_url' => 'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?q=80&w=600',
                'opening_hours' => '12:00 - 20:00',
                'rating' => 4.8,
                'min_temp' => null,
                'max_temp' => null,
            ],
            [
                'name' => 'Alun-Alun Kota Batu',
                'description' => 'Taman bermain terbuka yang ramah keluarga dengan bianglala ikonik dan pasar kuliner malam hari. Sangat ramai dan menyenangkan saat cuaca cerah.',
                'category' => 'outdoor',
                'city_name' => 'Batu',
                'image_url' => 'https://images.unsplash.com/photo-1490730141103-6cac27aaab94?q=80&w=600',
                'opening_hours' => 'Buka 24 Jam',
                'rating' => 4.6,
                'min_temp' => 18,
                'max_temp' => 30,
            ],
            [
                'name' => 'Lippo Plaza Batu',
                'description' => 'Pusat perbelanjaan ber-AC yang nyaman dengan gerai ritel modern, restoran, bioskop, dan area bermain anak. Pilihan utama saat hujan.',
                'category' => 'indoor',
                'city_name' => 'Batu',
                'image_url' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=600',
                'opening_hours' => '10:00 - 22:00',
                'rating' => 4.3,
                'min_temp' => null,
                'max_temp' => null,
            ],
            [
                'name' => 'Coban Rondo Waterfall',
                'description' => 'Wisata air terjun alam terbuka yang menawan di lereng gunung. Sangat indah saat cerah namun tidak disarankan saat hujan deras.',
                'category' => 'outdoor',
                'city_name' => 'Batu',
                'image_url' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?q=80&w=600',
                'opening_hours' => '07:00 - 17:00',
                'rating' => 4.5,
                'min_temp' => 15,
                'max_temp' => 28,
            ],
            // Jakarta
            [
                'name' => 'Taman Impian Jaya Ancol',
                'description' => 'Taman hiburan pantai yang luas di utara Jakarta. Sangat menyenangkan dikunjungi saat cuaca cerah untuk menikmati pantai dan wahana outdoor.',
                'category' => 'outdoor',
                'city_name' => 'Jakarta',
                'image_url' => 'https://images.unsplash.com/photo-1571439223789-9b936eef67da?q=80&w=600',
                'opening_hours' => '06:00 - 22:00',
                'rating' => 4.5,
                'min_temp' => 20,
                'max_temp' => 36,
            ],
            [
                'name' => 'Museum Nasional',
                'description' => 'Museum arkeologi, sejarah, etnografi, dan geografi yang terletak di Jakarta Pusat. Sangat ideal untuk wisata edukasi dalam ruangan saat cuaca hujan.',
                'category' => 'indoor',
                'city_name' => 'Jakarta',
                'image_url' => 'https://images.unsplash.com/photo-1582555172866-f73bb12a2ab3?q=80&w=600',
                'opening_hours' => '08:00 - 16:00',
                'rating' => 4.6,
                'min_temp' => null,
                'max_temp' => null,
            ],
            [
                'name' => 'Jakarta Aquarium & Safari',
                'description' => 'Akuarium indoor terbesar di Indonesia, terletak di dalam mall Neo Soho. Menampilkan lebih dari 3.500 spesies satwa akuatik dan non-akuatik.',
                'category' => 'indoor',
                'city_name' => 'Jakarta',
                'image_url' => 'https://images.unsplash.com/photo-1533414488820-b4974ba5a278?q=80&w=600',
                'opening_hours' => '10:00 - 21:00',
                'rating' => 4.7,
                'min_temp' => null,
                'max_temp' => null,
            ],
            [
                'name' => 'Dunia Fantasi (Dufan)',
                'description' => 'Pusat hiburan outdoor terbesar di Jakarta dengan berbagai pilihan wahana adrenalin ekstrem maupun santai.',
                'category' => 'outdoor',
                'city_name' => 'Jakarta',
                'image_url' => 'https://images.unsplash.com/photo-1509316785289-025f5b846b35?q=80&w=600',
                'opening_hours' => '10:00 - 18:00',
                'rating' => 4.6,
                'min_temp' => 20,
                'max_temp' => 35,
            ],
            // Malang
            [
                'name' => 'Museum Angkut',
                'description' => 'Museum transportasi modern pertama di Asia Tenggara yang memadukan wisata edukasi dengan pameran mobil antik berskala internasional secara indoor.',
                'category' => 'indoor',
                'city_name' => 'Malang',
                'image_url' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=600',
                'opening_hours' => '12:00 - 20:00',
                'rating' => 4.8,
                'min_temp' => null,
                'max_temp' => null,
            ],
            [
                'name' => 'Jatim Park Indoor (Science Center)',
                'description' => 'Wahana edukasi sains interaktif dalam ruangan yang sangat cocok untuk liburan keluarga saat cuaca dingin atau hujan di Kota Batu/Malang.',
                'category' => 'indoor',
                'city_name' => 'Malang',
                'image_url' => 'https://images.unsplash.com/photo-1507208773393-40d9fc670acf?q=80&w=600',
                'opening_hours' => '08:30 - 16:30',
                'rating' => 4.5,
                'min_temp' => null,
                'max_temp' => null,
            ],
            [
                'name' => 'Gunung Bromo (Jalur Malang)',
                'description' => 'Destinasi wisata luar ruangan yang legendaris. Menawarkan pemandangan matahari terbit indah dan lautan pasir yang luas saat cuaca cerah.',
                'category' => 'outdoor',
                'city_name' => 'Malang',
                'image_url' => 'https://images.unsplash.com/photo-1589308078059-be1415eab4c3?q=80&w=600',
                'opening_hours' => '24 Jam',
                'rating' => 4.9,
                'min_temp' => 5,
                'max_temp' => 22,
            ],
            // Bandung
            [
                'name' => 'Kawah Putih Ciwidey',
                'description' => 'Danau vulkanik yang indah dengan tanah berwarna putih kehijauan. Udara sejuk dan pemandangan menakjubkan saat cerah atau berawan tipis.',
                'category' => 'outdoor',
                'city_name' => 'Bandung',
                'image_url' => 'https://images.unsplash.com/photo-1626125345510-4603468eedfb?q=80&w=600',
                'opening_hours' => '07:00 - 17:00',
                'rating' => 4.6,
                'min_temp' => 10,
                'max_temp' => 25,
            ],
            [
                'name' => 'Trans Studio Bandung',
                'description' => 'Salah satu taman bermain indoor terbesar di dunia yang menghadirkan wahana menantang dan pertunjukan spektakuler tanpa takut kehujanan.',
                'category' => 'indoor',
                'city_name' => 'Bandung',
                'image_url' => 'https://images.unsplash.com/photo-1513885041144-809f42633084?q=80&w=600',
                'opening_hours' => '10:00 - 19:00',
                'rating' => 4.7,
                'min_temp' => null,
                'max_temp' => null,
            ],
            // Bogor
            [
                'name' => 'Kebun Raya Bogor',
                'description' => 'Kebun botani tertua di Asia Tenggara. Tempat ideal untuk jalan santai menikmati alam di bawah pohon rindang saat cuaca cerah.',
                'category' => 'outdoor',
                'city_name' => 'Bogor',
                'image_url' => 'https://images.unsplash.com/photo-1542314831-c6a42072120e?q=80&w=600',
                'opening_hours' => '08:00 - 16:00',
                'rating' => 4.6,
                'min_temp' => 18,
                'max_temp' => 30,
            ]
        ];

        foreach ($destinations as $dest) {
            $city = $cityModels[$dest['city_name']];
            Destination::create([
                'city_id' => $city->id,
                'name' => $dest['name'],
                'description' => $dest['description'],
                'category' => $dest['category'],
                'image_url' => $dest['image_url'],
                'opening_hours' => $dest['opening_hours'],
                'rating' => $dest['rating'],
                'min_temp' => $dest['min_temp'],
                'max_temp' => $dest['max_temp'],
            ]);
        }

        // 4. Generate initial recommendations via agent service
        $service = app(\App\Services\WeatherAdaptiveGuideService::class);
        foreach ($cityModels as $city) {
            $service->getRecommendations($city->id);
        }
    }
}
