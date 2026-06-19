<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, onMounted, watch, computed } from 'vue';

const props = defineProps({
    cities: Array,
    selectedCityId: Number,
    guideData: Object,
    stats: Object,
    googleMapsApiKey: String
});

const selectedCity = ref(props.selectedCityId);
const searchQuery = ref('');
const showSuggestions = ref(false);
const searchInput = ref(null);

const filteredCities = computed(() => {
    if (!searchQuery.value) {
        return props.cities;
    }
    const query = searchQuery.value.toLowerCase();
    return props.cities.filter(city => 
        city.name.toLowerCase().includes(query) || 
        city.country.toLowerCase().includes(query)
    );
});

const selectCity = (city) => {
    searchQuery.value = city.name;
    selectedCity.value = city.id;
    showSuggestions.value = false;
    changeCity();
};

const handleBlur = () => {
    setTimeout(() => {
        showSuggestions.value = false;
        if (!searchQuery.value) {
            const name = props.guideData?.city?.name;
            if (name) searchQuery.value = name;
        }
    }, 200);
};

const onEnter = () => {
    if (filteredCities.value.length > 0) {
        selectCity(filteredCities.value[0]);
    }
};

const initAutocomplete = () => {
    if (!searchInput.value || !window.google || !window.google.maps) return;
    const autocomplete = new window.google.maps.places.Autocomplete(searchInput.value, {
        types: ['(regions)'],
        fields: ['geometry', 'name', 'formatted_address']
    });
    
    autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace();
        if (place.geometry && place.geometry.location) {
            const lat = place.geometry.location.lat();
            const lng = place.geometry.location.lng();
            const name = place.name;
            
            router.get(route('dashboard'), { lat, lng, q: name }, { preserveState: true });
        }
    });
};

