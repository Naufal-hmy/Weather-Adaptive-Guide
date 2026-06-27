<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';
import { useDarkMode } from '@/Composables/useDarkMode';

const showingNavigationDropdown = ref(false);
const { isDark, toggle } = useDarkMode();
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans antialiased transition-colors duration-300">

        <!-- Navbar -->
        <nav class="sticky top-0 z-50 border-b border-slate-100 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md transition-colors duration-300">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between items-center">

                    <!-- Left: Logo + Nav links -->
                    <div class="flex items-center">
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('dashboard')" class="flex items-center focus:outline-none focus:ring-2 focus:ring-indigo-500/25 rounded-lg p-1">
                                <ApplicationLogo class="block h-9 w-auto dark:brightness-90" />
                            </Link>
                        </div>
                        <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')"
                                class="text-sm font-medium transition-colors duration-200 dark:text-slate-300 dark:hover:text-white">
                                Dashboard
                            </NavLink>

                            <NavLink v-if="$page.props.auth.user?.is_admin"
                                :href="route('destinations.index')" :active="route().current('destinations.*')"
                                class="text-sm font-medium transition-colors duration-200 dark:text-slate-300 dark:hover:text-white">
                                Kelola Destinasi
                            </NavLink>
                        </div>
                    </div>

                    <!-- Right: Dark mode toggle + user dropdown -->
                    <div class="hidden sm:flex sm:items-center sm:gap-3">

                        <!-- Dark Mode Toggle -->
                        <button @click="toggle" type="button"
                            class="relative w-10 h-10 flex items-center justify-center rounded-xl border transition-all duration-200
                                   border-slate-200 dark:border-slate-700
                                   bg-white dark:bg-slate-800
                                   hover:bg-slate-50 dark:hover:bg-slate-700
                                   text-slate-500 dark:text-slate-400
                                   focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                            :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
                            <!-- Sun icon (shown in dark mode) -->
                            <svg v-if="isDark" class="h-4 w-4 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm0 16a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zm10-7a1 1 0 110 2h-1a1 1 0 110-2h1zM3 12a1 1 0 110 2H2a1 1 0 110-2h1zm15.657-7.657a1 1 0 010 1.414l-.707.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM6.464 17.536a1 1 0 010 1.414l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 0zM18.657 17.536a1 1 0 01-1.414 0l-.707-.707a1 1 0 011.414-1.414l.707.707a1 1 0 010 1.414zM6.464 6.464a1 1 0 01-1.414 0l-.707-.707A1 1 0 015.757 4.343l.707.707a1 1 0 010 1.414zM12 7a5 5 0 100 10A5 5 0 0012 7z"/>
                            </svg>
                            <!-- Moon icon (shown in light mode) -->
                            <svg v-else class="h-4 w-4 text-slate-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z"/>
                            </svg>
                        </button>

                        <!-- User Dropdown -->
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button type="button"
                                    class="inline-flex items-center rounded-xl border px-4 py-2 text-sm font-medium leading-4 shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500/20
                                           border-slate-200/60 dark:border-slate-700
                                           bg-white dark:bg-slate-800
                                           text-slate-700 dark:text-slate-200
                                           hover:bg-slate-50 dark:hover:bg-slate-700">
                                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                                    {{ $page.props.auth.user?.name || 'Tamu (Guest)' }}
                                    <svg class="-me-0.5 ms-2 h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </template>
                            <template #content>
                                <DropdownLink v-if="$page.props.auth.user" :href="route('profile.edit')"
                                    class="dark:text-slate-200 dark:hover:bg-slate-700">
                                    Profile Saya
                                </DropdownLink>
                                <DropdownLink v-if="$page.props.auth.user" :href="route('logout')" method="post" as="button"
                                    class="w-full text-left hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/30 dark:hover:text-rose-400 dark:text-slate-200">
                                    Keluar (Log Out)
                                </DropdownLink>
                                <DropdownLink v-else :href="route('login')"
                                    class="font-semibold hover:bg-indigo-50 hover:text-indigo-600 dark:text-slate-200 dark:hover:bg-indigo-900/30">
                                    Masuk (Log In)
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <!-- Hamburger (mobile) -->
                    <div class="-me-2 flex items-center gap-2 sm:hidden">
                        <!-- Dark mode toggle mobile -->
                        <button @click="toggle" type="button"
                            class="w-9 h-9 flex items-center justify-center rounded-xl border transition-all
                                   border-slate-200 dark:border-slate-700
                                   bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                            <svg v-if="isDark" class="h-4 w-4 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm0 16a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zm10-7a1 1 0 110 2h-1a1 1 0 110-2h1zM3 12a1 1 0 110 2H2a1 1 0 110-2h1zm15.657-7.657a1 1 0 010 1.414l-.707.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM6.464 17.536a1 1 0 010 1.414l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 0zM18.657 17.536a1 1 0 01-1.414 0l-.707-.707a1 1 0 011.414-1.414l.707.707a1 1 0 010 1.414zM6.464 6.464a1 1 0 01-1.414 0l-.707-.707A1 1 0 015.757 4.343l.707.707a1 1 0 010 1.414zM12 7a5 5 0 100 10A5 5 0 0012 7z"/>
                            </svg>
                            <svg v-else class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z"/>
                            </svg>
                        </button>
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center rounded-xl p-2.5 transition duration-150
                                   text-slate-500 dark:text-slate-400
                                   hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-800 dark:hover:text-white">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                class="sm:hidden border-t border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 transition-all duration-300">
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')"
                        class="dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800">
                        Dashboard
                    </ResponsiveNavLink>

                    <ResponsiveNavLink v-if="$page.props.auth.user?.is_admin"
                        :href="route('destinations.index')" :active="route().current('destinations.*')"
                        class="dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800">
                        Kelola Destinasi
                    </ResponsiveNavLink>
                </div>

                <div class="border-t border-slate-100 dark:border-slate-800 pb-4 pt-4 bg-slate-50/50 dark:bg-slate-900/50">
                    <div class="px-4 flex items-center space-x-2">
                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></div>
                        <div>
                            <div class="text-base font-semibold text-slate-800 dark:text-slate-100">
                                {{ $page.props.auth.user?.name || 'Tamu (Guest)' }}
                            </div>
                            <div class="text-sm text-slate-500 dark:text-slate-400">
                                {{ $page.props.auth.user?.email || 'Akses Terbatas' }}
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink v-if="$page.props.auth.user" :href="route('profile.edit')"
                            class="dark:text-slate-300 dark:hover:bg-slate-800">
                            Profile Saya
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.user" :href="route('logout')" method="post" as="button"
                            class="text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20">
                            Keluar (Log Out)
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-else :href="route('login')"
                            class="text-indigo-600 dark:text-indigo-400 font-semibold">
                            Masuk (Log In)
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Header -->
        <header v-if="$slots.header"
            class="bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 shadow-sm transition-colors duration-300">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Page Content -->
        <main>
            <slot />
        </main>
    </div>
</template>
