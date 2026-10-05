{{-- cycles light, dark and system; the label always says what is active, so it never relies on an icon alone --}}
<button type="button" x-data="themeToggle" @click="cycle"
        class="nav-link inline-flex w-full cursor-pointer items-center gap-2 md:w-auto"
        :aria-label="ariaLabel">
    <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4.5"/><path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M4.9 19.1l1.8-1.8M17.3 6.7l1.8-1.8"/></svg>
    <span x-text="label">Theme</span>
</button>
