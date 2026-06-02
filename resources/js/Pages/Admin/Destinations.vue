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
    <Head title="Kelola Destinasi" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin: Kelola Destinasi</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col md:flex-row gap-6">
                
                <!-- Form Add/Edit -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 w-full md:w-1/3 h-fit">
                    <h3 class="text-lg font-medium mb-4">{{ isEditing ? 'Edit Destinasi' : 'Tambah Destinasi Baru' }}</h3>
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div>
                            <InputLabel for="name" value="Nama Tempat" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel for="city" value="Kota" />
                            <TextInput id="city" v-model="form.city" type="text" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel for="category" value="Kategori" />
                            <select id="category" v-model="form.category" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" required>
                                <option value="outdoor">Outdoor</option>
                                <option value="indoor">Indoor</option>
                            </select>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-1/2">
                                <InputLabel for="min_temp" value="Min Suhu (°C)" />
                                <TextInput id="min_temp" v-model="form.min_temp" type="number" class="mt-1 block w-full" placeholder="Opsional" />
                            </div>
                            <div class="w-1/2">
                                <InputLabel for="max_temp" value="Max Suhu (°C)" />
                                <TextInput id="max_temp" v-model="form.max_temp" type="number" class="mt-1 block w-full" placeholder="Opsional" />
                            </div>
                        </div>
                        <div>
                            <InputLabel for="description" value="Deskripsi Singkat" />
                            <textarea id="description" v-model="form.description" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full h-24" required></textarea>
                        </div>
                        <div>
                            <InputLabel for="image_url" value="URL Gambar (Opsional)" />
                            <TextInput id="image_url" v-model="form.image_url" type="url" class="mt-1 block w-full" placeholder="https://..." />
                        </div>
                        <div class="pt-2 border-t mt-2">
                            <InputLabel for="image_file" value="Atau Upload Gambar Baru" />
                            <input id="image_file" type="file" ref="fileInput" @change="handleFileUpload" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                        </div>

                        <div class="flex items-center gap-4 mt-6">
                            <PrimaryButton :disabled="form.processing">
                                {{ isEditing ? 'Update Data' : 'Simpan Data' }}
                            </PrimaryButton>
                            <button v-if="isEditing" type="button" @click="resetForm" class="text-sm text-gray-600 hover:text-gray-900">Batal</button>
                        </div>
                    </form>
                </div>

                <!-- Data Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 w-full md:w-2/3">
                    <h3 class="text-lg font-medium mb-4">Daftar Destinasi Database</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3">Nama</th>
                                    <th class="px-4 py-3">Kota</th>
                                    <th class="px-4 py-3">Kategori</th>
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="dest in props.destinations" :key="dest.id" class="border-b">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ dest.name }}</td>
                                    <td class="px-4 py-3">{{ dest.city }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="dest.category === 'indoor' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'" class="px-2 py-1 rounded text-xs">
                                            {{ dest.category }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-2">
                                        <button @click="editDestination(dest)" class="text-indigo-600 hover:underline">Edit</button>
                                        <button @click="deleteDestination(dest.id)" class="text-red-600 hover:underline">Hapus</button>
                                    </td>
                                </tr>
                                <tr v-if="!props.destinations.length">
                                    <td colspan="4" class="px-4 py-3 text-center text-gray-500">Belum ada data.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