const loadGooglePlacesAutocomplete = () => {
    if (!props.googleMapsApiKey) return;
    if (window.google && window.google.maps && window.google.maps.places) {
        initAutocomplete();
        return;
    }
    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${props.googleMapsApiKey}&libraries=places`;
    script.async = true;
    script.defer = true;
    script.onload = initAutocomplete;
    document.head.appendChild(script);
};

watch(() => props.guideData?.city?.name, (newName) => {
    if (newName) {
        searchQuery.value = newName;
    }
}, { immediate: true });

watch(() => props.selectedCityId, (newId) => {
    if (newId) {
        selectedCity.value = newId;
        const current = props.cities.find(c => c.id === newId);
        if (current) {
            searchQuery.value = current.name;
        }
    }
});

// Hardcoded coordinates for map visual targeting
const cityCoords = {
    'Jakarta': [-6.2088, 106.8456],
    'Malang': [-7.9839, 112.6214],
    'Bandung': [-6.9175, 107.6191],
    'Bogor': [-6.5971, 106.8060],
    'Batu': [-7.8712, 112.5269]
};

let map = null;
let mapMarkers = [];
const userLocation = ref(null);

// Form for simulating weather (Admin Only)
const weatherForm = useForm({
    status: props.guideData?.weather?.status || 'Cerah',
    temperature: props.guideData?.weather?.temperature || 28,
    humidity: props.guideData?.weather?.humidity || 70,
    wind_speed: props.guideData?.weather?.wind_speed || 10,
});

// Update dashboard based on city selection
const changeCity = () => {
    router.get(route('dashboard'), { city_id: selectedCity.value }, { preserveState: true });
};

// Admin action to update weather
const submitWeatherSimulation = () => {
    weatherForm.post(route('admin.weather.update', selectedCity.value), {
        onSuccess: () => {
            // Recommendation updates are triggered automatically in backend service
        }
    });
};

const getWeatherBg = (status) => {
    if (status === 'Hujan') return 'from-blue-600 via-sky-500 to-indigo-600';
    if (status === 'Berawan') return 'from-slate-500 via-zinc-400 to-slate-600';
    return 'from-amber-400 via-orange-400 to-amber-500';
};

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

const updateMap = () => {
    if (!map) return;
    
    // Clear old markers
    mapMarkers.forEach(m => map.removeLayer(m));
    mapMarkers = [];

    const cityName = props.guideData?.city?.name;
    const coords = cityCoords[cityName] || [-7.8712, 112.5269];
    
    const bounds = [];

    // 1. User Position Marker (if tracking is allowed)
    if (userLocation.value) {
        const userPoint = [userLocation.value.lat, userLocation.value.lng];
        bounds.push(userPoint);
        const uMarker = L.marker(userPoint, { icon: createCustomIcon('indigo', 'U') })
            .addTo(map)
            .bindPopup("<b>Posisi Anda Saat Ini</b>");
        mapMarkers.push(uMarker);
    } else {
        // Fallback user position slightly offset for visual mapping demo
        const userPoint = [coords[0] + 0.008, coords[1] - 0.008];
        bounds.push(userPoint);
        const uMarker = L.marker(userPoint, { icon: createCustomIcon('indigo', 'U') })
            .addTo(map)
            .bindPopup("<b>Estimasi Posisi Anda</b>");
        mapMarkers.push(uMarker);
    }

    // 2. City Center / Starting Point
    const weatherStatus = props.guideData?.weather?.status || 'Cerah';
    const startPoint = [coords[0], coords[1]];
    bounds.push(startPoint);
    
    const startingColor = weatherStatus === 'Hujan' ? 'red' : 'blue';
    const sMarker = L.marker(startPoint, { icon: createCustomIcon(startingColor, 'S') })
        .addTo(map)
        .bindPopup(`<b>${cityName} (Pusat Kota)</b><br>Cuaca: ${weatherStatus}`);
    mapMarkers.push(sMarker);

    // 3. Recommended Places (Google Places API alternatives)
    if (props.guideData?.destinations) {
        props.guideData.destinations.forEach((dest, i) => {
            if (dest.lat && dest.lng) {
                const destPoint = [dest.lat, dest.lng];
                bounds.push(destPoint);
                
                const markerColor = dest.category === 'indoor' ? 'green' : 'blue';
                const dMarker = L.marker(destPoint, { icon: createCustomIcon(markerColor, (i+1).toString()) })
                    .addTo(map)
                    .bindPopup(`
                        <div class="p-1 max-w-[200px]">
                            <span class="text-[10px] font-bold text-emerald-600 uppercase block">Rekomendasi #${i+1}</span>
                            <h4 class="text-xs font-bold text-slate-800 mt-0.5">${dest.name}</h4>
                            <p class="text-[10px] text-slate-500 mt-1">⭐ ${dest.rating} • ${dest.distance} km</p>
                            <span class="inline-block mt-1 text-[9px] font-bold bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded">
                                Match: ${dest.suitability_score}%
                            </span>
                        </div>
                    `);
                mapMarkers.push(dMarker);
            }
        });
    }

    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [40, 40] });
    }
};

onMounted(() => {
    // Geolocation trigger
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition((pos) => {
            userLocation.value = {
                lat: pos.coords.latitude,
                lng: pos.coords.longitude
            };
            updateMap();
        });
    }

    // Initial map setup
    const cityName = props.guideData?.city?.name;
    const coords = cityCoords[cityName] || [-7.8712, 112.5269];

    map = L.map('interactive-map').setView(coords, 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    updateMap();
    loadGooglePlacesAutocomplete();
});

// Watch for changes in city name to move the map
watch(() => props.guideData?.city?.name, () => {
    updateMap();
    if (props.guideData?.weather) {
        weatherForm.status = props.guideData.weather.status;
        weatherForm.temperature = props.guideData.weather.temperature;
        weatherForm.humidity = props.guideData.weather.humidity;
        weatherForm.wind_speed = props.guideData.weather.wind_speed;
    }
});
</script>

<template>
    <Head title="Smart Travel Weather Guide" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">Smart Travel Weather Guide</h2>
                    <p class="text-sm text-slate-500 mt-1">Platform Travel-Tech Cerdas dengan sistem rekomendasi otomatis berbasis cuaca.</p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 mr-1.5 animate-pulse"></span>
                        Weather Agent Ready
                    </span>
                </div>
            </div>
        </template>

        <div class="py-8 bg-slate-50/50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Main Grid Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- LEFT COLUMN (Controls & Simulator) -->
                    <div class="lg:col-span-4 space-y-6">
                        
                        <!-- City Selector Card -->
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Pilih Kota Tujuan</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Sistem akan menyajikan cuaca hari ini & rekomendasi destinasi.</p>
                            </div>
                            
                            <div class="relative">
                                <div class="relative">
                                    <input 
                                        ref="searchInput"
                                        type="text"
                                        v-model="searchQuery"
                                        @focus="showSuggestions = !props.googleMapsApiKey"
                                        @blur="handleBlur"
                                        @keyup.enter="onEnter"
                                        placeholder="Cari lokasi tujuan..."
                                        class="block w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 py-3 pl-4 pr-10 shadow-sm"
                                    />
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </span>
                                </div>

                                <!-- Suggestions Dropdown -->
                                <div 
                                    v-if="showSuggestions && filteredCities.length > 0" 
                                    class="absolute z-50 mt-1 w-full bg-white rounded-xl border border-slate-200 shadow-lg max-h-60 overflow-y-auto py-1"
                                >
                                    <button
                                        v-for="city in filteredCities"
                                        :key="city.id"
                                        type="button"
                                        @mousedown="selectCity(city)"
                                        class="w-full text-left px-4 py-2.5 text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors flex justify-between items-center"
                                    >
                                        <span>{{ city.name }}, {{ city.country }}</span>
                                        <span v-if="city.id === props.selectedCityId" class="text-indigo-600 text-xs font-semibold">Aktif</span>
                                    </button>
                                </div>
                                
                                <div 
                                    v-else-if="showSuggestions && filteredCities.length === 0" 
                                    class="absolute z-50 mt-1 w-full bg-white rounded-xl border border-slate-200 shadow-lg py-3 px-4 text-xs text-slate-500 italic"
                                >
                                    Kota tidak ditemukan
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Card -->
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
                            <h3 class="text-base font-bold text-slate-900">Statistik Destinasi Global</h3>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100 text-center">
                                    <span class="text-xs font-semibold text-emerald-700 block">Outdoor</span>
                                    <span class="text-3xl font-black text-emerald-800 mt-1 block">
                                        {{ props.stats?.outdoor || 0 }}
                                    </span>
                                    <span class="text-[10px] text-emerald-600 block mt-1">Destinasi</span>
                                </div>
                                
                                <div class="bg-indigo-50 rounded-xl p-4 border border-indigo-100 text-center">
                                    <span class="text-xs font-semibold text-indigo-700 block">Indoor</span>
                                    <span class="text-3xl font-black text-indigo-800 mt-1 block">
                                        {{ props.stats?.indoor || 0 }}
                                    </span>
                                    <span class="text-[10px] text-indigo-600 block mt-1">Destinasi</span>
                                </div>
                            </div>
                        </div>

                        <!-- Weather Simulator Card (Admin Only) -->
                        <div 
                            v-if="$page.props.auth.user?.is_admin && selectedCity" 
                            class="bg-white rounded-2xl border border-rose-100 shadow-sm p-6 space-y-4"
                        >
                            <div class="border-b border-rose-50 pb-3 flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Weather Simulator</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Simulasikan cuaca untuk kota {{ props.guideData?.city?.name }}.</p>
                                </div>
                                <span class="bg-rose-100 text-rose-800 text-[10px] font-bold px-2 py-0.5 rounded uppercase">Admin</span>
                            </div>

                            <form @submit.prevent="submitWeatherSimulation" class="space-y-4">
                                <div>
                                    <label class="text-xs font-semibold text-slate-600 uppercase block">Status Cuaca</label>
                                    <select 
                                        v-model="weatherForm.status"
                                        class="mt-1 block w-full rounded-xl border-slate-200 text-xs focus:border-rose-500 focus:ring-rose-500 py-2.5 px-3"
                                    >
                                        <option value="Cerah">☀️ Cerah</option>
                                        <option value="Berawan">☁️ Berawan</option>
                                        <option value="Hujan">🌧️ Hujan</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label class="text-[10px] font-semibold text-slate-600 uppercase block">Suhu (°C)</label>
                                        <input 
                                            v-model="weatherForm.temperature" 
                                            type="number" 
                                            class="mt-1 block w-full rounded-xl border-slate-200 text-xs focus:border-rose-500 focus:ring-rose-500 py-2 px-2.5"
                                        >
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-semibold text-slate-600 uppercase block">Kelembapan (%)</label>
                                        <input 
                                            v-model="weatherForm.humidity" 
                                            type="number" 
                                            class="mt-1 block w-full rounded-xl border-slate-200 text-xs focus:border-rose-500 focus:ring-rose-500 py-2 px-2.5"
                                        >
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-semibold text-slate-600 uppercase block">Kecepatan Angin (m/s)</label>
                                        <input 
                                            v-model="weatherForm.wind_speed" 
                                            type="number" 
                                            class="mt-1 block w-full rounded-xl border-slate-200 text-xs focus:border-rose-500 focus:ring-rose-500 py-2 px-2.5"
                                        >
                                    </div>
                                </div>

                                <button 
                                    type="submit" 
                                    :disabled="weatherForm.processing"
                                    class="w-full inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-rose-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600 transition-all active:scale-[0.98] disabled:opacity-50"
                                >
                                    Perbarui Simulasi Cuaca
                                </button>
                            </form>
                        </div>

                        <!-- Map Card -->
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden p-6 space-y-4">
                            <h3 class="text-base font-bold text-slate-900">Peta Lokasi</h3>
                            <div class="relative overflow-hidden rounded-xl border border-slate-200">
                                <div id="interactive-map" class="w-full h-64 z-10"></div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN (Weather & Destinations) -->
                    <div class="lg:col-span-8 space-y-6">
                        
                        <!-- Error Alert -->
                        <div v-if="props.guideData.is_error" class="bg-rose-50 border border-rose-100 rounded-2xl p-5 shadow-sm">
                            <div class="flex items-center space-x-3">
                                <svg class="h-5 w-5 text-rose-500 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div>
                                    <h3 class="text-sm font-bold text-rose-800">Kesalahan Sistem</h3>
                                    <p class="text-xs text-rose-700 mt-0.5">{{ props.guideData.error_message }}</p>
                                </div>
                            </div>
                        </div>

                        <template v-else>
                            <!-- Weather Status Panel -->
                            <div :class="[
                                'relative overflow-hidden rounded-3xl bg-gradient-to-br text-white shadow-lg p-8 transition-all duration-500', 
                                getWeatherBg(props.guideData.weather?.status)
                            ]">
                                <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-6">
                                    <div class="space-y-4">
                                        <div class="text-xs font-bold uppercase tracking-wider opacity-85 flex items-center space-x-2">
                                            <span>☀️ WEATHER TODAY</span>
                                            <span>•</span>
                                            <span>Terakhir Diupdate: {{ new Date(props.guideData.weather?.updated_at).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'}) }} WIB</span>
                                        </div>
                                        <div>
                                            <h3 class="text-4xl font-extrabold tracking-tight">
                                                {{ props.guideData.city?.name }}
                                            </h3>
                                            <p class="text-lg opacity-90 mt-1 flex items-center gap-1.5">
                                                <span v-if="props.guideData.weather?.status === 'Cerah'">☀️ Cerah</span>
                                                <span v-else-if="props.guideData.weather?.status === 'Berawan'">☁️ Berawan</span>
                                                <span v-else>🌧️ Hujan</span>
                                            </p>
                                        </div>
                                        <div class="text-5xl font-black tracking-tighter">
                                            {{ props.guideData.weather?.temperature }}°C
                                        </div>
                                    </div>

                                    <!-- Decision Summary Widget -->
                                    <div class="bg-white/10 backdrop-blur-md rounded-2xl p-5 border border-white/10 text-right min-w-[200px] flex flex-col justify-center shadow-inner">
                                        <span class="text-xs opacity-85 font-semibold uppercase tracking-wider block">Mode Rekomendasi</span>
                                        <span class="text-2xl font-bold tracking-tight block mt-1">
                                            {{ props.guideData.weather?.status === 'Hujan' ? 'Indoor Only' : (props.guideData.weather?.status === 'Cerah' ? 'Outdoor Only' : 'Indoor & Outdoor') }}
                                        </span>
                                        <span class="text-xs opacity-90 mt-2 block font-medium">
                                            {{ props.guideData.weather?.status === 'Hujan' ? 'Menghindari Hujan' : (props.guideData.weather?.status === 'Cerah' ? 'Aktivitas Luar Ruangan' : 'Bebas Pilih Destinasi') }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Meteorological details grid -->
                                <div class="grid grid-cols-3 gap-4 mt-8 pt-6 border-t border-white/10 text-center">
                                    <div class="p-3 bg-white/5 rounded-2xl border border-white/5">
                                        <span class="text-[10px] opacity-75 font-bold uppercase tracking-wider block">Angin</span>
                                        <span class="text-base font-bold mt-0.5 block">
                                            {{ props.guideData.weather?.wind_speed }} m/s
                                        </span>
                                    </div>
                                    <div class="p-3 bg-white/5 rounded-2xl border border-white/5">
                                        <span class="text-[10px] opacity-75 font-bold uppercase tracking-wider block">Kelembapan</span>
                                        <span class="text-base font-bold mt-0.5 block">
                                            {{ props.guideData.weather?.humidity }}%
                                        </span>
                                    </div>
                                    <div class="p-3 bg-white/5 rounded-2xl border border-white/5">
                                        <span class="text-[10px] opacity-75 font-bold uppercase tracking-wider block">Suhu Cuaca</span>
                                        <span class="text-base font-bold mt-0.5 block">
                                            {{ props.guideData.weather?.temperature }}°C
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Agent Reasoning Card -->
                            <div class="bg-gradient-to-r from-blue-50/50 to-indigo-50/50 border-l-4 border-indigo-500 rounded-r-2xl p-5 shadow-sm flex items-start gap-4">
                                <div class="flex-shrink-0 mt-0.5 bg-indigo-100 text-indigo-600 rounded-full p-2">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Alasan Rekomendasi Agen Cerdas</h4>
                                    <p class="text-sm text-indigo-900/90 font-medium mt-1 leading-relaxed">
                                        {{ props.guideData.reason }}
                                    </p>
                                </div>
                            </div>

                            <!-- Recommendations Section -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-bold text-slate-900">Rekomendasi Destinasi</h3>
                                    <span class="text-xs text-slate-400 font-medium">
                                        {{ props.guideData.destinations?.length || 0 }} tempat cocok
                                    </span>
                                </div>

                                <!-- Cards Grid -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div 
                                        v-for="dest in props.guideData.destinations" 
                                        :key="dest.id" 
                                        class="group bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 flex flex-col h-full"
                                    >
                                        <!-- Image Header -->
                                        <div class="relative h-44 overflow-hidden bg-slate-100">
                                            <img 
                                                :src="dest.image_url || 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?q=80&w=600'" 
                                                alt="Destination" 
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                            >
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>
                                            
                                            <!-- Category Badge -->
                                            <span :class="[
                                                'absolute top-4 right-4 px-2.5 py-1 text-[9px] font-bold uppercase tracking-wider rounded-lg shadow-sm text-white',
                                                dest.category === 'indoor' ? 'bg-indigo-600' : 'bg-emerald-600'
                                            ]">
                                                {{ dest.category === 'indoor' ? '🏠 Indoor' : '🌳 Outdoor' }}
                                            </span>

                                            <!-- Rating Badge -->
                                            <span class="absolute bottom-4 left-4 inline-flex items-center gap-1 text-xs font-bold text-white bg-black/40 backdrop-blur-sm px-2 py-0.5 rounded-lg">
                                                ⭐ {{ dest.rating }}
                                            </span>
                                        </div>

                                        <!-- Content Body -->
                                        <div class="p-5 flex flex-col justify-between flex-grow space-y-4">
                                            <div class="space-y-1.5">
                                                <h4 class="text-base font-bold text-slate-800 leading-snug group-hover:text-indigo-600 transition-colors">
                                                    {{ dest.name }}
                                                </h4>
                                                <p class="text-xs text-slate-500 line-clamp-3 leading-relaxed">
                                                    {{ dest.description }}
                                                </p>
                                            </div>

                                            <div class="flex items-center justify-between text-[11px] text-slate-400 pt-3 border-t border-slate-50 font-medium">
                                                <div class="flex items-center">
                                                    <span class="mr-1">🕒</span> Jam Buka: {{ dest.opening_hours }}
                                                </div>
                                                <div class="flex items-center text-slate-400">
                                                    📍 {{ dest.city?.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div 
                                        v-if="!props.guideData.destinations || props.guideData.destinations.length === 0" 
                                        class="col-span-2 text-slate-500 italic bg-white rounded-2xl border border-slate-100 p-8 text-center"
                                    >
                                        Belum ada destinasi terdaftar untuk kota ini dengan kategori cuaca sekarang.
                                    </div>
                                </div>
                            </div>

                            <!-- Audit Timeline / Logs History -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-bold text-slate-900">Riwayat Perubahan Rekomendasi (Logs)</h3>
                                
                                <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm space-y-6">
                                    <div 
                                        v-if="!props.guideData.logs || props.guideData.logs.length === 0"
                                        class="text-xs text-slate-400 italic text-center"
                                    >
                                        Belum ada riwayat perubahan rekomendasi untuk kota ini.
                                    </div>

                                    <div v-else class="flow-root">
                                        <ul role="list" class="-mb-8">
                                            <li v-for="(log, logIdx) in props.guideData.logs" :key="log.id">
                                                <div class="relative pb-8">
                                                    <span 
                                                        v-if="logIdx !== props.guideData.logs.length - 1" 
                                                        class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-200" 
                                                        aria-hidden="true"
                                                    ></span>
                                                    <div class="relative flex space-x-3">
                                                        <div>
                                                            <span :class="[
                                                                'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white text-xs',
                                                                log.weather_status === 'Hujan' ? 'bg-blue-100 text-blue-600' : (log.weather_status === 'Cerah' ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-slate-600')
                                                            ]">
                                                                {{ log.weather_status === 'Hujan' ? '🌧️' : (log.weather_status === 'Cerah' ? '☀️' : '☁️') }}
                                                            </span>
                                                        </div>
                                                        <div class="flex-1 min-w-0 pt-1.5 flex justify-between space-x-4">
                                                            <div>
                                                                <p class="text-xs text-slate-500">
                                                                    Rekomendasi diadaptasi ke <span class="font-bold text-slate-800">{{ log.weather_status }}</span>:
                                                                    <span class="italic text-slate-600 block mt-1">"{{ log.reason }}"</span>
                                                                </p>
                                                            </div>
                                                            <div class="text-right text-[10px] whitespace-nowrap text-slate-400 font-semibold">
                                                                {{ new Date(log.created_at).toLocaleString('id-ID', {day: 'numeric', month: 'short', hour: '2-digit', minute:'2-digit'}) }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </template>

                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.leaflet-container {
    border-radius: 0.75rem;
}
.custom-leaflet-marker {
    background: none !important;
    border: none !important;
}
</style>
