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
    if (!temp) return 'bg-gradient-to-r from-gray-500 to-gray-700';
    if (temp <= 20) return 'bg-gradient-to-r from-blue-700 to-blue-400';
    if (temp <= 26) return 'bg-gradient-to-r from-teal-500 to-green-500';
    if (temp <= 30) return 'bg-gradient-to-r from-yellow-400 to-orange-500';
    return 'bg-gradient-to-r from-orange-600 to-red-600';
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
    <Head title="Weather-Adaptive Guide" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Agen Perjalanan Cerdas: Panduan Cuaca Adaptif</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Interactive Map -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Pilih Lokasi di Peta Dunia</h3>
                    <p class="text-sm text-gray-600 mb-4">Klik titik mana pun di peta untuk melihat cuaca dan mencari rekomendasi destinasi di area tersebut.</p>
                    <div id="interactive-map" class="w-full rounded-md shadow-inner border border-gray-300" style="height: 400px; z-index: 1;"></div>
                </div>

                <!-- Search Bar -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6 flex flex-col space-y-4 md:space-y-0 md:flex-row md:items-center md:space-x-4">
                    <input 
                        v-model="cityInput" 
                        @keyup.enter="searchCity"
                        type="text" 
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full md:w-1/3" 
                        placeholder="Masukkan Nama Kota (mis. Jakarta, Bali)"
                    >
                    <button @click="searchCity" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                        Cari Rekomendasi
                    </button>
                    <!-- Simulation button -->
                    <button @click="router.get(route('dashboard'), { city: props.guideData.weather?.name || cityInput, force_weather: 'Rain' })" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">
                        Simulasi Hujan
                    </button>
                    <button @click="router.get(route('dashboard'), { city: props.guideData.weather?.name || cityInput, force_weather: 'Clear' })" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded text-sm">
                        Simulasi Cerah
                    </button>
                </div>
                
                <!-- Error State -->
                <div v-if="props.guideData.is_error" class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 shadow-sm">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Sistem Otonom Gagal</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p>{{ props.guideData.error_message }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <template v-else>
                <!-- Weather Status Widget -->
                <div :class="[
                    'overflow-hidden shadow-lg sm:rounded-lg mb-6 p-8 text-white transition-colors duration-500', 
                    getTempGradient(props.guideData.weather.main.temp)
                ]">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-semibold opacity-80 uppercase tracking-widest mb-1 flex items-center space-x-2">
                                <span>{{ props.guideData.weather.country_code }}</span>
                                <span>•</span>
                                <span>Waktu Lokal: {{ props.guideData.weather.formatted_time }}</span>
                            </div>
                            <h3 class="text-3xl font-bold mb-2">
                                {{ props.guideData.weather.name }}
                            </h3>
                            <p class="text-xl capitalize">
                                {{ props.guideData.weather.weather[0].description }}
                            </p>
                            <p class="text-4xl font-bold mt-4">
                                {{ props.guideData.weather.main.temp }}°C
                            </p>
                        </div>
                        <div class="text-3xl font-bold uppercase tracking-wider text-right">
                            <span v-if="props.guideData.is_raining">Hujan<br><span class="text-lg opacity-75">Prioritas Indoor</span></span>
                            <span v-else>Cerah<br><span class="text-lg opacity-75">Prioritas Outdoor</span></span>
                        </div>
                    </div>
                </div>

                <!-- Agent Reasoning -->
                <div class="bg-indigo-50 border-l-4 border-indigo-400 p-4 mb-6 shadow-sm">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-indigo-700 font-medium">
                                <strong>Rekomendasi Cerdas:</strong> {{ props.guideData.reason }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Destinations List -->
                <h3 class="text-2xl font-bold text-gray-800 mb-4">Destinasi Rekomendasi</h3>
                
                <div v-if="props.guideData.destinations.length === 0" class="text-gray-500 italic bg-gray-100 p-4 rounded-lg">
                    Maaf, belum ada destinasi yang tersimpan di database untuk kota ini.
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="dest in props.guideData.destinations" :key="dest.id" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                        <img :src="dest.image_url || 'https://via.placeholder.com/600x400?text=No+Image'" alt="Destination" class="w-full h-48 object-cover">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="text-xl font-bold text-gray-900">{{ dest.name }}</h4>
                                <span :class="[
                                    'px-2 py-1 text-xs font-semibold rounded-full',
                                    dest.category === 'indoor' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'
                                ]">
                                    {{ dest.category }}
                                </span>
                            </div>
                            <p class="text-gray-600 text-sm mb-4">{{ dest.description }}</p>
                            <div class="flex items-center text-sm text-gray-500">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.242-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                {{ dest.city }}
                            </div>
                        </div>
                    </div>
                </div>
                </template>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
