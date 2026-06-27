<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    cities: Array,
    googleMapsApiKey: String
});

const selectedCityId = ref(props.cities[0]?.id || null);
const forceWeather   = ref('Cerah');
const userLocation   = ref(null);
const weather        = ref(null);
const starting       = ref(null);
const recommendations = ref([]);
const reasoning      = ref('');
const isLoading      = ref(false);
const trackingStatus = ref('Mendeteksi lokasi...');
const isGoogleMapsLoaded = ref(false);

let mapInstance = null;
let mapMarkers  = [];

// ── helpers ──────────────────────────────────────────────
const weatherIcon = (s) => s === 'Hujan' ? '🌧️' : s === 'Berawan' ? '☁️' : '☀️';
const weatherGradient = (s) => {
    if (s === 'Hujan')   return 'from-blue-700 via-sky-600 to-indigo-700';
    if (s === 'Berawan') return 'from-slate-600 via-zinc-500 to-slate-700';
    return 'from-amber-500 via-orange-400 to-yellow-400';
};
const selectedCityName = computed(() =>
    props.cities?.find(c => c.id === selectedCityId.value)?.name || ''
);

// ── Google Maps loader ────────────────────────────────────
const loadGoogleMaps = () => {
    if (!props.googleMapsApiKey) { isGoogleMapsLoaded.value = false; return; }
    if (window.google?.maps) { isGoogleMapsLoaded.value = true; return; }
    const s = document.createElement('script');
    s.src = `https://maps.googleapis.com/maps/api/js?key=${props.googleMapsApiKey}&libraries=places`;
    s.async = true; s.defer = true;
    s.onload  = () => { isGoogleMapsLoaded.value = true;  initMap(); };
    s.onerror = () => { isGoogleMapsLoaded.value = false; initMap(); };
    document.head.appendChild(s);
};

// ── fetch ─────────────────────────────────────────────────
const fetchRecommendations = async () => {
    isLoading.value = true;
    try {
        const params = { city_id: selectedCityId.value, force_weather: forceWeather.value };
        if (userLocation.value) {
            params.user_lat = userLocation.value.lat;
            params.user_lng = userLocation.value.lng;
        }
        const { data } = await axios.get(route('api.nearby-recommendations'), { params });
        weather.value         = data.weather;
        starting.value        = data.starting;
        recommendations.value = data.recommendations;
        reasoning.value       = data.reasoning;
        if (!userLocation.value) userLocation.value = data.user;
        initMap();
    } catch (e) {
        console.error('SmartMap fetch error:', e);
    } finally {
        isLoading.value = false;
    }
};

// ── map init ──────────────────────────────────────────────
const initMap = () => {
    const lat = starting.value?.lat || -7.8712;
    const lng = starting.value?.lng || 112.5269;
    const el  = document.getElementById('map-view-element');
    if (!el) return;

    if (mapInstance && typeof mapInstance.remove === 'function') {
        mapInstance.remove(); mapInstance = null;
    }
    mapMarkers = [];
    el.innerHTML = '';

    isGoogleMapsLoaded.value ? renderGoogleMap(lat, lng) : renderLeafletMap([lat, lng]);
};

