<script setup>
import { ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Register" />

    <div class="min-h-screen flex text-slate-900 dark:text-slate-100 bg-white dark:bg-slate-950 font-sans selection:bg-indigo-500 selection:text-white">
        
        <!-- Left Side: Register Form -->
        <div class="flex flex-col justify-center w-full lg:w-1/2 px-8 sm:px-16 md:px-24 xl:px-32 relative z-10 py-12 lg:py-0">
            <!-- Decorative Elements -->
            <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
                <div class="absolute -top-24 -left-24 w-96 h-96 bg-indigo-100 dark:bg-indigo-900/20 rounded-full blur-3xl opacity-50"></div>
                <div class="absolute -bottom-24 right-0 w-96 h-96 bg-emerald-50 dark:bg-emerald-900/20 rounded-full blur-3xl opacity-50"></div>
            </div>

            <div class="w-full max-w-sm mx-auto relative z-10">
                <!-- Logo & Heading -->
                <div class="mb-8 flex flex-col items-center sm:items-start text-center sm:text-left">
                    <Link href="/" class="inline-block mb-4 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 rounded-xl">
                        <ApplicationLogo class="h-10 w-auto" />
                    </Link>
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                        Daftar Akun
                    </h2>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 font-medium">
                        Buat akun baru untuk mulai menjelajahi platform cerdas kami.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Name -->
                    <div>
                        <InputLabel for="name" value="Nama" class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1.5" />
                        <div class="relative">
                            <TextInput
                                id="name"
                                type="text"
                                class="block w-full pl-11 py-3 bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm"
                                v-model="form.name"
                                required
                                autofocus
                                autocomplete="name"
                                placeholder="Nama Lengkap"
                            />
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <!-- Email -->
                    <div>
                        <InputLabel for="email" value="Email" class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1.5" />
                        <div class="relative">
                            <TextInput
                                id="email"
                                type="email"
                                class="block w-full pl-11 py-3 bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm"
                                v-model="form.email"
                                required
                                autocomplete="username"
                                placeholder="nama@email.com"
                            />
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" /></svg>
                        </div>
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <!-- Password -->
                    <div>
                        <InputLabel for="password" value="Password" class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1.5" />
                        <div class="relative">
                            <TextInput
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="block w-full pl-11 pr-11 py-3 bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm"
                                v-model="form.password"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                            />
                            <!-- Lock Icon -->
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            <!-- Eye Icon Toggle -->
                            <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-indigo-500 focus:outline-none transition-colors" title="Tampilkan Password">
                                <svg v-if="showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                            </button>
                        </div>
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <InputLabel for="password_confirmation" value="Konfirmasi Password" class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1.5" />
                        <div class="relative">
                            <TextInput
                                id="password_confirmation"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                class="block w-full pl-11 pr-11 py-3 bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                            />
                            <!-- Lock Icon -->
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            <!-- Eye Icon Toggle -->
                            <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-indigo-500 focus:outline-none transition-colors" title="Tampilkan Password">
                                <svg v-if="showConfirmPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                            </button>
                        </div>
                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                    </div>

                    <!-- Submit -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full flex items-center justify-center gap-2 py-3.5 px-4 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white rounded-xl font-bold uppercase tracking-wider shadow-lg shadow-indigo-200 dark:shadow-none transition-all active:scale-[0.98] disabled:opacity-70 disabled:pointer-events-none"
                        >
                            <span v-if="form.processing">Memproses...</span>
                            <span v-else>Daftar Sekarang</span>
                            <svg v-if="!form.processing" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                        </button>
                    </div>

                    <!-- Login Link -->
                    <div class="text-center mt-6">
                        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">
                            Sudah punya akun?
                            <Link :href="route('login')" class="font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 transition-colors focus:outline-none focus:underline">
                                Masuk di sini
                            </Link>
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Side: Image / Visuals -->
        <div class="hidden lg:flex lg:w-1/2 relative bg-slate-900 overflow-hidden">
            <!-- Dynamic Image based on Theme/Vibe -->
            <img 
                src="https://images.unsplash.com/photo-1501785888041-af3ef285b470?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80" 
                alt="Beautiful Landscape" 
                class="absolute inset-0 w-full h-full object-cover opacity-60 mix-blend-overlay"
            />
            <!-- Gradient Overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-indigo-950 via-indigo-900/60 to-transparent"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950/80 to-transparent lg:from-slate-900 lg:via-transparent"></div>

            <!-- Content overlay -->
            <div class="relative z-10 flex flex-col justify-end p-16 w-full h-full">
                <div class="max-w-md">
                    <h1 class="text-4xl md:text-5xl font-black text-white leading-tight mb-4 drop-shadow-lg">
                        Jelajahi dunia dengan rekomendasi cerdas.
                    </h1>
                    <p class="text-lg text-indigo-100 font-medium leading-relaxed drop-shadow-md">
                        Platform kami menganalisis cuaca secara real-time untuk menyajikan destinasi terbaik khusus untuk Anda.
                    </p>
                </div>
                
                <!-- Small abstract elements -->
                <div class="absolute top-1/4 right-1/4 w-64 h-64 bg-indigo-500 rounded-full blur-[100px] opacity-40 mix-blend-screen"></div>
                <div class="absolute bottom-1/3 left-1/4 w-72 h-72 bg-emerald-500 rounded-full blur-[100px] opacity-20 mix-blend-screen"></div>
            </div>
        </div>
    </div>
</template>
