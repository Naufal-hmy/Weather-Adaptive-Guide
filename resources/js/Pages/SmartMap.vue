<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    cities: Array,
    googleMapsApiKey: String
});

const selectedCityId = ref(props.cities[0]?.id || null);
const forceWeather = ref('Hujan'); // Default to Rain to demonstrate the smart indoor recommendation logic
const userLocation = ref(null);
const weather = ref(null);
const starting = ref(null);
const recommendations = ref([]);
const reasoning = ref('');
const isLoading = ref(false);

let mapInstance = null;
let mapMarkers = [];

// Geolocation state
const trackingStatus = ref('Mendeteksi lokasi...');

// Load Google Maps JS SDK dynamically if API Key is provided
const isGoogleMapsLoaded = ref(false);

const loadGoogleMaps = () => {
    if (!props.googleMapsApiKey) {
        isGoogleMapsLoaded.value = false;
        return;
    }

    if (window.google && window.google.maps) {
        isGoogleMapsLoaded.value = true;
        return;
    }

    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${props.googleMapsApiKey}&libraries=places`;
    script.async = true;
    script.defer = true;
    script.onload = () => {
        isGoogleMapsLoaded.value = true;
        initMap();
    };
    script.onerror = () => {
        isGoogleMapsLoaded.value = false;
        initMap(); // Fallback to Leaflet
    };
    document.head.appendChild(script);
};

// Main fetch function
const fetchRecommendations = async () => {
    isLoading.value = true;
    try {
        const params = {
            city_id: selectedCityId.value,
            force_weather: forceWeather.value
        };

        if (userLocation.value) {
            params.user_lat = userLocation.value.lat;
            params.user_lng = userLocation.value.lng;
        }

        const response = await axios.get(route('api.nearby-recommendations'), { params });
        const data = response.data;

        weather.value = data.weather;
        starting.value = data.starting;
        recommendations.value = data.recommendations;
        reasoning.value = data.reasoning;
        
        if (!userLocation.value) {
            userLocation.value = data.user;
        }

        // Update visual map
        initMap();
    } catch (error) {
        console.error("Error fetching recommendation map data:", error);
    } finally {
        isLoading.value = false;
    }
};

// Initialize / Update Map (supports Google Maps & Leaflet fallback)
const initMap = () => {
    const startLat = starting.value?.lat || -7.8712;
    const startLng = starting.value?.lng || 112.5269;
    const centerCoords = [startLat, startLng];

    // Clear previous Leaflet instance if present
    if (mapInstance && typeof mapInstance.remove === 'function') {
        mapInstance.remove();
        mapInstance = null;
    }
    
    // Clear marker references
    mapMarkers = [];
    document.getElementById('map-view-element').innerHTML = '';

    if (isGoogleMapsLoaded.value) {
        renderGoogleMap(startLat, startLng);
    } else {
        renderLeafletMap(centerCoords);
    }
};

// Render using Leaflet
const renderLeafletMap = (centerCoords) => {
    // Recreate container div because Leaflet requires a clean element
    const container = document.getElementById('map-view-element');
    
    mapInstance = L.map(container).setView(centerCoords, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(mapInstance);

    const bounds = [];

    // Helper for Custom colored Leaflet SVG icons
    const createCustomIcon = (color, name) => {
        let pinColor = '#3b82f6'; // blue
        if (color === 'red') pinColor = '#ef4444';
        if (color === 'green') pinColor = '#10b981';
        if (color === 'indigo') pinColor = '#6366f1';

        return L.divIcon({
            html: `
                <div class="flex flex-col items-center">
                    <div class="relative flex items-center justify-center w-8 h-8 rounded-full shadow-lg text-white font-bold text-xs" style="background-color: ${pinColor}">
                        ${name.substring(0, 1)}
                        <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-3 h-3 rotate-45" style="background-color: ${pinColor}"></div>
                    </div>
                </div>
            `,
            className: 'custom-leaflet-marker',
            iconSize: [32, 42],
            iconAnchor: [16, 42],
            popupAnchor: [0, -40]
        });
    };

    // 1. User Marker
    if (userLocation.value) {
        const userPoint = [userLocation.value.lat, userLocation.value.lng];
        bounds.push(userPoint);
        L.marker(userPoint, { icon: createCustomIcon('indigo', 'U') })
            .addTo(mapInstance)
            .bindPopup("<b>Posisi Anda Saat Ini</b>")
            .openPopup();
    }

    // 2. Starting Destination Marker
    if (starting.value) {
        const startPoint = [starting.value.lat, starting.value.lng];
        bounds.push(startPoint);
        const statusText = starting.value.recommended ? 'Direkomendasikan' : 'TIDAK DIREKOMENDASIKAN (Luar Ruangan / Hujan)';
        L.marker(startPoint, { icon: createCustomIcon(starting.value.marker_color, 'S') })
            .addTo(mapInstance)
            .bindPopup(`
                <div class="p-1">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Destinasi Awal</span>
                    <h4 class="text-sm font-bold text-slate-800 mt-0.5">${starting.value.name}</h4>
                    <p class="text-xs text-slate-600 mt-1">${starting.value.address}</p>
                    <span class="inline-block mt-2 text-[10px] font-extrabold uppercase px-2 py-0.5 rounded ${starting.value.recommended ? 'bg-indigo-50 text-indigo-700' : 'bg-rose-50 text-rose-700'}">
                        ${statusText}
                    </span>
                </div>
            `);
    }

    // 3. Alternative Recommendations Markers
    recommendations.value.forEach((rec, i) => {
        const recPoint = [rec.lat, rec.lng];
        bounds.push(recPoint);
        L.marker(recPoint, { icon: createCustomIcon(rec.marker_color, (i + 1).toString()) })
            .addTo(mapInstance)
            .bindPopup(`
                <div class="p-1 max-w-[200px]">
                    <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider block">Rekomendasi #${i+1} (Indoor)</span>
                    <h4 class="text-sm font-bold text-slate-800 mt-0.5">${rec.name}</h4>
                    <p class="text-[11px] text-slate-500 mt-1">⭐ ${rec.rating} • ${rec.distance} km</p>
                    <p class="text-xs text-slate-600 mt-1">${rec.address}</p>
                    <span class="inline-block mt-2 text-[10px] font-bold bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded">
                        Suitability: ${rec.suitability_score}%
                    </span>
                </div>
            `);
    });

    if (bounds.length > 1) {
        mapInstance.fitBounds(bounds, { padding: [50, 50] });
    }
};

// Render using Google Maps
const renderGoogleMap = (lat, lng) => {
    const mapOptions = {
        center: { lat, lng },
        zoom: 13,
        mapId: 'DEMO_MAP_ID'
    };

    const container = document.getElementById('map-view-element');
    const gMap = new google.maps.Map(container, mapOptions);

    const bounds = new google.maps.LatLngBounds();

    // 1. User Position
    if (userLocation.value) {
        const pos = { lat: userLocation.value.lat, lng: userLocation.value.lng };
        bounds.extend(pos);

        const marker = new google.maps.Marker({
            position: pos,
            map: gMap,
            title: 'Posisi Anda',
            icon: {
                path: google.maps.SymbolPath.BACKWARD_CLOSED_ARROW,
                scale: 6,
                fillColor: '#6366f1',
                fillOpacity: 0.9,
                strokeWeight: 2,
                strokeColor: '#ffffff'
            }
        });

        const info = new google.maps.InfoWindow({ content: '<b>Posisi Anda Saat Ini</b>' });
        marker.addListener('click', () => info.open(gMap, marker));
    }

    // 2. Starting Destination
    if (starting.value) {
        const pos = { lat: starting.value.lat, lng: starting.value.lng };
        bounds.extend(pos);

        const color = starting.value.marker_color === 'red' ? '#ef4444' : '#3b82f6';
        const marker = new google.maps.Marker({
            position: pos,
            map: gMap,
            title: starting.value.name,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 10,
                fillColor: color,
                fillOpacity: 0.9,
                strokeWeight: 2,
                strokeColor: '#ffffff'
            }
        });

        const statusText = starting.value.recommended ? 'Direkomendasikan' : 'TIDAK DIREKOMENDASIKAN (Luar Ruangan / Hujan)';
        const info = new google.maps.InfoWindow({
            content: `
                <div class="p-1">
                    <span style="font-size:10px; font-weight:bold; color:#64748b; text-transform:uppercase;">Destinasi Awal</span>
                    <h4 style="margin:2px 0; font-size:13px; font-weight:bold; color:#1e293b;">${starting.value.name}</h4>
                    <p style="margin:2px 0; font-size:11px; color:#475569;">${starting.value.address}</p>
                    <span style="display:inline-block; margin-top:6px; font-size:9px; font-weight:bold; padding:2px 6px; border-radius:4px; ${starting.value.recommended ? 'background:#e0e7ff; color:#4338ca;' : 'background:#fee2e2; color:#b91c1c;'}">
                        ${statusText}
                    </span>
                </div>
            `
        });
        marker.addListener('click', () => info.open(gMap, marker));
    }

    // 3. Alternative Recommendations
    recommendations.value.forEach((rec, i) => {
        const pos = { lat: rec.lat, lng: rec.lng };
        bounds.extend(pos);

        const marker = new google.maps.Marker({
            position: pos,
            map: gMap,
            title: rec.name,
            label: (i + 1).toString(),
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 12,
                fillColor: '#10b981', // green
                fillOpacity: 0.9,
                strokeWeight: 2,
                strokeColor: '#ffffff'
            }
        });

        const info = new google.maps.InfoWindow({
            content: `
                <div class="p-1 max-w-[200px]">
                    <span style="font-size:10px; font-weight:bold; color:#059669; text-transform:uppercase;">Rekomendasi #${i+1} (Indoor)</span>
                    <h4 style="margin:2px 0; font-size:13px; font-weight:bold; color:#1e293b;">${rec.name}</h4>
                    <p style="margin:2px 0; font-size:11px; color:#64748b;">⭐ ${rec.rating} • ${rec.distance} km</p>
                    <p style="margin:2px 0; font-size:11px; color:#475569;">${rec.address}</p>
                    <span style="display:inline-block; margin-top:6px; font-size:10px; font-weight:bold; background:#d1fae5; color:#065f46; padding:2px 6px; border-radius:4px;">
                        Suitability: ${rec.suitability_score}%
                    </span>
                </div>
            `
        });
        marker.addListener('click', () => info.open(gMap, marker));
    });

    gMap.fitBounds(bounds);
};

// Geolocation trigger
const detectLocation = () => {
    trackingStatus.value = "Meminta izin GPS...";
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLocation.value = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                trackingStatus.value = "GPS Aktif";
                fetchRecommendations();
            },
            (error) => {
                console.warn("User denied Geolocation or error occurred. Using default city coordinates.", error);
                trackingStatus.value = "GPS Ditolak (Menggunakan koordinat kota)";
                fetchRecommendations();
            },
            { enableHighAccuracy: true, timeout: 5000 }
        );
    } else {
        trackingStatus.value = "GPS tidak didukung browser";
        fetchRecommendations();
    }
};

onMounted(() => {
    loadGoogleMaps();
    detectLocation();
});

watch([selectedCityId, forceWeather], () => {
    fetchRecommendations();
});
</script>

<template>
    <Head title="Smart Recommendation Map" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">Smart Recommendation Map</h2>
                    <p class="text-sm text-slate-500 mt-1">Integrasi Google Places & OpenWeather untuk perutean cerdas saat cuaca ekstrem.</p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-700/10">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 mr-1.5 animate-pulse"></span>
                        {{ trackingStatus }}
                    </span>
                </div>
            </div>
        </template>

        <div class="py-8 bg-slate-50/50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
                    
                    <!-- LEFT PANEL (Controls & Recommended Destinations) -->
                    <div class="lg:col-span-4 space-y-6 flex flex-col justify-start">
                        
                        <!-- Configuration Controls -->
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Konfigurasi Perutean</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Pilih kota tujuan rencana awal Anda.</p>
                            </div>
                            
                            <!-- City Input Selection -->
                            <div>
                                <label class="text-[10px] font-semibold text-slate-600 uppercase block mb-1">Kota Tujuan</label>
                                <select 
                                    v-model="selectedCityId" 
                                    class="block w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 py-3 px-4 shadow-sm"
                                >
                                    <option v-for="city in props.cities" :key="city.id" :value="city.id">
                                        {{ city.name }}, {{ city.country }}
                                    </option>
                                </select>
                            </div>

                            <!-- Weather Simulator Toggle -->
                            <div>
                                <label class="text-[10px] font-semibold text-slate-600 uppercase block mb-1.5">Simulasi Cuaca Saat Ini</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <button 
                                        type="button" 
                                        @click="forceWeather = 'Cerah'"
                                        :class="[
                                            'py-2 px-1 text-xs font-bold rounded-xl transition-all border text-center flex flex-col items-center justify-center gap-1',
                                            forceWeather === 'Cerah' ? 'bg-amber-500 border-amber-600 text-white shadow-sm' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                                        ]"
                                    >
                                        <span>☀️</span>
                                        <span>Cerah</span>
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="forceWeather = 'Berawan'"
                                        :class="[
                                            'py-2 px-1 text-xs font-bold rounded-xl transition-all border text-center flex flex-col items-center justify-center gap-1',
                                            forceWeather === 'Berawan' ? 'bg-slate-500 border-slate-600 text-white shadow-sm' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                                        ]"
                                    >
                                        <span>☁️</span>
                                        <span>Berawan</span>
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="forceWeather = 'Hujan'"
                                        :class="[
                                            'py-2 px-1 text-xs font-bold rounded-xl transition-all border text-center flex flex-col items-center justify-center gap-1',
                                            forceWeather === 'Hujan' ? 'bg-blue-600 border-blue-700 text-white shadow-sm' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                                        ]"
                                    >
                                        <span>🌧️</span>
                                        <span>Hujan</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Smart Reason and Agent Reasoning -->
                        <div v-if="reasoning" class="bg-gradient-to-r from-blue-50/50 to-indigo-50/50 border-l-4 border-indigo-500 rounded-r-2xl p-5 shadow-sm">
                            <div class="flex gap-3">
                                <div class="flex-shrink-0 bg-indigo-100 text-indigo-600 rounded-full p-2 h-9 w-9 flex items-center justify-center font-bold text-sm">
                                    💡
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Analisis Smart Recommendation</h4>
                                    <p class="text-xs text-indigo-900/90 font-medium mt-1 leading-relaxed">
                                        {{ reasoning }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Recommendations list -->
                        <div class="space-y-3 flex-grow overflow-y-auto">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-slate-800">Daftar Destinasi Rekomendasi</h3>
                                <span class="text-xs text-slate-400 font-semibold">{{ recommendations.length }} tempat terdekat</span>
                            </div>

                            <!-- List Item -->
                            <div v-if="isLoading" class="text-center py-8 text-xs text-slate-400 italic">
                                Memproses data cuaca & merekomendasikan...
                            </div>
                            
                            <template v-else>
                                <!-- Starting destination status -->
                                <div 
                                    v-if="starting"
                                    :class="[
                                        'p-4 rounded-xl border flex items-start gap-3 shadow-xs',
                                        starting.recommended ? 'bg-blue-50/30 border-blue-100' : 'bg-rose-50/30 border-rose-100'
                                    ]"
                                >
                                    <div :class="[
                                        'p-2 rounded-lg font-bold text-white text-xs',
                                        starting.recommended ? 'bg-blue-500' : 'bg-rose-500'
                                    ]">
                                        Awal
                                    </div>
                                    <div class="flex-grow">
                                        <div class="flex justify-between items-start">
                                            <h4 class="text-xs font-bold text-slate-800">{{ starting.name }}</h4>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase">🌳 Outdoor</span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 mt-1 leading-relaxed">{{ starting.address }}</p>
                                        <span 
                                            :class="[
                                                'inline-block mt-2 text-[9px] font-extrabold uppercase px-2 py-0.5 rounded',
                                                starting.recommended ? 'bg-indigo-100 text-indigo-800' : 'bg-rose-100 text-rose-800'
                                            ]"
                                        >
                                            {{ starting.recommended ? 'Rencana Utama: Aman Dikunjungi' : 'Batal Rencana: Resiko Cuaca Hujan' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Dynamic Recommendations -->
                                <div 
                                    v-for="(rec, index) in recommendations" 
                                    :key="rec.name" 
                                    class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 flex gap-3 items-start hover:shadow-md transition-shadow"
                                >
                                    <div class="w-16 h-16 rounded-lg overflow-hidden bg-slate-100 flex-shrink-0">
                                        <img :src="rec.photo" alt="Photo" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-grow">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <span class="text-[9px] font-extrabold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded uppercase tracking-wider">
                                                    Alternatif #${index + 1}
                                                </span>
                                                <h4 class="text-xs font-bold text-slate-800 mt-1">{{ rec.name }}</h4>
                                            </div>
                                            <div class="bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-black rounded-lg px-2 py-1 text-center">
                                                {{ rec.suitability_score }}%
                                                <span class="block text-[7px] text-indigo-500 font-bold uppercase tracking-tighter">Match</span>
                                            </div>
                                        </div>
                                        <p class="text-[10px] text-slate-500 mt-1 leading-relaxed">{{ rec.address }}</p>
                                        <div class="flex items-center gap-2 mt-2 text-[10px] text-slate-400 font-medium">
                                            <span>⭐ {{ rec.rating }}</span>
                                            <span>•</span>
                                            <span>📍 {{ rec.distance }} km dari tujuan awal</span>
                                            <span>•</span>
                                            <span :class="rec.open_now ? 'text-emerald-600 font-bold' : 'text-rose-500'">{{ rec.opening_hours }}</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- RIGHT PANEL (Interactive Map View) -->
                    <div class="lg:col-span-8 h-[600px] lg:h-auto rounded-3xl overflow-hidden shadow-md border border-slate-100 flex flex-col bg-white">
                        <div class="bg-slate-900 px-6 py-4 flex items-center justify-between text-white border-b border-slate-800">
                            <div class="flex items-center space-x-3">
                                <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></div>
                                <span class="text-xs font-bold tracking-wider uppercase">Visual Map Routing Live</span>
                            </div>
                            <div class="flex gap-4 text-[10px] font-bold text-slate-400">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Posisi Anda
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span> Tujuan Awal (Cerah)
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span> Rencana Rawan (Hujan)
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Alternatif Terpilih (Indoor)
                                </span>
                            </div>
                        </div>
                        <div class="relative flex-grow min-h-[400px]">
                            <!-- Loading overlay -->
                            <div v-if="isLoading" class="absolute inset-0 bg-slate-900/10 backdrop-blur-xs flex items-center justify-center z-50">
                                <div class="bg-white rounded-xl shadow-lg px-4 py-3 flex items-center space-x-3 text-sm text-slate-700 font-semibold border border-slate-200">
                                    <span class="animate-spin text-lg">⏳</span>
                                    <span>Memperbarui titik lokasi...</span>
                                </div>
                            </div>

                            <div id="map-view-element" class="w-full h-full min-h-[500px]"></div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.leaflet-container {
    height: 100% !important;
    width: 100% !important;
}

.custom-leaflet-marker {
    background: none !important;
    border: none !important;
}
</style>
