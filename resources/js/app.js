import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('appointmentAvailability', (config) => ({
    items: config.items,
    patientId: config.patientId,
    itemId: config.itemId,
    therapistId: config.therapistId,
    date: config.date,
    scheduledAt: config.scheduledAt,
    availabilityUrl: config.availabilityUrl,
    availability: null,
    loading: false,
    error: '',

    get patientItems() {
        return this.items.filter((item) => String(item.patient_id) === String(this.patientId));
    },

    get selectedItem() {
        return this.items.find((item) => String(item.id) === String(this.itemId));
    },

    get bookableWindows() {
        return (this.availability?.free_windows || []).filter((window) => window.start_times.length > 0);
    },

    init() {
        if (this.itemId && this.therapistId && this.date) {
            this.loadAvailability();
        }
    },

    resetItem() {
        this.itemId = '';
        this.resetTherapist();
    },

    resetTherapist() {
        this.therapistId = '';
        this.date = '';
        this.resetAvailability();
    },

    resetAvailability() {
        this.availability = null;
        this.scheduledAt = '';
        this.error = '';
    },

    async loadAvailability() {
        const previousStart = this.scheduledAt;
        this.availability = null;
        this.scheduledAt = '';
        this.error = '';

        if (!this.itemId || !this.therapistId || !this.date) {
            return;
        }

        this.loading = true;
        const query = new URLSearchParams({
            patient_service_plan_item_id: this.itemId,
            therapist_id: this.therapistId,
            date: this.date,
        });

        try {
            const response = await fetch(`${this.availabilityUrl}?${query}`, {
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();

            if (!response.ok) {
                const messages = Object.values(payload.errors || {}).flat();
                throw new Error(messages[0] || payload.message || 'تعذر تحميل المواعيد المتاحة.');
            }

            this.availability = payload;
            const previousTime = previousStart?.slice(11, 16);
            if (previousTime && this.bookableWindows.some((window) => window.start_times.includes(previousTime))) {
                this.scheduledAt = `${this.date}T${previousTime}`;
            }
        } catch (error) {
            this.error = error.message || 'تعذر تحميل المواعيد المتاحة.';
        } finally {
            this.loading = false;
        }
    },

    selectStart(time) {
        this.scheduledAt = `${this.date}T${time}`;
    },

    formatTime(time) {
        const [hour, minute] = time.split(':').map(Number);

        return new Intl.DateTimeFormat('ar-EG', {
            hour: 'numeric',
            minute: '2-digit',
        }).format(new Date(2000, 0, 1, hour, minute));
    },

    formatDuration(minutes) {
        if (minutes < 60) {
            return `${minutes} دقيقة`;
        }

        const hours = Math.floor(minutes / 60);
        const remainder = minutes % 60;

        return remainder ? `${hours} ساعة و${remainder} دقيقة` : `${hours} ساعة`;
    },
}));

Alpine.start();
