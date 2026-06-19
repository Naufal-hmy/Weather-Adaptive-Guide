<template>
    <div class="flex items-center space-x-2">
        <svg class="h-9 w-9 text-indigo-600 dark:text-indigo-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Sun Backing -->
            <circle cx="17" cy="7" r="4" fill="#FBBF24" class="opacity-80" />
            
            <!-- Styled Globe/Travel Paths -->
            <path d="M12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="opacity-40" />
            
            <!-- Cloud Outline -->
            <path d="M5 16C5 13.7909 6.79086 12 9 12C9.5539 12 10.0818 12.1124 10.565 12.315C11.3653 10.9412 12.8716 10 14.6 10C17.03 10 19 11.97 19 14.4C19 14.6033 18.9862 14.8033 18.9592 15C20.1264 15.1118 21 16.0967 21 17.3C21 18.7912 19.7912 20 18.3 20H8.7C6.65655 20 5 18.3435 5 16Z" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
            
            <!-- Flight Route swoosh -->
            <path d="M2 12C8 9 15 15 22 4" stroke="#6366F1" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="1 1" class="opacity-90" />
            
            <!-- Plane Icon -->
            <path d="M21.5 3.5L16.5 6L18 7.5L21.5 3.5Z" fill="#6366F1" />
        </svg>
        <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent">
            AeroWeather
        </span>
    </div>
</template>
