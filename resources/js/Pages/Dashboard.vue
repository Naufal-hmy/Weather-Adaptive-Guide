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
    if (status === 'Hujan') return 'bg-gradient-to-br from-indigo-900 via-slate-800 to-blue-900 shadow-indigo-900/50';
    if (status === 'Berawan') return 'bg-gradient-to-br from-slate-600 via-slate-700 to-slate-800 shadow-slate-700/50';
    return 'bg-gradient-to-br from-orange-400 via-amber-500 to-rose-500 shadow-orange-500/50';
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
        <!-- Custom Hero / Header within AuthenticatedLayout -->
        <template #header>
            <div class="relative overflow-hidden bg-slate-900 rounded-[2rem] p-8 lg:p-12 shadow-2xl mt-4 mb-2 mx-4 lg:mx-0 border border-slate-800">
                <!-- Abstract Background -->
                <div class="absolute inset-0 opacity-40">
                    <div class="absolute -top-24 -left-24 w-96 h-96 bg-indigo-500 rounded-full mix-blend-screen filter blur-[100px] opacity-60"></div>
                    <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-emerald-500 rounded-full mix-blend-screen filter blur-[100px] opacity-60"></div>
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-cyan-500 rounded-full mix-blend-screen filter blur-[100px] opacity-40"></div>
                </div>
                
                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div class="max-w-2xl">
                        <div class="inline-flex items-center space-x-2 bg-white/10 backdrop-blur-md rounded-full px-4 py-1.5 mb-6 border border-white/10 shadow-lg">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span class="text-[11px] font-bold text-white/90 tracking-widest uppercase">AI Engine Active</span>
                        </div>
                        <h2 class="text-4xl md:text-5xl font-black tracking-tight text-white mb-4 leading-tight">
                            Smart Travel <br/><span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-emerald-400 to-emerald-200">Weather Guide</span>
                        </h2>
                        <p class="text-base md:text-lg text-slate-300 font-medium max-w-xl leading-relaxed">
                            Platform Travel-Tech Cerdas dengan sistem rekomendasi otomatis berbasis cuaca. Temukan destinasi terbaik kapanpun, dimanapun.
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-10 bg-[#F8FAFC] dark:bg-slate-950 min-h-screen relative">
            <!-- Decorative Background Elements -->
            <div class="absolute top-0 w-full h-[500px] bg-gradient-to-b from-indigo-50/80 to-transparent pointer-events-none"></div>
            
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                
                <!-- Main Grid Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- LEFT COLUMN (Controls & Simulator) -->
                    <div class="lg:col-span-4 space-y-8">
                        
                        <!-- City Selector Card -->
                        <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-xl rounded-[2rem] border border-white dark:border-slate-700 shadow-xl shadow-slate-200/50 dark:shadow-none p-8 hover:shadow-2xl hover:shadow-slate-200/60 dark:hover:shadow-slate-900/50 transition-all duration-300">
                            <div class="mb-6">
                                <h3 class="text-xl font-black text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                    <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    Pilih Kota Tujuan
                                </h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1.5 font-medium">Sistem akan menyajikan cuaca hari ini & rekomendasi destinasi otomatis.</p>
                            </div>
                            
                            <div class="relative group">
                                <div class="relative">
                                    <input 
                                        ref="searchInput"
                                        type="text"
                                        v-model="searchQuery"
                                        @focus="showSuggestions = !props.googleMapsApiKey"
                                        @blur="handleBlur"
                                        @keyup.enter="onEnter"
                                        placeholder="Cari lokasi tujuan..."
                                        class="block w-full rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 text-sm font-bold text-slate-700 dark:text-slate-200 focus:border-indigo-500 focus:ring-0 py-4 pl-5 pr-14 shadow-inner transition-all group-hover:bg-white dark:group-hover:bg-slate-800"
                                    />
                                    <button @click="onEnter" class="absolute inset-y-2 right-2 flex items-center justify-center w-10 h-10 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors shadow-md hover:shadow-lg hover:-translate-y-0.5 duration-200">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Suggestions Dropdown -->
                                <Transition
                                    enter-active-class="transition ease-out duration-200"
                                    enter-from-class="opacity-0 translate-y-2"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition ease-in duration-150"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 translate-y-2"
                                >
                                    <div 
                                        v-if="showSuggestions && filteredCities.length > 0" 
                                        class="absolute z-50 mt-3 w-full bg-white/95 dark:bg-slate-800/95 backdrop-blur-xl rounded-2xl border border-slate-100 dark:border-slate-700 shadow-2xl max-h-60 overflow-y-auto py-2"
                                    >
                                        <button
                                            v-for="city in filteredCities"
                                            :key="city.id"
                                            type="button"
                                            @mousedown="selectCity(city)"
                                            class="w-full text-left px-5 py-3.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-700 dark:hover:text-indigo-400 transition-colors flex justify-between items-center group/item border-b border-slate-50 dark:border-slate-700/50 last:border-0"
                                        >
                                            <span class="font-bold group-hover/item:translate-x-1 transition-transform">{{ city.name }}, <span class="text-slate-400 group-hover/item:text-indigo-400 font-medium">{{ city.country }}</span></span>
                                            <span v-if="city.id === props.selectedCityId" class="text-indigo-600 text-[10px] font-black bg-indigo-100 px-2.5 py-1 rounded-md uppercase tracking-wider">Aktif</span>
                                        </button>
                                    </div>
                                </Transition>
                            </div>
                        </div>

                        <!-- Map Card -->
                        <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-xl rounded-[2rem] border border-white dark:border-slate-700 shadow-xl shadow-slate-200/50 dark:shadow-none overflow-hidden hover:shadow-2xl hover:shadow-slate-200/60 dark:hover:shadow-slate-900/50 transition-all duration-300 p-2">
                            <div class="px-6 pt-5 pb-4 flex items-center justify-between">
                                <h3 class="text-lg font-black text-slate-800 dark:text-slate-100">Peta Interaktif</h3>
                                <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                                </div>
                            </div>
                            <div class="relative overflow-hidden rounded-[1.5rem] border-4 border-white dark:border-slate-700 shadow-inner bg-slate-100 dark:bg-slate-900 mx-2 mb-2">
                                <div id="interactive-map" class="w-full h-72 z-10"></div>
                            </div>
                        </div>

                        <!-- Statistics Card -->
                        <div class="bg-slate-900 rounded-[2rem] shadow-2xl p-8 text-white overflow-hidden relative group">
                            <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/20 to-purple-500/20 opacity-50 group-hover:opacity-100 transition-opacity duration-500"></div>
                            <div class="absolute top-0 right-0 w-40 h-40 bg-white/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-700"></div>
                            <h3 class="text-xl font-black mb-6 relative z-10 tracking-tight">Global Stats</h3>
                            
                            <div class="grid grid-cols-2 gap-4 relative z-10">
                                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-5 border border-white/10 hover:bg-white/20 transition-colors">
                                    <span class="text-[10px] font-black text-emerald-400 uppercase tracking-widest block mb-1">Outdoor</span>
                                    <span class="text-4xl font-black text-white block">
                                        {{ props.stats?.outdoor || 0 }}
                                    </span>
                                </div>
                                
                                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-5 border border-white/10 hover:bg-white/20 transition-colors">
                                    <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest block mb-1">Indoor</span>
                                    <span class="text-4xl font-black text-white block">
                                        {{ props.stats?.indoor || 0 }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Weather Simulator Card (Admin Only) -->
                        <div 
                            v-if="$page.props.auth.user?.is_admin && selectedCity" 
                            class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-xl rounded-[2rem] border-2 border-rose-100 dark:border-rose-900/30 shadow-xl shadow-rose-100/50 dark:shadow-none p-8"
                        >
                            <div class="flex flex-col mb-6 border-b border-slate-100 dark:border-slate-700 pb-5">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-black text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                        Weather Simulator
                                    </h3>
                                    <span class="bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-400 text-[9px] font-black px-2.5 py-1 rounded-full uppercase tracking-widest">Admin</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 font-medium">Control panel admin untuk simulasi cuaca.</p>
                            </div>

                            <form @submit.prevent="submitWeatherSimulation" class="space-y-5">
                                <div>
                                    <label class="text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest block mb-2">Status Cuaca</label>
                                    <select 
                                        v-model="weatherForm.status"
                                        class="block w-full rounded-xl border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-sm focus:border-rose-500 focus:ring-rose-500 py-3.5 px-4 font-bold text-slate-700 dark:text-slate-200"
                                    >
                                        <option value="Cerah">☀️ Cerah</option>
                                        <option value="Berawan">☁️ Berawan</option>
                                        <option value="Hujan">🌧️ Hujan</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-3 gap-3">
                                    <div class="bg-slate-50 dark:bg-slate-900/50 p-3 rounded-xl border border-slate-100 dark:border-slate-700 text-center">
                                        <label class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1.5">Suhu (°C)</label>
                                        <input 
                                            v-model="weatherForm.temperature" 
                                            type="number" 
                                            class="block w-full rounded-lg border-0 bg-white dark:bg-slate-800 text-base focus:ring-2 focus:ring-rose-500 py-2 px-2 font-black text-slate-800 dark:text-slate-200 text-center shadow-sm"
                                        >
                                    </div>
                                    <div class="bg-slate-50 dark:bg-slate-900/50 p-3 rounded-xl border border-slate-100 dark:border-slate-700 text-center">
                                        <label class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1.5">Kelembapan</label>
                                        <input 
                                            v-model="weatherForm.humidity" 
                                            type="number" 
                                            class="block w-full rounded-lg border-0 bg-white dark:bg-slate-800 text-base focus:ring-2 focus:ring-rose-500 py-2 px-2 font-black text-slate-800 dark:text-slate-200 text-center shadow-sm"
                                        >
                                    </div>
                                    <div class="bg-slate-50 dark:bg-slate-900/50 p-3 rounded-xl border border-slate-100 dark:border-slate-700 text-center">
                                        <label class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1.5">Angin</label>
                                        <input 
                                            v-model="weatherForm.wind_speed" 
                                            type="number" 
                                            class="block w-full rounded-lg border-0 bg-white dark:bg-slate-800 text-base focus:ring-2 focus:ring-rose-500 py-2 px-2 font-black text-slate-800 dark:text-slate-200 text-center shadow-sm"
                                        >
                                    </div>
                                </div>

                                <button 
                                    type="submit" 
                                    :disabled="weatherForm.processing"
                                    class="w-full inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-rose-500 to-pink-600 px-4 py-4 text-sm font-black text-white uppercase tracking-widest shadow-lg shadow-rose-200 hover:shadow-xl hover:from-rose-600 hover:to-pink-700 transition-all active:scale-[0.98] disabled:opacity-50"
                                >
                                    Push Update
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN (Weather & Destinations) -->
                    <div class="lg:col-span-8 space-y-8">
                        
                        <!-- Error Alert -->
                        <div v-if="props.guideData.is_error" class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-md border-l-8 border-rose-500 rounded-2xl p-6 shadow-xl">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-rose-100 dark:bg-rose-900/50 rounded-full flex items-center justify-center shadow-inner">
                                    <svg class="h-6 w-6 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-rose-900 dark:text-rose-400">Sistem Mendeteksi Kesalahan</h3>
                                    <p class="text-sm text-rose-700 dark:text-rose-300 mt-1 font-bold">{{ props.guideData.error_message }}</p>
                                </div>
                            </div>
                        </div>

                        <template v-else>
                            <!-- Premium Weather Status Panel -->
                            <div :class="[
                                'relative overflow-hidden rounded-[2.5rem] text-white shadow-2xl p-8 md:p-12 transition-all duration-700 group', 
                                getWeatherBg(props.guideData.weather?.status)
                            ]">
                                <!-- Glass Overlay -->
                                <div class="absolute inset-0 bg-white/5 backdrop-blur-[1px] mix-blend-overlay"></div>
                                <!-- Decorative Elements -->
                                <div class="absolute -top-32 -right-32 w-96 h-96 bg-white/20 rounded-full blur-[80px] group-hover:bg-white/30 transition-colors duration-700"></div>
                                <div class="absolute -bottom-32 -left-32 w-96 h-96 bg-black/20 rounded-full blur-[80px]"></div>
                                
                                <div class="relative z-10 flex flex-col md:flex-row md:justify-between md:items-start gap-8">
                                    <div class="space-y-6">
                                        <div class="inline-flex items-center space-x-2 bg-black/20 backdrop-blur-md rounded-full px-4 py-1.5 border border-white/10 shadow-inner">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-400 animate-pulse"></span>
                                            <span class="text-[10px] font-black uppercase tracking-widest text-white/90">LIVE WEATHER</span>
                                            <span class="text-white/40 px-1">•</span>
                                            <span class="text-[10px] font-bold text-white/80 uppercase tracking-wider">{{ new Date(props.guideData.weather?.updated_at).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'}) }} WIB</span>
                                        </div>
                                        <div>
                                            <h3 class="text-5xl md:text-7xl font-black tracking-tighter mb-4 drop-shadow-xl">
                                                {{ props.guideData.city?.name }}
                                            </h3>
                                            <div class="inline-flex items-center gap-3 bg-white/20 backdrop-blur-md px-5 py-2.5 rounded-2xl shadow-lg border border-white/20">
                                                <span class="text-3xl">
                                                    <span v-if="props.guideData.weather?.status === 'Cerah'">☀️</span>
                                                    <span v-else-if="props.guideData.weather?.status === 'Berawan'">☁️</span>
                                                    <span v-else>🌧️</span>
                                                </span>
                                                <span class="text-xl font-bold tracking-wide">
                                                    {{ props.guideData.weather?.status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Temperature & Decision -->
                                    <div class="text-left md:text-right mt-4 md:mt-0">
                                        <div class="text-8xl md:text-9xl font-black tracking-tighter drop-shadow-2xl mb-6 leading-none">
                                            {{ props.guideData.weather?.temperature }}°
                                        </div>
                                        <div class="bg-black/30 backdrop-blur-xl rounded-2xl p-5 border border-white/20 inline-block text-left shadow-2xl">
                                            <span class="text-[10px] font-black uppercase tracking-widest text-emerald-300 block mb-1">Rekomendasi Mode</span>
                                            <span class="text-xl font-black tracking-tight block">
                                                {{ props.guideData.weather?.status === 'Hujan' ? 'Indoor Activities' : (props.guideData.weather?.status === 'Cerah' ? 'Outdoor Explorer' : 'Flexible Plan') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Meteorological Details Grid -->
                                <div class="relative z-10 grid grid-cols-3 gap-4 md:gap-6 mt-12 pt-8 border-t border-white/20">
                                    <div class="bg-black/20 backdrop-blur-md rounded-2xl p-5 border border-white/10 hover:bg-black/30 transition-colors">
                                        <div class="flex items-center gap-2 mb-3">
                                            <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <span class="text-[11px] font-black uppercase tracking-widest text-white/80">Kelembapan</span>
                                        </div>
                                        <span class="text-3xl font-black">{{ props.guideData.weather?.humidity }}<span class="text-xl opacity-70 ml-1">%</span></span>
                                    </div>
                                    <div class="bg-black/20 backdrop-blur-md rounded-2xl p-5 border border-white/10 hover:bg-black/30 transition-colors">
                                        <div class="flex items-center gap-2 mb-3">
                                            <svg class="w-5 h-5 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                            <span class="text-[11px] font-black uppercase tracking-widest text-white/80">Angin</span>
                                        </div>
                                        <span class="text-3xl font-black">{{ props.guideData.weather?.wind_speed }}<span class="text-base opacity-70 ml-1 font-bold">m/s</span></span>
                                    </div>
                                    <div class="bg-black/20 backdrop-blur-md rounded-2xl p-5 border border-white/10 hover:bg-black/30 transition-colors">
                                        <div class="flex items-center gap-2 mb-3">
                                            <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                            <span class="text-[11px] font-black uppercase tracking-widest text-white/80">Suhu Fix</span>
                                        </div>
                                        <span class="text-3xl font-black block">{{ props.guideData.weather?.temperature }}<span class="text-xl opacity-70 ml-1">°C</span></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Agent Reasoning Card -->
                            <div class="bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-xl shadow-slate-200/50 dark:shadow-none p-8 flex flex-col sm:flex-row items-start sm:items-center gap-6 relative overflow-hidden group hover:shadow-2xl hover:shadow-indigo-100/50 dark:hover:shadow-indigo-900/20 transition-all duration-300">
                                <div class="absolute inset-0 bg-gradient-to-r from-indigo-50/50 dark:from-indigo-900/10 via-purple-50/30 dark:via-purple-900/10 to-transparent group-hover:opacity-70 transition-opacity"></div>
                                <div class="relative z-10 flex-shrink-0 bg-gradient-to-br from-indigo-500 to-purple-600 text-white rounded-2xl p-5 shadow-lg shadow-indigo-200 dark:shadow-none group-hover:scale-105 transition-transform">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                </div>
                                <div class="relative z-10 flex-1">
                                    <h4 class="text-[11px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest mb-2 bg-indigo-50 dark:bg-indigo-900/50 inline-block px-3 py-1 rounded-full">AI Reasoning Insight</h4>
                                    <p class="text-lg text-slate-700 dark:text-slate-300 font-bold leading-relaxed">
                                        {{ props.guideData.reason }}
                                    </p>
                                </div>
                            </div>

                            <!-- Recommendations Section -->
                            <div class="space-y-8 pt-6">
                                <div class="flex items-end justify-between pb-4">
                                    <div>
                                        <h3 class="text-3xl font-black text-slate-800 dark:text-slate-100 tracking-tight">Top Rekomendasi</h3>
                                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1.5 font-bold">Destinasi terbaik berdasarkan cuaca saat ini</p>
                                    </div>
                                    <span class="bg-indigo-600 text-white text-sm font-black px-4 py-2 rounded-xl shadow-lg shadow-indigo-200">
                                        {{ props.guideData.destinations?.length || 0 }} Matches
                                    </span>
                                </div>

                                <!-- Cards Grid -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div 
                                        v-for="dest in props.guideData.destinations" 
                                        :key="dest.id" 
                                        class="group bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-xl shadow-slate-200/50 dark:shadow-none overflow-hidden hover:shadow-2xl hover:shadow-indigo-100/60 dark:hover:shadow-indigo-900/30 transition-all duration-500 hover:-translate-y-2 flex flex-col h-full cursor-pointer"
                                    >
                                        <!-- Image Header -->
                                        <div class="relative h-64 overflow-hidden bg-slate-100">
                                            <img 
                                                :src="dest.image_url || `https://placehold.co/600x400/1e293b/ffffff.png?text=${encodeURIComponent(dest.name)}`" 
                                                alt="Destination" 
                                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-out"
                                            >
                                            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/30 to-transparent opacity-80 group-hover:opacity-95 transition-opacity duration-500"></div>
                                            
                                            <!-- Top Badges -->
                                            <div class="absolute top-5 inset-x-5 flex justify-between items-start">
                                                <span :class="[
                                                    'px-3.5 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-xl shadow-lg backdrop-blur-md text-white border border-white/20',
                                                    dest.category === 'indoor' ? 'bg-indigo-600/90' : 'bg-emerald-600/90'
                                                ]">
                                                    {{ dest.category === 'indoor' ? '🏠 Indoor' : '🌳 Outdoor' }}
                                                </span>
                                                <div class="w-10 h-10 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/30 text-white hover:bg-white hover:text-rose-500 transition-colors shadow-lg">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                                </div>
                                            </div>

                                            <!-- Bottom Info Overlay -->
                                            <div class="absolute bottom-5 left-5 right-5">
                                                <div class="inline-flex items-center gap-1.5 text-xs font-black text-white bg-black/50 backdrop-blur-md px-3 py-1.5 rounded-xl border border-white/10 mb-3 shadow-lg">
                                                    <span class="text-amber-400 text-sm">★</span> {{ dest.rating }}
                                                </div>
                                                <h4 class="text-2xl font-black text-white leading-tight drop-shadow-lg group-hover:text-indigo-200 transition-colors">
                                                    {{ dest.name }}
                                                </h4>
                                            </div>
                                        </div>

                                        <!-- Content Body -->
                                        <div class="p-7 flex flex-col justify-between flex-grow bg-white dark:bg-slate-800 relative">
                                            <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-3 leading-relaxed mb-6 font-bold">
                                                {{ dest.description }}
                                            </p>

                                            <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400 pt-5 border-t border-slate-100 dark:border-slate-700 font-black">
                                                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-900 px-3.5 py-2 rounded-xl border border-slate-100 dark:border-slate-700">
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    {{ dest.opening_hours }}
                                                </div>
                                                <div class="flex items-center gap-2 bg-indigo-50/50 dark:bg-indigo-900/30 px-3.5 py-2 rounded-xl text-indigo-700 dark:text-indigo-400 border border-indigo-100/50 dark:border-indigo-800/50">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                    {{ dest.city?.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div 
                                        v-if="!props.guideData.destinations || props.guideData.destinations.length === 0" 
                                        class="col-span-1 md:col-span-2 flex flex-col items-center justify-center bg-white dark:bg-slate-800 rounded-[2rem] border-2 border-dashed border-slate-200 dark:border-slate-700 p-16 text-center shadow-sm dark:shadow-none"
                                    >
                                        <div class="w-20 h-20 bg-slate-50 dark:bg-slate-900 rounded-full flex items-center justify-center shadow-inner mb-5">
                                            <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </div>
                                        <h3 class="text-xl font-black text-slate-800 dark:text-slate-200 mb-2">Tidak Ada Destinasi</h3>
                                        <p class="text-base text-slate-500 dark:text-slate-400 font-bold max-w-sm">Belum ada destinasi yang cocok untuk kategori cuaca saat ini di kota ini.</p>
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
    border-radius: 1.2rem;
}
.custom-leaflet-marker {
    background: none !important;
    border: none !important;
}
</style>
