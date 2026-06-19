<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({
    guideData: Object,
    currentCity: String,
});

const cityInput = ref(props.currentCity);
let map = null;
let currentMarker = null;

const searchCity = () => {
    if (cityInput.value.trim() !== '') {
        router.get(route('dashboard'), { city: cityInput.value }, { preserveState: true });
    }
};

const getTempGradient = (temp) => {
    if (!temp) return 'from-slate-700 to-slate-800';
    if (temp <= 20) return 'from-sky-700 via-blue-600 to-indigo-700';
    if (temp <= 26) return 'from-teal-600 via-emerald-500 to-emerald-700';
    if (temp <= 30) return 'from-amber-500 via-orange-500 to-amber-600';
    return 'from-orange-600 via-rose-500 to-red-600';
};

onMounted(() => {
    // Initialize Leaflet map targeting Indonesia roughly
    map = L.map('interactive-map').setView([-2.5489, 118.0149], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // If there's weather data with coords, show marker there
    if (props.guideData && !props.guideData.is_error && props.guideData.weather && props.guideData.weather.coord) {
        const lat = props.guideData.weather.coord.lat;
        const lon = props.guideData.weather.coord.lon;
        map.setView([lat, lon], 10);
        currentMarker = L.marker([lat, lon]).addTo(map).bindPopup("Titik terpilih").openPopup();
    }

    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        
        if (currentMarker) {
            map.removeLayer(currentMarker);
        }
        currentMarker = L.marker([lat, lng]).addTo(map).bindPopup("Mencari info...").openPopup();
        
        // Push coordinate search to backend
        router.get(route('dashboard'), { lat: lat, lon: lng }, { preserveState: true });
    });
});
</script>

