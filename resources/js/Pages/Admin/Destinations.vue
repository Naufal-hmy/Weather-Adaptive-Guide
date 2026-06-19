<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    destinations: Array,
});

const form = useForm({
    name: '',
    description: '',
    category: 'outdoor',
    city: '',
    image_url: '',
    image_file: null,
    min_temp: '',
    max_temp: '',
});

const isEditing = ref(false);
const editingId = ref(null);
const fileInput = ref(null);

const submitForm = () => {
    // Inertia requires POST for file uploads. If editing, we use POST and add _method: PUT
    if (isEditing.value) {
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(route('destinations.update', editingId.value), {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(route('destinations.store'), {
            onSuccess: () => resetForm(),
        });
    }
};

const handleFileUpload = (e) => {
    form.image_file = e.target.files[0];
};

const editDestination = (dest) => {
    isEditing.value = true;
    editingId.value = dest.id;
    form.name = dest.name;
    form.description = dest.description;
    form.category = dest.category;
    form.city = dest.city;
    form.image_url = dest.image_url;
    form.min_temp = dest.min_temp ?? '';
    form.max_temp = dest.max_temp ?? '';
    form.image_file = null;
    if (fileInput.value) fileInput.value.value = '';
};

const deleteDestination = (id) => {
    if (confirm('Yakin ingin menghapus destinasi ini?')) {
        form.delete(route('destinations.destroy', id));
    }
};

const resetForm = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    if (fileInput.value) fileInput.value.value = '';
};
</script>

<template>
    <Head title="Kelola Destinasi - AeroWeather" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">Admin: Kelola Destinasi</h2>
                    <p class="text-sm text-slate-500 mt-1">Tambahkan atau sesuaikan daftar destinasi perjalanan beserta parameter suhunya.</p>
                </div>
            </div>
        </template>

        <div class="py-8 bg-slate-50/50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row gap-8 items-start">
                    
                    <!-- Form Add/Edit Column -->
                    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm p-6 w-full lg:w-4/12">
                        <div class="border-b border-slate-100 pb-4 mb-5">
                            <h3 class="text-lg font-bold text-slate-800">
                                {{ isEditing ? 'Edit Destinasi' : 'Tambah Destinasi Baru' }}
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Lengkapi data destinasi di bawah ini.</p>
                        </div>

                        <form @submit.prevent="submitForm" class="space-y-4">
                            <div>
                                <InputLabel for="name" value="Nama Tempat" class="text-xs font-semibold text-slate-600 uppercase tracking-wider" />
                                <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5 px-3.5 text-sm" placeholder="Nama destinasi wisata" required />
                            </div>

                            <div>
                                <InputLabel for="city" value="Kota" class="text-xs font-semibold text-slate-600 uppercase tracking-wider" />
                                <TextInput id="city" v-model="form.city" type="text" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5 px-3.5 text-sm" placeholder="Contoh: Jakarta, Bali" required />
                            </div>

                            <div>
                                <InputLabel for="category" value="Kategori" class="text-xs font-semibold text-slate-600 uppercase tracking-wider" />
                                <select id="category" v-model="form.category" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5 px-3 text-sm shadow-sm" required>
                                    <option value="outdoor">Outdoor (Luar Ruangan)</option>
                                    <option value="indoor">Indoor (Dalam Ruangan)</option>
                                </select>
                            </div>

                            <div class="flex gap-4">
                                <div class="w-1/2">
                                    <InputLabel for="min_temp" value="Min Suhu (°C)" class="text-xs font-semibold text-slate-600 uppercase tracking-wider" />
                                    <TextInput id="min_temp" v-model="form.min_temp" type="number" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5 px-3 text-sm" placeholder="Opsional" />
                                </div>
                                <div class="w-1/2">
                                    <InputLabel for="max_temp" value="Max Suhu (°C)" class="text-xs font-semibold text-slate-600 uppercase tracking-wider" />
                                    <TextInput id="max_temp" v-model="form.max_temp" type="number" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5 px-3 text-sm" placeholder="Opsional" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="description" value="Deskripsi Singkat" class="text-xs font-semibold text-slate-600 uppercase tracking-wider" />
                                <textarea id="description" v-model="form.description" class="border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm mt-1 block w-full h-24 py-2.5 px-3.5 text-sm" placeholder="Tuliskan gambaran singkat mengenai destinasi wisata ini..." required></textarea>
                            </div>

                            <div>
                                <InputLabel for="image_url" value="URL Gambar (Opsional)" class="text-xs font-semibold text-slate-600 uppercase tracking-wider" />
                                <TextInput id="image_url" v-model="form.image_url" type="url" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5 px-3.5 text-sm" placeholder="https://images.unsplash.com/..." />
                            </div>

                            <div class="pt-3 border-t border-slate-100 mt-2">
                                <InputLabel for="image_file" value="Atau Upload Gambar Baru" class="text-xs font-semibold text-slate-600 uppercase tracking-wider" />
                                <input id="image_file" type="file" ref="fileInput" @change="handleFileUpload" accept="image/*" class="mt-1 block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all cursor-pointer" />
                            </div>

                            <div class="flex items-center gap-3 pt-4">
                                <button 
                                    :disabled="form.processing"
                                    type="submit"
                                    class="flex-1 inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all active:scale-[0.98] disabled:opacity-50"
                                >
                                    {{ isEditing ? 'Update Data' : 'Simpan Data' }}
                                </button>
                                <button 
                                    v-if="isEditing" 
                                    type="button" 
                                    @click="resetForm" 
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-colors"
                                >
                                    Batal
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Data Table Column -->
                    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden p-6 w-full lg:w-8/12">
                        <div class="border-b border-slate-100 pb-4 mb-5">
                            <h3 class="text-lg font-bold text-slate-800">Daftar Destinasi Database</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Daftar lengkap destinasi wisata yang terdaftar di dalam sistem.</p>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-slate-100 shadow-inner">
                            <table class="w-full text-sm text-left text-slate-500">
                                <thead class="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-100">
                                    <tr>
                                        <th class="px-5 py-3.5 font-semibold">Nama Destinasi</th>
                                        <th class="px-5 py-3.5 font-semibold">Kota</th>
                                        <th class="px-5 py-3.5 font-semibold">Kategori</th>
                                        <th class="px-5 py-3.5 font-semibold text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="dest in props.destinations" :key="dest.id" class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-5 py-4 font-semibold text-slate-900">{{ dest.name }}</td>
                                        <td class="px-5 py-4 text-slate-600">{{ dest.city }}</td>
                                        <td class="px-5 py-4">
                                            <span :class="[
                                                'px-2.5 py-1 rounded-lg text-xs font-semibold uppercase tracking-wider',
                                                dest.category === 'indoor' ? 'bg-indigo-50 text-indigo-700' : 'bg-emerald-50 text-emerald-700'
                                            ]">
                                                {{ dest.category }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-right space-x-3 text-xs">
                                            <button @click="editDestination(dest)" class="text-indigo-600 hover:text-indigo-800 font-semibold hover:underline">Edit</button>
                                            <button @click="deleteDestination(dest.id)" class="text-rose-600 hover:text-rose-800 font-semibold hover:underline">Hapus</button>
                                        </td>
                                    </tr>
                                    <tr v-if="!props.destinations || !props.destinations.length">
                                        <td colspan="4" class="px-5 py-8 text-center text-slate-400 italic">
                                            Belum ada data destinasi dalam database. Silakan tambahkan destinasi baru.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
