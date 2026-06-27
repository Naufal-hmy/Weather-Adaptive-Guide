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
    if (!searchQuery.value) return props.cities;
    const q = searchQuery.value.toLowerCase();
    return props.cities.filter(c =>
        c.name.toLowerCase().includes(q) || c.country.toLowerCase().includes(q)
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
        if (!searchQuery.value && props.guideData?.city?.name) {
            searchQuery.value = props.guideData.city.name;
        }
    }, 200);
};

const onEnter = () => {
    if (filteredCities.value.length > 0) selectCity(filteredCities.value[0]);
};

const initAutocomplete = () => {
    if (!searchInput.value || !window.google?.maps) return;
    const ac = new window.google.maps.places.Autocomplete(searchInput.value, {
        types: ['(regions)'], fields: ['geometry', 'name']
    });
    ac.addListener('place_changed', () => {
        const place = ac.getPlace();
        if (place.geometry?.location) {
            router.get(route('dashboard'), {
                lat: place.geometry.location.lat(),
                lng: place.geometry.location.lng(),
                q: place.name
            }, { preserveState: true });
        }
    });
};

const loadGooglePlacesAutocomplete = () => {
    if (!props.googleMapsApiKey) return;
    if (window.google?.maps?.places) { initAutocomplete(); return; }
    const s = document.createElement('script');
    s.src = `https://maps.googleapis.com/maps/api/js?key=${props.googleMapsApiKey}&libraries=places`;
    s.async = true; s.defer = true; s.onload = initAutocomplete;
    document.head.appendChild(s);
};

watch(() => props.guideData?.city?.name, (n) => { if (n) searchQuery.value = n; }, { immediate: true });
watch(() => props.selectedCityId, (n) => {
    if (n) {
        selectedCity.value = n;
        const c = props.cities?.find(x => x.id === n);
        if (c) searchQuery.value = c.name;
    }
});

const cityCoords = {
    'Batu':    [-7.8712, 112.5269],
    'Jakarta': [-6.2088, 106.8456],
    'Malang':  [-7.9839, 112.6214],
    'Bandung': [-6.9175, 107.6191],
    'Bogor':   [-6.5971, 106.8060],
    'Bali': [-8.4095, 115.1889],
    'Yogyakarta': [-7.7956, 110.3695]
};

let map = null;
let mapMarkers = [];
const userLocation = ref(null);

const weatherForm = useForm({
    status:      props.guideData?.weather?.status      || 'Cerah',
    temperature: props.guideData?.weather?.temperature || 28,
    humidity:    props.guideData?.weather?.humidity    || 70,
    wind_speed:  props.guideData?.weather?.wind_speed  || 10,
});

const changeCity = () => {
    router.get(route('dashboard'), { city_id: selectedCity.value }, { preserveState: true });
};

const submitWeatherSimulation = () => {
    weatherForm.post(route('admin.weather.update', selectedCity.value));
};

const resetWeatherSimulation = () => {
    router.post(route('admin.weather.reset', selectedCity.value), {}, {
        preserveScroll: true,
    });
};