// ── Leaflet ───────────────────────────────────────────────
const renderLeafletMap = (center) => {
    const el = document.getElementById('map-view-element');
    mapInstance = L.map(el).setView(center, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(mapInstance);

    const icon = (hex, label) => L.divIcon({
        html: `<div style="background:${hex};width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:12px;box-shadow:0 3px 10px ${hex}99;border:2.5px solid rgba(255,255,255,0.8)">${label}</div>`,
        className: '', iconSize: [34, 34], iconAnchor: [17, 17], popupAnchor: [0, -20]
    });
    const colorHex = (c) => c === 'red' ? '#ef4444' : c === 'green' ? '#10b981' : c === 'indigo' ? '#6366f1' : '#3b82f6';

    const bounds = [];

    if (userLocation.value) {
        const p = [userLocation.value.lat, userLocation.value.lng];
        bounds.push(p);
        L.marker(p, { icon: icon('#6366f1', 'U') }).addTo(mapInstance)
            .bindPopup('<b>📍 Posisi Anda</b>').openPopup();
    }

    if (starting.value) {
        const p = [starting.value.lat, starting.value.lng];
        bounds.push(p);
        const status = starting.value.recommended ? '✅ Aman dikunjungi' : '⚠️ Tidak disarankan (hujan)';
        L.marker(p, { icon: icon(colorHex(starting.value.marker_color), 'S') }).addTo(mapInstance)
            .bindPopup(`<div style="min-width:180px"><b>Destinasi Awal</b><br><strong>${starting.value.name}</strong><br><small>${starting.value.address}</small><br><span style="font-size:11px;color:${starting.value.recommended?'#4338ca':'#b91c1c'}">${status}</span></div>`);
    }

    recommendations.value.forEach((rec, i) => {
        const p = [rec.lat, rec.lng];
        bounds.push(p);
        L.marker(p, { icon: icon(colorHex(rec.marker_color), (i + 1).toString()) }).addTo(mapInstance)
            .bindPopup(`<div style="min-width:180px"><b>Alternatif #${i+1}</b><br><strong>${rec.name}</strong><br><small>${rec.address}</small><br>⭐ ${rec.rating} · ${rec.distance} km · <b style="color:#059669">${rec.suitability_score}%</b></div>`);
    });

    if (bounds.length > 1) mapInstance.fitBounds(bounds, { padding: [50, 50] });
};

// ── Google Maps ───────────────────────────────────────────
const renderGoogleMap = (lat, lng) => {
    const el   = document.getElementById('map-view-element');
    const gMap = new google.maps.Map(el, { center: { lat, lng }, zoom: 13, mapId: 'DEMO_MAP_ID' });
    const bounds = new google.maps.LatLngBounds();

    const mkMarker = (pos, color, label, infoHtml) => {
        const marker = new google.maps.Marker({
            position: pos, map: gMap,
            icon: { path: google.maps.SymbolPath.CIRCLE, scale: 11, fillColor: color, fillOpacity: 0.9, strokeWeight: 2.5, strokeColor: '#fff' },
            label: { text: label, color: '#fff', fontWeight: '800', fontSize: '11px' }
        });
        const info = new google.maps.InfoWindow({ content: infoHtml });
        marker.addListener('click', () => info.open(gMap, marker));
        bounds.extend(pos);
    };

    if (userLocation.value) {
        mkMarker({ lat: userLocation.value.lat, lng: userLocation.value.lng }, '#6366f1', 'U', '<b>📍 Posisi Anda</b>');
    }
    if (starting.value) {
        const color = starting.value.marker_color === 'red' ? '#ef4444' : '#3b82f6';
        mkMarker({ lat: starting.value.lat, lng: starting.value.lng }, color, 'S',
            `<div style="min-width:180px"><b>Destinasi Awal</b><br><strong>${starting.value.name}</strong><br><small>${starting.value.address}</small><br><span style="color:${starting.value.recommended?'#4338ca':'#b91c1c'};font-size:11px">${starting.value.recommended?'✅ Aman':'⚠️ Tidak disarankan'}</span></div>`);
    }
    recommendations.value.forEach((rec, i) => {
        mkMarker({ lat: rec.lat, lng: rec.lng }, '#10b981', (i+1).toString(),
            `<div style="min-width:180px"><b>Alternatif #${i+1}</b><br><strong>${rec.name}</strong><br><small>${rec.address}</small><br>⭐ ${rec.rating} · ${rec.distance} km · <b style="color:#059669">${rec.suitability_score}%</b></div>`);
    });

    gMap.fitBounds(bounds);
};

// ── geolocation ───────────────────────────────────────────
const detectLocation = () => {
    trackingStatus.value = 'Meminta izin GPS...';
    if (!navigator.geolocation) {
        trackingStatus.value = 'GPS tidak didukung';
        fetchRecommendations();
        return;
    }
    navigator.geolocation.getCurrentPosition(
        (p) => {
            userLocation.value = { lat: p.coords.latitude, lng: p.coords.longitude };
            trackingStatus.value = 'GPS Aktif ✓';
            fetchRecommendations();
        },
        () => {
            trackingStatus.value = 'GPS Ditolak – pakai koordinat kota';
            fetchRecommendations();
        },
        { enableHighAccuracy: true, timeout: 5000 }
    );
};

onMounted(() => { loadGoogleMaps(); detectLocation(); });
watch([selectedCityId, forceWeather], fetchRecommendations);
</script>

<template>
    <Head title="Smart Recommendation Map" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Smart Recommendation Map</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Perutean cerdas berbasis cuaca menggunakan Google Places & OpenWeather.</p>
                </div>
                <span class="inline-flex items-center self-start sm:self-auto rounded-full bg-emerald-50 dark:bg-emerald-900/20 px-3 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-400 ring-1 ring-inset ring-emerald-600/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                    {{ trackingStatus }}
                </span>
            </div>
        </template>

        <div class="py-8 bg-slate-50 dark:bg-slate-950 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- CONTROL ROW -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    <!-- City Selector -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-5">
                        <label class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-2">Kota Tujuan Awal</label>
                        <select v-model="selectedCityId"
                            class="block w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5 px-4 shadow-sm">
                            <option v-for="city in props.cities" :key="city.id" :value="city.id">
                                {{ city.name }}, {{ city.country }}
                            </option>
                        </select>
                    </div>

                    <!-- Weather Simulator -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-5">
                        <label class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-2">Simulasi Cuaca</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" @click="forceWeather = 'Cerah'" :class="[
                                'py-2.5 rounded-xl text-xs font-bold border transition-all flex flex-col items-center gap-1',
                                forceWeather === 'Cerah'
                                    ? 'bg-amber-500 border-amber-600 text-white shadow-md shadow-amber-200'
                                    : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:border-amber-200'
                            ]"><span class="text-lg">☀️</span><span>Cerah</span></button>

                            <button type="button" @click="forceWeather = 'Berawan'" :class="[
                                'py-2.5 rounded-xl text-xs font-bold border transition-all flex flex-col items-center gap-1',
                                forceWeather === 'Berawan'
                                    ? 'bg-slate-500 border-slate-600 text-white shadow-md shadow-slate-200'
                                    : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'
                            ]"><span class="text-lg">☁️</span><span>Berawan</span></button>

                            <button type="button" @click="forceWeather = 'Hujan'" :class="[
                                'py-2.5 rounded-xl text-xs font-bold border transition-all flex flex-col items-center gap-1',
                                forceWeather === 'Hujan'
                                    ? 'bg-blue-600 border-blue-700 text-white shadow-md shadow-blue-200'
                                    : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-200'
                            ]"><span class="text-lg">🌧️</span><span>Hujan</span></button>
                        </div>
                    </div>
                </div>

                <!-- WEATHER MINI BANNER (appears after load) -->
                <Transition enter-from-class="opacity-0 -translate-y-2" enter-active-class="transition duration-300" leave-to-class="opacity-0">
                    <div v-if="weather" :class="['relative overflow-hidden rounded-2xl bg-gradient-to-r text-white px-6 py-4 flex items-center justify-between shadow-lg', weatherGradient(weather.status)]">
                        <!-- glow blob -->
                        <div class="absolute -right-8 -top-8 w-40 h-40 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
                        <div class="flex items-center gap-4">
                            <span class="text-4xl drop-shadow">{{ weatherIcon(weather.status) }}</span>
                            <div>
                                <div class="text-xs font-bold uppercase tracking-widest opacity-80">Cuaca Sekarang · {{ selectedCityName }}</div>
                                <div class="text-2xl font-black mt-0.5">{{ weather.temperature }}° — {{ weather.status }}</div>
                            </div>
                        </div>
                        <div class="hidden sm:flex gap-4 text-center">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-2 border border-white/15">
                                <div class="text-[9px] font-bold uppercase opacity-70">Angin</div>
                                <div class="text-sm font-bold">{{ weather.wind_speed }} m/s</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-2 border border-white/15">
                                <div class="text-[9px] font-bold uppercase opacity-70">Lembap</div>
                                <div class="text-sm font-bold">{{ weather.humidity }}%</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-2 border border-white/15">
                                <div class="text-[9px] font-bold uppercase opacity-70">Mode</div>
                                <div class="text-sm font-bold">{{ weather.status === 'Hujan' ? 'Indoor' : weather.status === 'Cerah' ? 'Outdoor' : 'Semua' }}</div>
                            </div>
                        </div>
                    </div>
                </Transition>

                <!-- AGENT REASONING -->
                <Transition enter-from-class="opacity-0 translate-y-2" enter-active-class="transition duration-300">
                    <div v-if="reasoning" class="bg-white dark:bg-slate-900 border-l-4 border-indigo-500 rounded-2xl shadow-sm p-5 flex items-start gap-4">
                        <div class="flex-shrink-0 w-9 h-9 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center text-base">💡</div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Analisis Smart Routing</h4>
                            <p class="text-sm text-slate-700 dark:text-slate-300 mt-1 leading-relaxed">{{ reasoning }}</p>
                        </div>
                    </div>
                </Transition>

                <!-- MAIN GRID: MAP + DESTINATION LIST -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                    <!-- MAP (full height) -->
                    <div class="lg:col-span-8 bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-md overflow-hidden flex flex-col">
                        <!-- Map toolbar -->
                        <div class="bg-slate-900 px-5 py-3.5 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span class="text-xs font-bold text-white tracking-wider uppercase">Live Route Map</span>
                            </div>
                            <div class="flex flex-wrap gap-3 text-[10px] font-semibold text-slate-400">
                                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-indigo-400 inline-block"></span>Posisi Anda</span>
                                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-400 inline-block"></span>Tujuan (Cerah)</span>
                                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-400 inline-block"></span>Tujuan (Hujan)</span>
                                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-400 inline-block"></span>Alternatif Indoor</span>
                            </div>
                        </div>

                        <!-- Map container -->
                        <div class="relative flex-grow" style="min-height:480px">
                            <!-- Loading overlay -->
                            <Transition enter-from-class="opacity-0" enter-active-class="transition duration-200" leave-to-class="opacity-0" leave-active-class="transition duration-200">
                                <div v-if="isLoading" class="absolute inset-0 bg-white/60 dark:bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-50">
                                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl px-5 py-4 flex items-center gap-3 border border-slate-100 dark:border-slate-700">
                                        <svg class="animate-spin h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                        </svg>
                                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Memperbarui peta...</span>
                                    </div>
                                </div>
                            </Transition>
                            <div id="map-view-element" class="w-full h-full absolute inset-0"></div>
                        </div>
                    </div>

                    <!-- DESTINATION LIST -->
                    <div class="lg:col-span-4 space-y-4">

                        <div class="flex items-center justify-between px-1">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Daftar Rekomendasi</h3>
                            <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">{{ recommendations.length }} tempat</span>
                        </div>

                        <!-- Loading skeleton -->
                        <template v-if="isLoading">
                            <div v-for="n in 3" :key="n" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 p-4 flex gap-3 animate-pulse">
                                <div class="w-16 h-16 rounded-xl bg-slate-100 dark:bg-slate-800 flex-shrink-0"></div>
                                <div class="flex-1 space-y-2 py-1">
                                    <div class="h-3 bg-slate-100 dark:bg-slate-800 rounded w-3/4"></div>
                                    <div class="h-2 bg-slate-100 dark:bg-slate-800 rounded w-full"></div>
                                    <div class="h-2 bg-slate-100 dark:bg-slate-800 rounded w-1/2"></div>
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <!-- Starting destination card -->
                            <div v-if="starting" :class="[
                                'group rounded-2xl border p-4 flex gap-3 items-start transition-all duration-200 hover:shadow-md',
                                starting.recommended
                                    ? 'bg-gradient-to-br from-blue-50 to-indigo-50/60 border-blue-100'
                                    : 'bg-gradient-to-br from-rose-50 to-red-50/60 border-rose-100'
                            ]">
                                <div :class="['flex-shrink-0 w-9 h-9 rounded-xl flex items-center justify-center text-white text-[10px] font-black shadow-sm', starting.recommended ? 'bg-blue-500' : 'bg-rose-500']">
                                    AWAL
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="text-xs font-bold text-slate-800 leading-snug">{{ starting.name }}</h4>
                                        <span class="text-[9px] text-slate-400 font-semibold flex-shrink-0">🌳 Outdoor</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 mt-1 leading-relaxed line-clamp-2">{{ starting.address }}</p>
                                    <span :class="['inline-block mt-2 text-[9px] font-extrabold uppercase px-2 py-0.5 rounded-md', starting.recommended ? 'bg-indigo-100 text-indigo-700' : 'bg-rose-100 text-rose-700']">
                                        {{ starting.recommended ? '✅ Aman Dikunjungi' : '⚠️ Risiko Cuaca Hujan' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Empty state -->
                            <div v-if="!recommendations.length && !isLoading"
                                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 p-8 text-center text-sm text-slate-400 dark:text-slate-500 italic">
                                Tidak ada rekomendasi tersedia.
                            </div>

                            <!-- Recommendation cards -->
                            <div v-for="(rec, index) in recommendations" :key="rec.name"
                                class="group bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 flex gap-0">

                                <!-- Image thumbnail -->
                                <div class="relative w-20 flex-shrink-0 overflow-hidden bg-slate-100 dark:bg-slate-800">
                                    <img :src="rec.photo || 'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?q=80&w=400'"
                                        :alt="rec.name"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                    <!-- Reflection shimmer -->
                                    <div class="absolute inset-0 bg-gradient-to-br from-white/10 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                </div>

                                <!-- Content -->
                                <div class="p-3.5 flex-1 min-w-0 flex flex-col justify-between gap-2">
                                    <div>
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="text-[9px] font-extrabold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/40 px-1.5 py-0.5 rounded uppercase tracking-wider">
                                                Alt #{{ index + 1 }}
                                            </span>
                                            <div class="flex-shrink-0 bg-indigo-600 text-white text-[10px] font-black rounded-lg px-2 py-0.5 text-center leading-tight">
                                                {{ rec.suitability_score }}%
                                                <span class="block text-[7px] font-semibold opacity-80">Match</span>
                                            </div>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-1.5 leading-snug line-clamp-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ rec.name }}</h4>
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">{{ rec.address }}</p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                                        <span>⭐ {{ rec.rating }}</span>
                                        <span class="text-slate-200 dark:text-slate-700">·</span>
                                        <span>📍 {{ rec.distance }} km</span>
                                        <span class="text-slate-200 dark:text-slate-700">·</span>
                                        <span :class="rec.open_now ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-rose-400'">
                                            {{ rec.opening_hours }}
                                        </span>
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
#map-view-element { min-height: 480px; }
.leaflet-container { height: 100% !important; width: 100% !important; border-radius: 0; }
</style>
