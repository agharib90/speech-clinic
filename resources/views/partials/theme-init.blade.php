<script>
    (function () {
        const key = 'speech-clinic-theme';
        const media = window.matchMedia('(prefers-color-scheme: dark)');

        const readPreference = () => {
            try {
                const stored = localStorage.getItem(key);
                return ['light', 'dark', 'system'].includes(stored) ? stored : 'system';
            } catch (error) {
                return 'system';
            }
        };

        const apply = (preference) => {
            const isDark = preference === 'dark' || (preference === 'system' && media.matches);
            document.documentElement.classList.toggle('dark', isDark);
            document.documentElement.dataset.theme = preference;
            document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
        };

        const set = (preference) => {
            try {
                localStorage.setItem(key, preference);
            } catch (error) {
                // The selected theme still applies for the current page when storage is unavailable.
            }
            apply(preference);
        };

        window.SpeechClinicTheme = {
            key,
            current: readPreference,
            apply,
            set,
            toggle() {
                set(document.documentElement.classList.contains('dark') ? 'light' : 'dark');
            },
        };

        apply(readPreference());
        media.addEventListener('change', () => {
            if (readPreference() === 'system') apply('system');
        });
    })();
</script>
