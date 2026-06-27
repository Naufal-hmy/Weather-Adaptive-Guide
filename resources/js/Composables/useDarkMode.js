import { ref, watchEffect } from 'vue';

const isDark = ref(false);

// Initialize from localStorage or system preference
const stored = localStorage.getItem('theme');
if (stored === 'dark') {
    isDark.value = true;
} else if (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    isDark.value = true;
}

// Apply class to <html> reactively
watchEffect(() => {
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
});

export function useDarkMode() {
    const toggle = () => { isDark.value = !isDark.value; };
    return { isDark, toggle };
}