const focusMap = (dest) => {
    if (map && dest.lat && dest.lng) {
        map.flyTo([dest.lat, dest.lng], 16, {
            animate: true,
            duration: 1.5
        });
        
        mapMarkers.forEach(marker => {
            const pos = marker.getLatLng();
            if (Math.abs(pos.lat - dest.lat) < 0.0001 && Math.abs(pos.lng - dest.lng) < 0.0001) {
                marker.openPopup();
            }
        });
        
        document.getElementById('interactive-map').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
};

const weatherIcon = (s) => s === 'Hujan' ? '🌧️' : s === 'Berawan' ? '☁️' : '☀️';
const weatherGradient = (s) => {
    if (s === 'Hujan')   return 'from-blue-700 via-sky-600 to-indigo-700';
    if (s === 'Berawan') return 'from-slate-600 via-zinc-500 to-slate-700';
    return 'from-amber-500 via-orange-400 to-yellow-400';
};
const weatherGlow = (s) => {
    if (s === 'Hujan')   return 'shadow-blue-500/30';
    if (s === 'Berawan') return 'shadow-slate-400/30';
    return 'shadow-amber-400/40';
};

const createIcon = (color, label) => {
    const hex = color === 'red' ? '#ef4444' : color === 'green' ? '#10b981' : color === 'indigo' ? '#6366f1' : '#3b82f6';
    return L.divIcon({
        html: `<div style="background:${hex};width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:11px;box-shadow:0 2px 8px ${hex}88;border:2px solid rgba(255,255,255,0.7)">${label}</div>`,
        className: '', iconSize: [32, 32], iconAnchor: [16, 16], popupAnchor: [0, -18]
    });
};

const updateMap = () => {
    if (!map) return;
    mapMarkers.forEach(m => map.removeLayer(m));
    mapMarkers = [];
    const cityName = props.guideData?.city?.name;
    const coords = cityCoords[cityName] || [-7.8712, 112.5269];
    const bounds = [];
    const weatherStatus = props.guideData?.weather?.status || 'Cerah';

    const uPoint = userLocation.value
        ? [userLocation.value.lat, userLocation.value.lng]
        : [coords[0] + 0.008, coords[1] - 0.008];
    bounds.push(uPoint);
    mapMarkers.push(
        L.marker(uPoint, { icon: createIcon('indigo', 'U') })
            .addTo(map).bindPopup('<b>Posisi Anda</b>')
    );

    bounds.push(coords);
    mapMarkers.push(
        L.marker(coords, { icon: createIcon(weatherStatus === 'Hujan' ? 'red' : 'blue', 'S') })
            .addTo(map).bindPopup(`<b>${cityName}</b><br>Cuaca: ${weatherStatus}`)
    );

    props.guideData?.destinations?.forEach((d, i) => {
        if (!d.lat || !d.lng) return;
        const pt = [d.lat, d.lng];
        bounds.push(pt);
        mapMarkers.push(
            L.marker(pt, { icon: createIcon(d.category === 'indoor' ? 'green' : 'blue', (i+1).toString()) })
                .addTo(map).bindPopup(`<b>${d.name}</b><br>⭐ ${d.rating} — ${d.category}`)
        );
    });

    if (bounds.length > 1) map.fitBounds(bounds, { padding: [40, 40] });
    else map.setView(coords, 13);
};

onMounted(() => {
    const cityName = props.guideData?.city?.name;
    const coords = cityCoords[cityName] || [-7.8712, 112.5269];
    map = L.map('interactive-map').setView(coords, 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    updateMap();
    loadGooglePlacesAutocomplete();
    navigator.geolocation?.getCurrentPosition((p) => {
        userLocation.value = { lat: p.coords.latitude, lng: p.coords.longitude };
        updateMap();
    });
});

watch(() => props.guideData?.city?.name, () => {
    updateMap();
    if (props.guideData?.weather) {
        weatherForm.status      = props.guideData.weather.status;
        weatherForm.temperature = props.guideData.weather.temperature;
        weatherForm.humidity    = props.guideData.weather.humidity;
        weatherForm.wind_speed  = props.guideData.weather.wind_speed;
    }
});
</script>

<template>
    <Head title="Smart Travel Weather Guide" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Smart Travel Weather Guide</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Rekomendasi destinasi otomatis berbasis cuaca real-time.</p>
                </div>
                <span class="inline-flex items-center self-start sm:self-auto rounded-full bg-indigo-50 dark:bg-indigo-900/30 px-3 py-1 text-xs font-semibold text-indigo-700 dark:text-indigo-300 ring-1 ring-inset ring-indigo-700/10 dark:ring-indigo-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 mr-2 animate-pulse"></span>
                    Weather Agent Active
                </span>
            </div>
        </template>

        <div class="py-8 bg-slate-100 dark:bg-slate-950 min-h-screen transition-colors duration-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- TOP ROW: City Selector + Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    <!-- City Selector -->
                    <div class="md:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-6">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 mb-1">Pilih Kota Tujuan</h3>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mb-4">Sistem menyajikan cuaca hari ini & rekomendasi destinasi secara otomatis.</p>
                        <div class="relative">
                            <input
                                ref="searchInput"
                                type="text"
                                v-model="searchQuery"
                                @focus="showSuggestions = !props.googleMapsApiKey"
                                @blur="handleBlur"
                                @keyup.enter="onEnter"
                                placeholder="Cari kota tujuan..."
                                class="block w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500 text-sm focus:border-indigo-500 focus:ring-indigo-500 py-3 pl-4 pr-10 shadow-sm"
                            />
                            <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                            <div v-if="showSuggestions && filteredCities.length > 0"
                                class="absolute z-50 mt-1 w-full bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-lg max-h-48 overflow-y-auto py-1">
                                <button v-for="city in filteredCities" :key="city.id" type="button"
                                    @mousedown="selectCity(city)"
                                    class="w-full text-left px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-600 flex justify-between items-center">
                                    <span>{{ city.name }}, {{ city.country }}</span>
                                    <span v-if="city.id === props.selectedCityId" class="text-[10px] font-bold text-indigo-500 bg-indigo-50 dark:bg-indigo-900/40 px-1.5 py-0.5 rounded">Aktif</span>
                                </button>
                            </div>
                            <div v-else-if="showSuggestions && filteredCities.length === 0"
                                class="absolute z-50 mt-1 w-full bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-lg py-3 px-4 text-xs text-slate-400 italic">
                                Kota tidak ditemukan
                            </div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-6 flex flex-col justify-between">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 mb-4">Statistik Destinasi</h3>
                        <div class="grid grid-cols-2 gap-3 flex-grow">
                            <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-3 border border-emerald-100 dark:border-emerald-800/40 flex flex-col items-center justify-center">
                                <span class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">Outdoor</span>
                                <span class="text-3xl font-black text-emerald-700 dark:text-emerald-400 leading-tight">{{ props.stats?.outdoor || 0 }}</span>
                                <span class="text-[10px] text-emerald-500 dark:text-emerald-500 mt-0.5">destinasi</span>
                            </div>
                            <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-3 border border-indigo-100 dark:border-indigo-800/40 flex flex-col items-center justify-center">
                                <span class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wide">Indoor</span>
                                <span class="text-3xl font-black text-indigo-700 dark:text-indigo-400 leading-tight">{{ props.stats?.indoor || 0 }}</span>
                                <span class="text-[10px] text-indigo-500 dark:text-indigo-500 mt-0.5">destinasi</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ERROR STATE -->
                <div v-if="props.guideData?.is_error"
                    class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800/40 rounded-2xl p-6 flex items-start gap-4">
                    <svg class="h-6 w-6 text-rose-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-bold text-rose-800">Kesalahan Sistem</h3>
                        <p class="text-xs text-rose-600 mt-1">{{ props.guideData.error_message }}</p>
                    </div>
                </div>

                <template v-else>

                    <!-- WEATHER HERO CARD (reflective glassmorphism) -->
                    <div :class="['relative overflow-hidden rounded-3xl bg-gradient-to-br text-white shadow-2xl transition-all duration-500', weatherGradient(props.guideData.weather?.status), weatherGlow(props.guideData.weather?.status)]">

                        <!-- Decorative blobs for depth/reflection -->
                        <div class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
                        <div class="absolute -bottom-20 -left-10 w-72 h-72 rounded-full bg-white/5 blur-3xl pointer-events-none"></div>

                        <div class="relative z-10 p-8">
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-8">

                                <!-- Left: City + Temp -->
                                <div class="space-y-3">
                                    <div class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest bg-white/10 backdrop-blur-sm border border-white/20 rounded-full px-3 py-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                        Weather Live · {{ props.guideData.city?.name }}
                                    </div>
                                    <div class="flex items-end gap-4">
                                        <span class="text-7xl font-black tracking-tighter leading-none drop-shadow-md">
                                            {{ props.guideData.weather?.temperature }}°
                                        </span>
                                        <div class="pb-1 space-y-1">
                                            <div class="text-2xl font-bold flex items-center gap-2">
                                                {{ weatherIcon(props.guideData.weather?.status) }}
                                                {{ props.guideData.weather?.status }}
                                            </div>
                                            <div class="text-xs opacity-75 font-medium">
                                                Diperbarui: {{ new Date(props.guideData.weather?.updated_at).toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'}) }} WIB
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right: Mode widget + meteo stats -->
                                <div class="flex flex-col sm:flex-row lg:flex-col gap-4 lg:items-end">
                                    <!-- Mode badge -->
                                    <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl px-5 py-4 shadow-inner text-center min-w-[160px]">
                                        <span class="text-[10px] font-bold uppercase tracking-widest opacity-75 block">Mode Aktif</span>
                                        <span class="text-xl font-black block mt-1">
                                            {{ props.guideData.weather?.status === 'Hujan' ? 'Indoor Only' : props.guideData.weather?.status === 'Cerah' ? 'Outdoor Only' : 'Semua' }}
                                        </span>
                                        <span class="text-[10px] opacity-80 mt-1 block">
                                            {{ props.guideData.weather?.status === 'Hujan' ? 'Hindari luar ruangan' : props.guideData.weather?.status === 'Cerah' ? 'Sempurna outdoor' : 'Bebas memilih' }}
                                        </span>
                                    </div>

                                    <!-- Meteo row -->
                                    <div class="flex gap-3">
                                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-4 py-3 text-center min-w-[72px]">
                                            <span class="text-[9px] opacity-70 font-bold uppercase block">Angin</span>
                                            <span class="text-sm font-bold block mt-0.5">{{ props.guideData.weather?.wind_speed }}<span class="text-[9px] font-normal opacity-80"> m/s</span></span>
                                        </div>
                                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-4 py-3 text-center min-w-[72px]">
                                            <span class="text-[9px] opacity-70 font-bold uppercase block">Lembap</span>
                                            <span class="text-sm font-bold block mt-0.5">{{ props.guideData.weather?.humidity }}<span class="text-[9px] font-normal opacity-80">%</span></span>
                                        </div>
                                        <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-4 py-3 text-center min-w-[72px]">
                                            <span class="text-[9px] opacity-70 font-bold uppercase block">Suhu</span>
                                            <span class="text-sm font-bold block mt-0.5">{{ props.guideData.weather?.temperature }}<span class="text-[9px] font-normal opacity-80">°C</span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AGENT REASONING -->
                    <div class="bg-white dark:bg-slate-900 border-l-4 border-indigo-500 rounded-2xl shadow-sm p-5 flex items-start gap-4">
                        <div class="flex-shrink-0 w-9 h-9 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Analisis Agen Cerdas</h4>
                            <p class="text-sm text-slate-700 dark:text-slate-300 mt-1 leading-relaxed">{{ props.guideData.reason }}</p>
                        </div>
                    </div>

                    <!-- MAIN CONTENT: Destinations + Sidebar -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                        <!-- Destinations Grid -->
                        <div class="lg:col-span-8 space-y-5">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">Rekomendasi Destinasi</h3>
                                <span class="text-xs text-slate-400 dark:text-slate-500 font-medium bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-full">{{ props.guideData.destinations?.length || 0 }} tempat</span>
                            </div>

                            <div v-if="!props.guideData.destinations?.length"
                                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 p-10 text-center text-sm text-slate-400 dark:text-slate-500 italic">
                                Belum ada destinasi untuk kota ini dengan kondisi cuaca sekarang.
                            </div>

                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div v-for="dest in props.guideData.destinations" :key="dest.id"
                                    @click="focusMap(dest)"
                                    class="cursor-pointer group bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 flex flex-col">

                                    <!-- Image -->
                                    <div class="relative h-44 overflow-hidden bg-slate-100 dark:bg-slate-800 flex-shrink-0">
                                        <img :src="dest.image_url || 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?q=80&w=600'"
                                            :alt="dest.name"
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                                        <!-- Reflection shimmer overlay -->
                                        <div class="absolute inset-0 bg-gradient-to-br from-white/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                        <span :class="['absolute top-3 right-3 px-2 py-0.5 text-[9px] font-bold uppercase rounded-lg text-white shadow', dest.category === 'indoor' ? 'bg-indigo-600/90 backdrop-blur-sm' : 'bg-emerald-600/90 backdrop-blur-sm']">
                                            {{ dest.category === 'indoor' ? '🏠 Indoor' : '🌳 Outdoor' }}
                                        </span>
                                        <span class="absolute bottom-3 left-3 text-xs font-bold text-white bg-black/40 backdrop-blur-sm px-2 py-0.5 rounded-lg">
                                            ⭐ {{ dest.rating }}
                                        </span>
                                        <div v-if="dest.suitability_score"
                                            class="absolute bottom-3 right-3 text-[10px] font-black text-white bg-indigo-600/80 backdrop-blur-sm px-2 py-0.5 rounded-lg">
                                            {{ dest.suitability_score }}% match
                                        </div>
                                    </div>

                                    <!-- Body -->
                                    <div class="p-4 flex flex-col flex-grow gap-3">
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200 leading-snug group-hover:text-indigo-600 transition-colors">{{ dest.name }}</h4>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-relaxed">{{ dest.description }}</p>
                                        </div>
                                        <div class="mt-auto flex items-center justify-between text-[11px] text-slate-400 dark:text-slate-500 font-medium pt-3 border-t border-slate-50 dark:border-slate-800">
                                            <span class="flex items-center gap-1">🕒 {{ dest.opening_hours }}</span>
                                            <span class="flex items-center gap-1">📍 {{ dest.city?.name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar: Map + Admin Simulator -->
                        <div class="lg:col-span-4 space-y-6">

                            <!-- Interactive Map -->
                            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden">
                                <div class="px-5 py-4 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between">
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Peta Lokasi</h3>
                                    <span class="flex items-center gap-1.5 text-[10px] font-semibold text-emerald-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Live
                                    </span>
                                </div>
                                <div id="interactive-map" class="w-full h-64"></div>
                                <div class="px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border-t border-slate-100 dark:border-slate-700 flex gap-4 text-[10px] text-slate-500 dark:text-slate-400 font-medium flex-wrap">
                                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-indigo-500 inline-block"></span> Posisi Anda</span>
                                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span> Pusat Kota</span>
                                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span> Rekomendasi</span>
                                </div>
                            </div>

                            <!-- Admin Weather Simulator -->
                            <div v-if="$page.props.auth.user?.is_admin && selectedCity"
                                class="bg-white dark:bg-slate-900 rounded-2xl border border-rose-100 dark:border-rose-800/30 shadow-sm overflow-hidden">
                                <div class="px-5 py-4 border-b border-rose-50 dark:border-rose-800/20 flex items-center justify-between">
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Weather Simulator</h3>
                                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Kota: {{ props.guideData?.city?.name }}</p>
                                    </div>
                                    <span class="bg-rose-100 dark:bg-rose-900/20 text-rose-700 dark:text-rose-400 text-[9px] font-black px-2 py-0.5 rounded-md uppercase tracking-wider">Admin</span>
                                </div>
                                <form @submit.prevent="submitWeatherSimulation" class="p-5 space-y-4">
                                    <div>
                                        <label class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-1">Status Cuaca</label>
                                        <select v-model="weatherForm.status"
                                            class="block w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-rose-400 focus:ring-rose-400 py-2.5 px-3">
                                            <option value="Cerah">☀️ Cerah</option>
                                            <option value="Berawan">☁️ Berawan</option>
                                            <option value="Hujan">🌧️ Hujan</option>
                                        </select>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase block mb-1">Suhu °C</label>
                                            <input v-model="weatherForm.temperature" type="number"
                                                class="block w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-rose-400 focus:ring-rose-400 py-2 px-2.5">
                                        </div>
                                        <div>
                                            <label class="text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase block mb-1">Lembap %</label>
                                            <input v-model="weatherForm.humidity" type="number"
                                                class="block w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-rose-400 focus:ring-rose-400 py-2 px-2.5">
                                        </div>
                                        <div>
                                            <label class="text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase block mb-1">Angin m/s</label>
                                            <input v-model="weatherForm.wind_speed" type="number"
                                                class="block w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-rose-400 focus:ring-rose-400 py-2 px-2.5">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button type="submit" :disabled="weatherForm.processing"
                                            class="w-full rounded-xl bg-rose-600 hover:bg-rose-500 active:scale-[0.98] disabled:opacity-50 text-white text-xs font-bold py-2.5 transition-all shadow-sm shadow-rose-200 dark:shadow-rose-900/20">
                                            Simulasikan
                                        </button>
                                        <button type="button" @click="resetWeatherSimulation"
                                            class="w-full rounded-xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 active:scale-[0.98] text-slate-700 dark:text-slate-300 text-xs font-bold py-2.5 transition-all">
                                            Reset Real-time
                                        </button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>

                    <!-- RECOMMENDATION LOGS -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">Riwayat Rekomendasi</h3>
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-6">
                            <div v-if="!props.guideData.logs?.length" class="text-xs text-slate-400 dark:text-slate-500 italic text-center py-4">
                                Belum ada riwayat rekomendasi untuk kota ini.
                            </div>
                            <ul v-else class="space-y-4">
                                <li v-for="(log, idx) in props.guideData.logs" :key="log.id" class="flex gap-4 items-start">
                                    <div :class="['w-9 h-9 flex-shrink-0 rounded-xl flex items-center justify-center text-base ring-4', log.weather_status === 'Hujan' ? 'bg-blue-50 ring-blue-50 dark:bg-blue-900/20 dark:ring-blue-900/20' : log.weather_status === 'Cerah' ? 'bg-amber-50 ring-amber-50 dark:bg-amber-900/20 dark:ring-amber-900/20' : 'bg-slate-100 ring-slate-50 dark:bg-slate-800 dark:ring-slate-800']">
                                        {{ weatherIcon(log.weather_status) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2 flex-wrap">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ log.weather_status }}</span>
                                            <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                                                {{ new Date(log.created_at).toLocaleString('id-ID', {day:'numeric',month:'short',hour:'2-digit',minute:'2-digit'}) }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed italic">"{{ log.reason }}"</p>
                                    </div>
                                    <div v-if="idx !== props.guideData.logs.length - 1" class="hidden"></div>
                                </li>
                            </ul>
                        </div>
                    </div>

                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
#interactive-map { height: 256px; }
.leaflet-container { border-radius: 0; }
</style>
