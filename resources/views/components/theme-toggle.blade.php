{{-- cycles light, dark and system; the label always says what is active, so it never relies on an icon alone --}}
<button type="button" x-data="themeToggle" @click="cycle"
        class="nav-link inline-flex w-full cursor-pointer items-center gap-2 md:w-auto"
        :aria-label="ariaLabel">
    <svg x-show="isLight" x-cloak class="theme-icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4.5"/><path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M4.9 19.1l1.8-1.8M17.3 6.7l1.8-1.8"/></svg>
    <svg x-show="isDark" x-cloak class="theme-icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M20.5 14.2A8.5 8.5 0 1 1 9.8 3.5a6.8 6.8 0 0 0 10.7 10.7z"/></svg>
    <svg x-show="isSystem" class="theme-icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/></svg>
    <span x-text="label">{{ __('Theme') }}</span>
</button>
