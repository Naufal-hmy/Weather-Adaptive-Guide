<?php

namespace Database\Seeders;

use App\Models\Destination;
use Illuminate\Database\Seeder;

class DestinationSeeder extends Seeder
{
    public function run(): void
    {
        $destinations = [
            [
                'name' => 'Taman Impian Jaya Ancol',
                'description' => 'A large amusement park and beach area. Best enjoyed in sunny weather.',
                'category' => 'outdoor',
                'city' => 'Jakarta',
                'image_url' => 'https://images.unsplash.com/photo-1571439223789-9b936eef67da?q=80&w=600',
            ],
            [
                'name' => 'Museum Nasional',
                'description' => 'The National Museum of Indonesia. A perfect indoor activity for a rainy day.',
                'category' => 'indoor',
                'city' => 'Jakarta',
                'image_url' => 'https://images.unsplash.com/photo-1582555172866-f73bb12a2ab3?q=80&w=600',
            ],
            [
                'name' => 'Kebun Raya Bogor',
                'description' => 'Botanical gardens with diverse plants. Great for walking when the sky is clear.',
                'category' => 'outdoor',
                'city' => 'Bogor',
                'image_url' => 'https://images.unsplash.com/photo-1542314831-c6a42072120e?q=80&w=600',
            ],
            [
                'name' => 'Jakarta Aquarium & Safari',
                'description' => 'Indoor aquarium inside a shopping mall. Discover marine life comfortably regardless of weather.',
                'category' => 'indoor',
                'city' => 'Jakarta',
                'image_url' => 'https://images.unsplash.com/photo-1533414488820-b4974ba5a278?q=80&w=600',
            ],
            [
                'name' => 'Dufan (Dunia Fantasi)',
                'description' => 'Theme park with outdoor rides.',
                'category' => 'outdoor',
                'city' => 'Jakarta',
                'image_url' => 'https://images.unsplash.com/photo-1509316785289-025f5b846b35?q=80&w=600',
            ],
            [
                'name' => 'Grand Indonesia Mall',
                'description' => 'One of the largest shopping malls. Full of indoor entertainment, dining, and shopping.',
                'category' => 'indoor',
                'city' => 'Jakarta',
                'image_url' => 'https://images.unsplash.com/photo-1519567241046-7f4f03937112?q=80&w=600',
            ]
        ];

        foreach ($destinations as $dest) {
            Destination::create($dest);
        }
    }
}
