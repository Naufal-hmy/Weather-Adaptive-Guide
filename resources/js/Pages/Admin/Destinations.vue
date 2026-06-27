<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import TextInput  from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    destinations: Array,
    cities: Array,
});

// ── form ──────────────────────────────────────────────────
const form = useForm({
    name: '', description: '', category: 'outdoor',
    city_id: '', image_url: '', image_file: null,
    opening_hours: '08:00 - 17:00', rating: 4.0,
    min_temp: '', max_temp: '',
});

const isEditing  = ref(false);
const editingId  = ref(null);
const fileInput  = ref(null);
const searchQuery = ref('');

// ── stats ─────────────────────────────────────────────────
const totalIndoor  = computed(() => props.destinations?.filter(d => d.category === 'indoor').length  || 0);
const totalOutdoor = computed(() => props.destinations?.filter(d => d.category === 'outdoor').length || 0);

// ── filtered table ────────────────────────────────────────
const filteredDestinations = computed(() => {
    if (!searchQuery.value) return props.destinations;
    const q = searchQuery.value.toLowerCase();
    return props.destinations?.filter(d =>
        d.name.toLowerCase().includes(q) ||
        d.city?.name.toLowerCase().includes(q) ||
        d.category.toLowerCase().includes(q)
    );
});

// ── actions ───────────────────────────────────────────────
const submitForm = () => {
    if (isEditing.value) {
        form.transform(data => ({ ...data, _method: 'put' }))
            .post(route('destinations.update', editingId.value), { onSuccess: resetForm });
    } else {
        form.post(route('destinations.store'), { onSuccess: resetForm });
    }
};

const handleFileUpload = (e) => { form.image_file = e.target.files[0]; };

const editDestination = (dest) => {
    isEditing.value    = true;
    editingId.value    = dest.id;
    form.name          = dest.name;
    form.description   = dest.description;
    form.category      = dest.category;
    form.city_id       = dest.city_id;
    form.image_url     = dest.image_url;
    form.opening_hours = dest.opening_hours;
    form.rating        = dest.rating;
    form.min_temp      = dest.min_temp ?? '';
    form.max_temp      = dest.max_temp ?? '';
    form.image_file    = null;
    if (fileInput.value) fileInput.value.value = '';
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const deleteDestination = (id) => {
    if (confirm('Yakin ingin menghapus destinasi ini?'))
        form.delete(route('destinations.destroy', id));
};

const resetForm = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    if (fileInput.value) fileInput.value.value = '';
};
</script>

