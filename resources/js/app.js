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

Alpine.data('newCaseRegistration', (config) => ({
    step: config.initialStep,
    mode: config.mode,
    query: '',
    results: [],
    searched: false,
    loading: false,
    searchError: '',
    selectionError: '',
    requestNumber: 0,
    selectedGuardian: config.selectedGuardian,
    duplicateGuardian: config.duplicateGuardian,
    guardianId: config.guardianId,
    guardian: config.guardian,
    patient: config.patient,
    isActive: config.isActive,

    async searchGuardians() {
        const term = this.query.trim();
        const currentRequest = ++this.requestNumber;

        this.selectionError = '';
        this.searchError = '';

        if (term.length < 2) {
            this.results = [];
            this.searched = false;
            this.loading = false;
            return;
        }

        this.loading = true;

        try {
            const query = new URLSearchParams({ query: term });
            const response = await fetch(`${config.searchUrl}?${query}`, {
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'تعذر البحث عن ولي الأمر.');
            }

            if (currentRequest === this.requestNumber) {
                this.results = payload.data || [];
                this.searched = true;
            }
        } catch (error) {
            if (currentRequest === this.requestNumber) {
                this.results = [];
                this.searched = false;
                this.searchError = error.message || 'تعذر البحث عن ولي الأمر.';
            }
        } finally {
            if (currentRequest === this.requestNumber) this.loading = false;
        }
    },

    selectGuardian(guardian) {
        this.selectedGuardian = guardian;
        this.guardianId = String(guardian.id);
        this.mode = 'existing';
        this.selectionError = '';
    },

    useDuplicateGuardian() {
        if (!this.duplicateGuardian) return;
        this.selectGuardian(this.duplicateGuardian);
        this.step = 2;
        this.$nextTick(() => this.$refs.patientName?.focus());
    },

    changeGuardian() {
        this.step = 1;
        this.mode = 'search';
        this.selectedGuardian = null;
        this.guardianId = '';
        this.selectionError = '';
        this.$nextTick(() => this.$refs.guardianSearch?.focus());
    },

    startNewGuardian() {
        this.mode = 'new';
        this.selectedGuardian = null;
        this.guardianId = '';
        this.selectionError = '';
        this.$nextTick(() => this.$refs.guardianName?.focus());
    },

    guardianLabel() {
        return this.mode === 'new' ? this.guardian : this.selectedGuardian;
    },

    continueToChild() {
        if (this.mode === 'existing' && this.guardianId) {
            this.step = 2;
        } else if (this.mode === 'new' && this.$refs.guardianFields.reportValidity()) {
            this.step = 2;
        } else {
            this.selectionError = 'اختر ولي أمر موجودًا أو أكمل بيانات ولي الأمر الجديد.';
            return;
        }

        this.selectionError = '';
        this.$nextTick(() => this.$refs.patientName?.focus());
    },

    continueToReview() {
        if (!this.$refs.patientFields.reportValidity()) return;
        this.step = 3;
        this.$nextTick(() => this.$refs.submitButton?.focus());
    },

    age() {
        if (!this.patient.birth_date) return '';

        const birthDate = new Date(`${this.patient.birth_date}T00:00:00`);
        const today = new Date();
        let years = today.getFullYear() - birthDate.getFullYear();
        let months = today.getMonth() - birthDate.getMonth();

        if (today.getDate() < birthDate.getDate()) months--;
        if (months < 0) {
            years--;
            months += 12;
        }

        if (years === 0 && months === 0) return 'أقل من شهر';
        if (years === 0) return `${months} شهر`;
        if (months === 0) return `${years} سنة`;
        return `${years} سنة و ${months} شهر`;
    },

    genderLabel() {
        return this.patient.gender === 'female' ? 'أنثى' : 'ذكر';
    },
}));

Alpine.data('patientWorkspace', (config) => ({
    section: config.initialSection,
    panel: config.initialPanel,
    invoiceDetail: null,
    dirtyFormIds: [],
    submittingFormId: null,
    formSnapshots: new Map(),
    formObservers: new Map(),
    ignoredFields: new Set(['_token', 'workspace', 'workspace_panel', 'workspace_section']),

    get hasUnsavedChanges() {
        return this.dirtyFormIds.length > 0;
    },

    init() {
        this.beforeUnloadHandler = (event) => {
            const hasOtherDirtyForm = this.dirtyFormIds.some((id) => id !== this.submittingFormId);

            if (!hasOtherDirtyForm) return;

            event.preventDefault();
            event.returnValue = '';
        };

        window.addEventListener('beforeunload', this.beforeUnloadHandler);
        this.$nextTick(() => window.requestAnimationFrame(() => this.registerTrackedForms()));
    },

    destroy() {
        window.removeEventListener('beforeunload', this.beforeUnloadHandler);
        this.formObservers.forEach((observer) => observer.disconnect());
    },

    openSection(section, scroll = false) {
        if (!config.sections.includes(section)) return;

        this.section = section;
        this.replaceSectionUrl(section);

        if (scroll) {
            this.$nextTick(() => this.$refs.sectionContent?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
        }
    },

    openPanel(panel) {
        const section = config.panelSections[panel];

        if (!section || !config.sections.includes(section)) return;

        this.panel = panel;
        this.openSection(section, true);
    },

    togglePanel(panel) {
        const section = config.panelSections[panel];

        if (!section || !config.sections.includes(section)) return;

        this.panel = this.section === section && this.panel === panel ? null : panel;
        this.openSection(section);
    },

    closePanel(panel) {
        if (this.panel === panel) this.panel = null;
    },

    replaceSectionUrl(section) {
        const url = new URL(window.location.href);
        url.searchParams.set('section', section);
        window.history.replaceState(window.history.state, '', url);
    },

    registerTrackedForms() {
        this.$root.querySelectorAll('form[data-workspace-dirty-track]').forEach((form, index) => {
            const id = form.dataset.workspaceDirtyId || `workspace-form-${index + 1}`;
            form.dataset.workspaceDirtyId = id;
            this.formSnapshots.set(id, this.serializeForm(form));

            const refresh = () => this.$nextTick(() => this.refreshFormState(form, id));
            form.addEventListener('input', refresh);
            form.addEventListener('change', refresh);
            form.addEventListener('click', refresh);
            form.addEventListener('submit', () => {
                this.submittingFormId = id;

                window.setTimeout(() => {
                    if (this.submittingFormId === id) {
                        this.submittingFormId = null;
                    }
                }, 1000);
            });

            const observer = new MutationObserver(refresh);
            observer.observe(form, { childList: true, subtree: true });
            this.formObservers.set(id, observer);
        });
    },

    refreshFormState(form, id) {
        const isDirty = this.serializeForm(form) !== this.formSnapshots.get(id);
        const dirtyIds = new Set(this.dirtyFormIds);

        if (isDirty) dirtyIds.add(id);
        else dirtyIds.delete(id);

        this.dirtyFormIds = [...dirtyIds];
    },

    serializeForm(form) {
        return JSON.stringify(
            [...new FormData(form).entries()]
                .filter(([name]) => !this.ignoredFields.has(name))
                .map(([name, value]) => [name, value instanceof File ? `${value.name}:${value.size}` : String(value)])
        );
    },
}));

Alpine.start();
