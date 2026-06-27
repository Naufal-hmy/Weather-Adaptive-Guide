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
            ['name' => 'Bali', 'country' => 'Indonesia'],
            ['name' => 'Yogyakarta', 'country' => 'Indonesia'],
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
            'Bali' => ['status' => 'Cerah', 'temperature' => 32, 'humidity' => 60, 'wind_speed' => 15],
            'Yogyakarta' => ['status' => 'Berawan', 'temperature' => 28, 'humidity' => 70, 'wind_speed' => 9],
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
            [
                'name' => 'Jatim Park 3 (Dino Park)',
                'description' => 'Taman hiburan tematik dengan konsep dinosaurus. Memiliki wahana seru yang cocok untuk segala cuaca dengan campuran area indoor dan outdoor.',
                'category' => 'outdoor',
                'city_name' => 'Batu',
                'image_url' => 'https://images.unsplash.com/photo-1596489376912-3a3d58286b2e?q=80&w=600',
                'opening_hours' => '11:00 - 20:00',
                'rating' => 4.7,
                'min_temp' => 18,
                'max_temp' => 30,
            ],
            [
                'name' => 'Batu Secret Zoo (Jatim Park 2)',
                'description' => 'Kebun binatang modern dengan koleksi hewan dari berbagai benua. Menjelajahi taman ini sangat menyenangkan di cuaca yang cerah.',
                'category' => 'outdoor',
                'city_name' => 'Batu',
                'image_url' => 'https://images.unsplash.com/photo-1534567153574-2b12153a87f0?q=80&w=600',
                'opening_hours' => '08:30 - 16:30',
                'rating' => 4.8,
                'min_temp' => 20,
                'max_temp' => 30,
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
            [
                'name' => 'Monumen Nasional (Monas)',
                'description' => 'Ikon Kota Jakarta yang bersejarah. Pengunjung bisa naik ke puncak tugu atau mengunjungi museum sejarah nasional di bagian dasar (indoor).',
                'category' => 'indoor',
                'city_name' => 'Jakarta',
                'image_url' => 'https://images.unsplash.com/photo-1555899434-94d1368aa7af?q=80&w=600',
                'opening_hours' => '08:00 - 15:00',
                'rating' => 4.7,
                'min_temp' => null,
                'max_temp' => null,
            ],
            [
                'name' => 'Taman Mini Indonesia Indah',
                'description' => 'Taman hiburan budaya raksasa yang merangkum kebudayaan bangsa Indonesia. Memiliki banyak anjungan dan museum indoor.',
                'category' => 'outdoor',
                'city_name' => 'Jakarta',
                'image_url' => 'https://images.unsplash.com/photo-1580213895995-1f9dff863b1a?q=80&w=600',
                'opening_hours' => '06:00 - 18:00',
                'rating' => 4.5,
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
            [
                'name' => 'Kampung Warna Warni Jodipan',
                'description' => 'Perkampungan tematik yang dicat penuh warna. Spot foto outdoor yang sangat ikonik di tengah kota Malang.',
                'category' => 'outdoor',
                'city_name' => 'Malang',
                'image_url' => 'https://images.unsplash.com/photo-1590483736622-398541c88eb0?q=80&w=600',
                'opening_hours' => '07:00 - 18:00',
                'rating' => 4.4,
                'min_temp' => 20,
                'max_temp' => 32,
            ],
            [
                'name' => 'Hawai Waterpark',
                'description' => 'Taman bermain air raksasa dengan ombak tsunami buatan. Destinasi yang sangat menyegarkan saat cuaca panas.',
                'category' => 'outdoor',
                'city_name' => 'Malang',
                'image_url' => 'https://images.unsplash.com/photo-1533560904424-a0c61dc306fc?q=80&w=600',
                'opening_hours' => '10:00 - 16:00',
                'rating' => 4.6,
                'min_temp' => 25,
                'max_temp' => 35,
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
            [
                'name' => 'Gedung Sate',
                'description' => 'Gedung bersejarah dengan gaya arsitektur yang khas. Kini juga dilengkapi museum modern di dalamnya (indoor).',
                'category' => 'indoor',
                'city_name' => 'Bandung',
                'image_url' => 'https://images.unsplash.com/photo-1563177651-409df81b8966?q=80&w=600',
                'opening_hours' => '09:30 - 16:00',
                'rating' => 4.5,
                'min_temp' => null,
                'max_temp' => null,
            ],
            [
                'name' => 'Farmhouse Lembang',
                'description' => 'Wisata alam ala pedesaan Eropa. Pengunjung dapat berfoto mengenakan kostum tradisional dan berinteraksi dengan hewan ternak.',
                'category' => 'outdoor',
                'city_name' => 'Bandung',
                'image_url' => 'https://images.unsplash.com/photo-1550541785-5a50785ea81b?q=80&w=600',
                'opening_hours' => '09:00 - 18:00',
                'rating' => 4.6,
                'min_temp' => 15,
                'max_temp' => 28,
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
            ],
            [
                'name' => 'Taman Safari Indonesia',
                'description' => 'Kebun binatang berkonsep safari di mana pengunjung dapat melihat hewan liar dari jarak dekat menggunakan kendaraan.',
                'category' => 'outdoor',
                'city_name' => 'Bogor',
                'image_url' => 'https://images.unsplash.com/photo-1534567153574-2b12153a87f0?q=80&w=600',
                'opening_hours' => '08:30 - 17:00',
                'rating' => 4.8,
                'min_temp' => 18,
                'max_temp' => 30,
            ],
            [
                'name' => 'JungleLand Adventure Theme Park',
                'description' => 'Taman hiburan tematik di kawasan Sentul. Terdapat berbagai macam wahana luar ruangan yang sangat memacu adrenalin.',
                'category' => 'outdoor',
                'city_name' => 'Bogor',
                'image_url' => 'https://images.unsplash.com/photo-1509316785289-025f5b846b35?q=80&w=600',
                'opening_hours' => '10:00 - 17:00',
                'rating' => 4.4,
                'min_temp' => 20,
                'max_temp' => 32,
            ],
            // Bali
            [
                'name' => 'Pantai Kuta',
                'description' => 'Pantai berpasir putih legendaris yang sempurna untuk berselancar dan menikmati pemandangan matahari terbenam.',
                'category' => 'outdoor',
                'city_name' => 'Bali',
                'image_url' => 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?q=80&w=600',
                'opening_hours' => '24 Jam',
                'rating' => 4.5,
                'min_temp' => 25,
                'max_temp' => 35,
            ],
            [
                'name' => 'Garuda Wisnu Kencana (GWK)',
                'description' => 'Taman budaya raksasa yang menampilkan patung Dewa Wisnu dan Garuda raksasa yang megah. Mayoritas area outdoor.',
                'category' => 'outdoor',
                'city_name' => 'Bali',
                'image_url' => 'https://images.unsplash.com/photo-1621617514757-122e23616616?q=80&w=600',
                'opening_hours' => '08:00 - 20:00',
                'rating' => 4.6,
                'min_temp' => 22,
                'max_temp' => 35,
            ],
            [
                'name' => 'Trans Studio Bali',
                'description' => 'Taman hiburan indoor (dalam ruangan) pertama dan terbesar di Bali. Cocok untuk lari dari terik matahari dan badai hujan.',
                'category' => 'indoor',
                'city_name' => 'Bali',
                'image_url' => 'https://images.unsplash.com/photo-1513885041144-809f42633084?q=80&w=600',
                'opening_hours' => '10:00 - 19:00',
                'rating' => 4.7,
                'min_temp' => null,
                'max_temp' => null,
            ],
            // Yogyakarta
            [
                'name' => 'Candi Prambanan',
                'description' => 'Kompleks candi Hindu terbesar di Indonesia yang sangat megah. Sangat direkomendasikan dikunjungi di pagi atau sore hari saat cuaca cerah.',
                'category' => 'outdoor',
                'city_name' => 'Yogyakarta',
                'image_url' => 'https://images.unsplash.com/photo-1582298538104-18c7bc9bc7eb?q=80&w=600',
                'opening_hours' => '06:00 - 17:00',
                'rating' => 4.9,
                'min_temp' => 22,
                'max_temp' => 35,
            ],
            [
                'name' => 'Jalan Malioboro',
                'description' => 'Pusat wisata belanja legendaris di Yogyakarta. Penuh dengan pedagang kaki lima dan pertunjukan seni di malam hari.',
                'category' => 'outdoor',
                'city_name' => 'Yogyakarta',
                'image_url' => 'https://images.unsplash.com/photo-1594968393526-bb21bcac52b5?q=80&w=600',
                'opening_hours' => '24 Jam',
                'rating' => 4.6,
                'min_temp' => 20,
                'max_temp' => 32,
            ],
            [
                'name' => 'Museum Ullen Sentalu',
                'description' => 'Museum yang menyimpan sejarah peradaban dan budaya Mataram Islam. Wisata budaya dalam ruangan yang sangat sejuk dan damai.',
                'category' => 'indoor',
                'city_name' => 'Yogyakarta',
                'image_url' => 'https://images.unsplash.com/photo-1582555172866-f73bb12a2ab3?q=80&w=600',
                'opening_hours' => '08:30 - 16:00',
                'rating' => 4.8,
                'min_temp' => null,
                'max_temp' => null,
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