<template>
    <Head title="AeroWeather Travel Guide" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">Weather-Adaptive Travel Guide</h2>
                    <p class="text-sm text-slate-500 mt-1">Rekomendasi destinasi cerdas yang menyesuaikan secara otomatis dengan kondisi cuaca terkini.</p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 mr-1.5 animate-pulse"></span>
                        Autonomous Agent Active
                    </span>
                </div>
            </div>
        </template>

        <div class="py-8 bg-slate-50/50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Main Grid Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- LEFT COLUMN (Map, Search, and Tools) -->
                    <div class="lg:col-span-5 space-y-6">
                        
                        <!-- Search & Controls Card -->
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-5">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">Cari & Eksplorasi</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Cari kota atau klik titik mana pun di peta di bawah ini.</p>
                            </div>
                            
                            <!-- Custom Search Input -->
                            <div class="relative rounded-xl shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input 
                                    v-model="cityInput" 
                                    @keyup.enter="searchCity"
                                    type="text" 
                                    class="block w-full rounded-xl border-slate-200 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors py-2.5" 
                                    placeholder="Masukkan kota (contoh: Jakarta, Bandung)"
                                >
                            </div>

                            <button 
                                @click="searchCity" 
                                class="w-full inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all active:scale-[0.98]"
                            >
                                Perbarui Rekomendasi
                            </button>

                            <!-- Simulation Lab Widget -->
                            <div class="pt-4 border-t border-slate-100 space-y-3">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Simulation Lab</span>
                                <div class="grid grid-cols-2 gap-3">
                                    <button 
                                        @click="router.get(route('dashboard'), { city: props.guideData.weather?.name || cityInput, force_weather: 'Rain' })" 
                                        class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-colors"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                                        </svg>
                                        Simulasi Hujan
                                    </button>
                                    <button 
                                        @click="router.get(route('dashboard'), { city: props.guideData.weather?.name || cityInput, force_weather: 'Clear' })" 
                                        class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200 transition-colors"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M14 12a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        Simulasi Cerah
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Map Card -->
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-semibold text-slate-900">Peta Interaktif</h3>
                                <span class="text-xs text-slate-400 font-medium">Klik untuk mencari</span>
                            </div>
                            <div class="relative overflow-hidden rounded-xl border border-slate-200">
                                <div id="interactive-map" class="w-full h-80 z-10"></div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN (Weather details & Destinations) -->
                    <div class="lg:col-span-7 space-y-6">
                        
                        <!-- Error State -->
                        <div v-if="props.guideData.is_error" class="bg-rose-50 border border-rose-100 rounded-2xl p-5 shadow-sm">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-rose-500 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-semibold text-rose-800">Kegagalan Sistem Cuaca</h3>
                                    <div class="mt-1 text-sm text-rose-700">
                                        <p>{{ props.guideData.error_message }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <template v-else>
                            <!-- Weather Status Panel -->
                            <div :class="[
                                'relative overflow-hidden rounded-2xl bg-gradient-to-br text-white shadow-md p-8 transition-all duration-500', 
                                getTempGradient(props.guideData.weather.main?.temp)
                            ]">
                                <!-- Subtle Background Pattern -->
                                <div class="absolute right-0 bottom-0 opacity-10 pointer-events-none transform translate-x-12 translate-y-12">
                                    <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z" />
                                    </svg>
                                </div>

                                <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-6">
                                    <!-- City Temp Main Info -->
                                    <div class="space-y-4">
                                        <div class="text-xs font-bold uppercase tracking-wider opacity-75 flex items-center space-x-2">
                                            <span>{{ props.guideData.weather.country_code || 'Global' }}</span>
                                            <span>•</span>
                                            <span>Waktu Lokal: {{ props.guideData.weather.formatted_time || '--:--' }}</span>
                                        </div>
                                        <div>
                                            <h3 class="text-4xl font-extrabold tracking-tight">
                                                {{ props.guideData.weather.name }}
                                            </h3>
                                            <p class="text-lg opacity-90 capitalize mt-1 flex items-center gap-1.5">
                                                <img 
                                                    v-if="props.guideData.weather.weather?.[0]?.icon"
                                                    :src="`https://openweathermap.org/img/wn/${props.guideData.weather.weather[0].icon}.png`" 
                                                    alt="Weather Icon"
                                                    class="w-8 h-8"
                                                />
                                                {{ props.guideData.weather.weather?.[0]?.description || 'Kondisi Tidak Diketahui' }}
                                            </p>
                                        </div>
                                        <div class="text-5xl font-black tracking-tighter">
                                            {{ props.guideData.weather.main?.temp }}°C
                                        </div>
                                    </div>

                                    <!-- Decision Summary -->
                                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-5 border border-white/10 text-right min-w-[200px] flex flex-col justify-center">
                                        <span class="text-xs opacity-75 font-semibold uppercase tracking-wider block">Rekomendasi Sistem</span>
                                        <span class="text-2xl font-bold tracking-tight block mt-1">
                                            {{ props.guideData.is_raining ? 'Indoor' : 'Outdoor' }}
                                        </span>
                                        <span class="text-xs opacity-90 mt-2 block">
                                            {{ props.guideData.is_raining ? 'Utamakan tempat terlindung' : 'Waktu terbaik untuk luar ruangan' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Meteorological details grid -->
                                <div class="grid grid-cols-3 gap-4 mt-8 pt-6 border-t border-white/10 text-center">
                                    <div class="p-2.5 bg-white/5 rounded-xl border border-white/5">
                                        <span class="text-[10px] opacity-75 font-bold uppercase tracking-wider block">Angin</span>
                                        <span class="text-base font-bold mt-0.5 block">
                                            {{ props.guideData.weather.wind?.speed || 0 }} m/s
                                        </span>
                                    </div>
                                    <div class="p-2.5 bg-white/5 rounded-xl border border-white/5">
                                        <span class="text-[10px] opacity-75 font-bold uppercase tracking-wider block">Kelembapan</span>
                                        <span class="text-base font-bold mt-0.5 block">
                                            {{ props.guideData.weather.main?.humidity || 0 }}%
                                        </span>
                                    </div>
                                    <div class="p-2.5 bg-white/5 rounded-xl border border-white/5">
                                        <span class="text-[10px] opacity-75 font-bold uppercase tracking-wider block">Awan</span>
                                        <span class="text-base font-bold mt-0.5 block">
                                            {{ props.guideData.weather.clouds?.all || 0 }}%
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Agent Reasoning Card -->
                            <div class="bg-gradient-to-r from-indigo-50/60 to-purple-50/60 border-l-4 border-indigo-500 rounded-r-2xl p-5 shadow-sm flex items-start gap-4">
                                <div class="flex-shrink-0 mt-0.5 bg-indigo-100 text-indigo-600 rounded-full p-2">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 113.586-3.586 5.004 5.004 0 011.254 3.326z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-800 uppercase tracking-wider">Analisis Agen Otonom</h4>
                                    <p class="text-sm text-indigo-900/90 font-medium mt-1 leading-relaxed">
                                        {{ props.guideData.reason }}
                                    </p>
                                </div>
                            </div>

                            <!-- Destinations Area -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-bold text-slate-900">Destinasi Rekomendasi</h3>
                                    <span class="text-xs text-slate-400 font-medium">{{ props.guideData.destinations?.length || 0 }} lokasi ditemukan</span>
                                </div>

                                <div v-if="!props.guideData.destinations || props.guideData.destinations.length === 0" class="text-slate-500 italic bg-white rounded-2xl border border-slate-100 p-8 text-center">
                                    <svg class="mx-auto h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    Maaf, belum ada destinasi terdaftar di database untuk kota ini yang cocok dengan kriteria cuaca sekarang.
                                </div>

                                <!-- Cards Grid -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div 
                                        v-for="dest in props.guideData.destinations" 
                                        :key="dest.id" 
                                        class="group bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 flex flex-col h-full"
                                    >
                                        <!-- Image Header -->
                                        <div class="relative h-48 overflow-hidden bg-slate-100">
                                            <img 
                                                :src="dest.image_url || 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?q=80&w=600'" 
                                                alt="Destination" 
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                            >
                                            <!-- Overlay gradient -->
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>
                                            <!-- Category Badge -->
                                            <span :class="[
                                                'absolute top-4 right-4 px-3 py-1 text-[10px] font-bold uppercase tracking-wider rounded-lg shadow-sm',
                                                dest.category === 'indoor' ? 'bg-indigo-600 text-white' : 'bg-emerald-600 text-white'
                                            ]">
                                                {{ dest.category }}
                                            </span>
                                            <!-- Temperature Range Indicator if present -->
                                            <span 
                                                v-if="dest.min_temp !== null || dest.max_temp !== null" 
                                                class="absolute bottom-4 left-4 text-xs font-semibold text-white/90 bg-black/40 backdrop-blur-sm px-2.5 py-1 rounded-lg"
                                            >
                                                Ideal: {{ dest.min_temp !== null ? dest.min_temp + '°C' : 'Any' }} - {{ dest.max_temp !== null ? dest.max_temp + '°C' : 'Any' }}
                                            </span>
                                        </div>

                                        <!-- Content Body -->
                                        <div class="p-6 flex flex-col justify-between flex-grow space-y-4">
                                            <div class="space-y-2">
                                                <h4 class="text-base font-bold text-slate-800 leading-snug group-hover:text-indigo-600 transition-colors">
                                                    {{ dest.name }}
                                                </h4>
                                                <p class="text-xs text-slate-500 line-clamp-3 leading-relaxed">
                                                    {{ dest.description }}
                                                </p>
                                            </div>

                                            <div class="flex items-center text-xs text-slate-400 pt-3 border-t border-slate-50 font-medium">
                                                <svg class="w-4 h-4 mr-1 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.242-4.243a8 8 0 1111.314 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                                {{ dest.city }}
                                            </div>
                                        </div>
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
/* Style adjustments for leaflet inside dashboard container */
.leaflet-container {
    border-radius: 0.75rem;
}
</style>