<template>
    <Head title="Kelola Destinasi" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Kelola Destinasi</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Tambah, edit, atau hapus destinasi wisata dalam sistem.</p>
                </div>
                <span class="inline-flex items-center self-start sm:self-auto rounded-full bg-rose-50 dark:bg-rose-900/20 px-3 py-1 text-xs font-bold text-rose-700 dark:text-rose-400 ring-1 ring-inset ring-rose-600/20">
                    🔐 Admin Only
                </span>
            </div>
        </template>

        <div class="py-8 bg-slate-50 dark:bg-slate-950 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- STATS ROW -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 to-indigo-700 rounded-2xl p-5 text-white shadow-lg shadow-indigo-200">
                        <div class="absolute -top-4 -right-4 w-20 h-20 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
                        <div class="text-[10px] font-bold uppercase tracking-widest opacity-75">Total Destinasi</div>
                        <div class="text-4xl font-black mt-1">{{ props.destinations?.length || 0 }}</div>
                        <div class="text-[10px] opacity-60 mt-1">terdaftar</div>
                    </div>
                    <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-5 text-white shadow-lg shadow-emerald-200">
                        <div class="absolute -top-4 -right-4 w-20 h-20 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
                        <div class="text-[10px] font-bold uppercase tracking-widest opacity-75">Outdoor</div>
                        <div class="text-4xl font-black mt-1">{{ totalOutdoor }}</div>
                        <div class="text-[10px] opacity-60 mt-1">destinasi</div>
                    </div>
                    <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-5 text-white shadow-lg shadow-blue-200">
                        <div class="absolute -top-4 -right-4 w-20 h-20 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
                        <div class="text-[10px] font-bold uppercase tracking-widest opacity-75">Indoor</div>
                        <div class="text-4xl font-black mt-1">{{ totalIndoor }}</div>
                        <div class="text-[10px] opacity-60 mt-1">destinasi</div>
                    </div>
                    <div class="relative overflow-hidden bg-gradient-to-br from-slate-700 to-slate-800 rounded-2xl p-5 text-white shadow-lg shadow-slate-300">
                        <div class="absolute -top-4 -right-4 w-20 h-20 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
                        <div class="text-[10px] font-bold uppercase tracking-widest opacity-75">Kota</div>
                        <div class="text-4xl font-black mt-1">{{ props.cities?.length || 0 }}</div>
                        <div class="text-[10px] opacity-60 mt-1">tersedia</div>
                    </div>
                </div>

                <!-- MAIN GRID -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                    <!-- FORM PANEL -->
                    <div class="lg:col-span-4">
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border shadow-sm overflow-hidden transition-all duration-300"
                            :class="isEditing ? 'border-indigo-200 dark:border-indigo-800/30 shadow-indigo-100' : 'border-slate-100 dark:border-slate-800'">

                            <!-- Form header -->
                            <div :class="['px-6 py-5 border-b flex items-center justify-between', isEditing ? 'bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-900/20 dark:to-blue-900/20 border-indigo-100 dark:border-indigo-800/30' : 'border-slate-100 dark:border-slate-800']">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">
                                        {{ isEditing ? '✏️ Edit Destinasi' : '➕ Tambah Destinasi Baru' }}
                                    </h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ isEditing ? 'Perbarui data destinasi di bawah.' : 'Lengkapi semua data yang diperlukan.' }}</p>
                                </div>
                                <Transition enter-from-class="opacity-0 scale-90" enter-active-class="transition duration-200">
                                    <span v-if="isEditing" class="bg-indigo-100 text-indigo-700 text-[9px] font-black px-2 py-0.5 rounded-md uppercase tracking-wider">Edit Mode</span>
                                </Transition>
                            </div>

                            <!-- Form body -->
                            <form @submit.prevent="submitForm" class="p-6 space-y-4">

                                <div>
                                    <InputLabel value="Nama Destinasi" class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1" />
                                    <TextInput v-model="form.name" type="text" placeholder="Nama destinasi wisata"
                                        class="block w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5" required />
                                    <p v-if="form.errors.name" class="text-xs text-rose-500 mt-1">{{ form.errors.name }}</p>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <InputLabel value="Kota" class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1" />
                                        <select v-model="form.city_id"
                                            class="block w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3" required>
                                            <option value="" disabled>Pilih kota</option>
                                            <option v-for="city in props.cities" :key="city.id" :value="city.id">{{ city.name }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <InputLabel value="Kategori" class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1" />
                                        <select v-model="form.category"
                                            class="block w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3" required>
                                            <option value="outdoor">🌳 Outdoor</option>
                                            <option value="indoor">🏠 Indoor</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <InputLabel value="Rating (0–5)" class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1" />
                                        <TextInput v-model="form.rating" type="number" step="0.1" min="0" max="5"
                                            class="block w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5" required />
                                    </div>
                                    <div>
                                        <InputLabel value="Jam Buka" class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1" />
                                        <TextInput v-model="form.opening_hours" type="text" placeholder="08:00 - 17:00"
                                            class="block w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5" required />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <InputLabel value="Min Suhu °C" class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1" />
                                        <TextInput v-model="form.min_temp" type="number" placeholder="Opsional"
                                            class="block w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5" />
                                    </div>
                                    <div>
                                        <InputLabel value="Max Suhu °C" class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1" />
                                        <TextInput v-model="form.max_temp" type="number" placeholder="Opsional"
                                            class="block w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5" />
                                    </div>
                                </div>

                                <div>
                                    <InputLabel value="Deskripsi" class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1" />
                                    <textarea v-model="form.description" rows="3"
                                        placeholder="Gambaran singkat tentang destinasi ini..."
                                        class="block w-full rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3.5 resize-none shadow-sm"
                                        required></textarea>
                                    <p v-if="form.errors.description" class="text-xs text-rose-500 mt-1">{{ form.errors.description }}</p>
                                </div>

                                <div>
                                    <InputLabel value="URL Gambar (opsional)" class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1" />
                                    <TextInput v-model="form.image_url" type="url" placeholder="https://images.unsplash.com/..."
                                        class="block w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5" />
                                </div>

                                <!-- Image preview -->
                                <div v-if="form.image_url" class="relative rounded-xl overflow-hidden h-28 bg-slate-100 border border-slate-200">
                                    <img :src="form.image_url" alt="Preview" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
                                    <span class="absolute bottom-2 left-2 text-[9px] font-bold text-white bg-black/40 backdrop-blur-sm px-2 py-0.5 rounded">Preview</span>
                                </div>

                                <div class="border-t border-slate-100 pt-4">
                                    <InputLabel value="Upload Gambar Baru" class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1" />
                                    <input type="file" ref="fileInput" @change="handleFileUpload" accept="image/*"
                                        class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer transition-all" />
                                </div>

                                <!-- Action buttons -->
                                <div class="flex gap-3 pt-2">
                                    <button type="submit" :disabled="form.processing"
                                        class="flex-1 rounded-xl py-2.5 text-sm font-bold text-white shadow-sm transition-all active:scale-[0.98] disabled:opacity-50"
                                        :class="isEditing ? 'bg-indigo-600 hover:bg-indigo-500 shadow-indigo-200' : 'bg-indigo-600 hover:bg-indigo-500'">
                                        {{ isEditing ? '💾 Update Data' : '➕ Simpan Destinasi' }}
                                    </button>
                                    <button v-if="isEditing" type="button" @click="resetForm"
                                        class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 border border-slate-200 hover:bg-slate-50 transition-colors">
                                        Batal
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- TABLE PANEL -->
                    <div class="lg:col-span-8">
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden">

                            <!-- Table header -->
                            <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Daftar Destinasi</h3>
                                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ filteredDestinations?.length || 0 }} dari {{ props.destinations?.length || 0 }} destinasi</p>
                                </div>
                                <!-- Search -->
                                <div class="relative">
                                    <input v-model="searchQuery" type="text" placeholder="Cari nama, kota, kategori..."
                                        class="block w-full sm:w-56 rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 pl-9 pr-3 shadow-sm">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Table -->
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                        <tr>
                                            <th class="px-5 py-3.5 font-bold">Destinasi</th>
                                            <th class="px-4 py-3.5 font-bold">Kota</th>
                                            <th class="px-4 py-3.5 font-bold">Kategori</th>
                                            <th class="px-4 py-3.5 font-bold">Suhu</th>
                                            <th class="px-4 py-3.5 font-bold">Rating</th>
                                            <th class="px-4 py-3.5 text-right font-bold">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">

                                        <!-- Empty state -->
                                        <tr v-if="!filteredDestinations?.length">
                                            <td colspan="6" class="px-5 py-12 text-center">
                                                <div class="flex flex-col items-center gap-2">
                                                    <span class="text-3xl">🗺️</span>
                                                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                                                        {{ searchQuery ? 'Tidak ada destinasi yang cocok.' : 'Belum ada destinasi. Tambahkan yang pertama!' }}
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>

                                        <tr v-for="dest in filteredDestinations" :key="dest.id"
                                            class="group hover:bg-indigo-50/40 dark:hover:bg-indigo-900/20 transition-colors duration-150"
                                            :class="editingId === dest.id ? 'bg-indigo-50/60 dark:bg-indigo-900/30 border-l-4 border-l-indigo-400' : ''">

                                            <!-- Destination name + image -->
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="relative w-10 h-10 rounded-xl overflow-hidden bg-slate-100 flex-shrink-0 shadow-sm">
                                                        <img v-if="dest.image_url" :src="dest.image_url" :alt="dest.name" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                                        <div v-else class="w-full h-full flex items-center justify-center text-slate-300 text-lg">🏔️</div>
                                                        <!-- Reflective shimmer -->
                                                        <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="font-semibold text-slate-900 dark:text-slate-100 text-sm leading-snug truncate max-w-[160px]">{{ dest.name }}</p>
                                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 truncate max-w-[160px]">🕒 {{ dest.opening_hours }}</p>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                                <span class="flex items-center gap-1">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600 inline-block"></span>
                                                    {{ dest.city?.name }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-4">
                                                <span :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider',
                                                    dest.category === 'indoor'
                                                        ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800/50'
                                                        : 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/50']">
                                                    {{ dest.category === 'indoor' ? '🏠' : '🌳' }} {{ dest.category }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-4 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                                <span v-if="dest.min_temp || dest.max_temp">
                                                    {{ dest.min_temp ?? '—' }}° – {{ dest.max_temp ?? '—' }}°C
                                                </span>
                                                <span v-else class="text-slate-300 dark:text-slate-600 italic">semua suhu</span>
                                            </td>

                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-1">
                                                    <span class="text-amber-400 text-sm">⭐</span>
                                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ dest.rating }}</span>
                                                </div>
                                            </td>

                                            <td class="px-4 py-4 text-right whitespace-nowrap">
                                                <div class="flex items-center justify-end gap-2">
                                                    <button @click="editDestination(dest)"
                                                        class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-[11px] font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 border border-indigo-100 transition-all hover:shadow-sm">
                                                        ✏️ Edit
                                                    </button>
                                                    <button @click="deleteDestination(dest.id)"
                                                        class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-[11px] font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-100 transition-all hover:shadow-sm">
                                                        🗑️ Hapus
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
