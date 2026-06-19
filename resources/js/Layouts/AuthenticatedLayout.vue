<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="min-h-screen bg-slate-50/70 text-slate-900 font-sans antialiased">
        <!-- Sticky Premium Header -->
        <nav class="sticky top-0 z-50 border-b border-slate-100 bg-white/80 backdrop-blur-md transition-all duration-300">
            <!-- Primary Navigation Menu -->
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between items-center">
                    <div class="flex items-center">
                        <!-- Logo -->
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('dashboard')" class="flex items-center focus:outline-none focus:ring-2 focus:ring-indigo-500/25 rounded-lg p-1">
                                <ApplicationLogo class="block h-9 w-auto" />
                            </Link>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                            <NavLink
                                :href="route('dashboard')"
                                :active="route().current('dashboard')"
                                class="text-sm font-medium transition-colors duration-200"
                            >
                                Dashboard
                            </NavLink>
                            <NavLink 
                                v-if="$page.props.auth.user?.is_admin" 
                                :href="route('destinations.index')" 
                                :active="route().current('destinations.*')"
                                class="text-sm font-medium transition-colors duration-200"
                            >
                                Kelola Destinasi
                            </NavLink>
                        </div>
                    </div>

                    <!-- Right Header (Auth Options) -->
                    <div class="hidden sm:flex sm:items-center sm:ms-6">
                        <!-- Settings Dropdown -->
                        <div class="relative ms-3">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded-xl border border-slate-200/60 bg-white px-4 py-2 text-sm font-medium leading-4 text-slate-700 shadow-sm transition duration-150 ease-in-out hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                        >
                                            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                                            {{ $page.props.auth.user?.name || 'Tamu (Guest)' }}

                                            <svg
                                                class="-me-0.5 ms-2 h-4 w-4 text-slate-400 transition-transform duration-200"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </span>
                                </template>

                                <template #content>
                                    <DropdownLink
                                        v-if="$page.props.auth.user"
                                        :href="route('profile.edit')"
                                        class="hover:bg-slate-50"
                                    >
                                        Profile Saya
                                    </DropdownLink>
                                    <DropdownLink
                                        v-if="$page.props.auth.user"
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                        class="w-full text-left hover:bg-rose-50 hover:text-rose-600"
                                    >
                                        Keluar (Log Out)
                                    </DropdownLink>
                                    <DropdownLink
                                        v-else
                                        :href="route('login')"
                                        class="hover:bg-indigo-50 hover:text-indigo-600 font-semibold"
                                    >
                                        Masuk (Log In)
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <!-- Hamburger -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center rounded-xl p-2.5 text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition duration-150 ease-in-out focus:outline-none"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path
                                    :class="{
                                        hidden: showingNavigationDropdown,
                                        'inline-flex': !showingNavigationDropdown,
                                    }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    :class="{
                                        hidden: !showingNavigationDropdown,
                                        'inline-flex': showingNavigationDropdown,
                                    }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu -->
            <div
                :class="{
                    block: showingNavigationDropdown,
                    hidden: !showingNavigationDropdown,
                }"
                class="sm:hidden bg-white border-t border-slate-100 transition-all duration-300"
            >
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink
                        :href="route('dashboard')"
                        :active="route().current('dashboard')"
                    >
                        Dashboard
                    </ResponsiveNavLink>
                    <ResponsiveNavLink
                        v-if="$page.props.auth.user?.is_admin"
                        :href="route('destinations.index')"
                        :active="route().current('destinations.*')"
                    >
                        Kelola Destinasi
                    </ResponsiveNavLink>
                </div>

                <!-- Responsive Settings Options -->
                <div class="border-t border-slate-100 pb-4 pt-4 bg-slate-50/50">
                    <div class="px-4 flex items-center space-x-2">
                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></div>
                        <div>
                            <div class="text-base font-semibold text-slate-800">
                                {{ $page.props.auth.user?.name || 'Tamu (Guest)' }}
                            </div>
                            <div class="text-sm text-slate-500">
                                {{ $page.props.auth.user?.email || 'Akses Terbatas' }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink v-if="$page.props.auth.user" :href="route('profile.edit')">
                            Profile Saya
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="$page.props.auth.user"
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="text-rose-600 hover:bg-rose-50"
                        >
                            Keluar (Log Out)
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-else
                            :href="route('login')"
                            class="text-indigo-600 font-semibold"
                        >
                            Masuk (Log In)
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Heading (Sleek Banner) -->
        <header class="bg-white border-b border-slate-100 shadow-sm" v-if="$slots.header">
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
