<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Agents\WeatherGuideAgent;
use Inertia\Inertia;

class GuideController extends Controller
{
    protected WeatherGuideAgent $agent;

    public function __construct(WeatherGuideAgent $agent)
    {
        $this->agent = $agent;
    }

    public function index(Request $request)
    {
        // Default to Jakarta if no city is provided
        $city = $request->query('city');
        $lat = $request->query('lat');
        $lon = $request->query('lon');
        
        if (!$city && !$lat && !$lon) {
            $city = 'Jakarta';
        }
        
        $guideData = $this->agent->getRecommendations($city, $lat, $lon);

        return Inertia::render('Dashboard', [
            'guideData' => $guideData,
            'currentCity' => $city
        ]);
    }
}
