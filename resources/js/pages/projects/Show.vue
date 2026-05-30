<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, shallowRef, nextTick } from 'vue';
import {
    ArrowLeft,
    CircleAlert,
    FileText,
    FolderOpen,
    Plus,
    Save,
    MapPin,
    LocateFixed,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import EntityDetailHero from '@/components/entity/EntityDetailHero.vue';
import EntityPageSection from '@/components/entity/EntityPageSection.vue';
import DocumentUploadPanel from '@/components/shared/DocumentUploadPanel.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';
import type {
    ClientOption,
    DocumentConnectionOption,
    DocumentItem,
    Mode,
    PaymentStatus,
    ProgressSnapshot,
    ProjectDetails,
    ProjectStatus,
    UploadedDocument,
} from '@/types/project';

import { toast } from 'vue-sonner';

// Leaflet Imports
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

const props = defineProps<{
    mode: Mode;
    project: ProjectDetails & {
        latitude?: number | null;
        longitude?: number | null;
    };
    clients: ClientOption[];
    documents: DocumentItem[];
    progress: ProgressSnapshot;
    recentReport: {
        date: string | null;
        description: string | null;
        percent: number | null;
    } | null;
    documentConnections: DocumentConnectionOption[];
    uploadedDocuments: UploadedDocument[];
}>();

const isCreateMode = computed(() => props.mode === 'create');

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Projects',
        href: '/projects',
    },
    {
        title: isCreateMode.value ? 'Create Project' : props.project.name,
        href: isCreateMode.value
            ? '/projects/create'
            : `/projects/${props.project.id}`,
    },
]);

const projectStatusOptions: Array<{
    value: ProjectStatus;
    label: string;
    hint: string;
}> = [
    {
        value: 'planning',
        label: 'Planning',
        hint: 'Initial scope and approvals',
    },
    { value: 'ongoing', label: 'Ongoing', hint: 'Work is actively running' },
    {
        value: 'completed',
        label: 'Completed',
        hint: 'Delivery and handover done',
    },
];

const paymentStatusOptions: Array<{
    value: PaymentStatus;
    label: string;
    hint: string;
}> = [
    {
        value: 'pending',
        label: 'Pending',
        hint: 'Waiting for billing progress',
    },
    {
        value: 'partial',
        label: 'Partial',
        hint: 'Some payment has been received',
    },
    { value: 'paid', label: 'Paid', hint: 'Invoice is fully settled' },
    { value: 'overdue', label: 'Overdue', hint: 'Payment is late' },
];

const form = useForm({
    client_id: props.project.clientId
        ? String(props.project.clientId)
        : props.clients[0]?.id
          ? String(props.clients[0].id)
          : '',
    name: props.project.name,
    contract_number: props.project.contractNumber ?? '',
    contract_value: props.project.contractValue || 0,
    start_date: props.project.startDate ?? '',
    end_date: props.project.endDate ?? '',
    location: props.project.location ?? '',
    latitude: props.project.latitude ?? undefined,
    longitude: props.project.longitude ?? undefined,
    status: props.project.status,
    payment_status: props.project.paymentStatus,
});

// Map State
const mapContainer = shallowRef<HTMLElement | null>(null);
let map: L.Map;
let marker: L.Marker;

onMounted(() => {
    // Fix untuk icon marker Leaflet yang sering hilang di Vite/Vue
    delete (L.Icon.Default.prototype as any)._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl:
            'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
        iconUrl:
            'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
        shadowUrl:
            'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
    });

    const initLat = form.latitude ? Number(form.latitude) : -7.954625;
    const initLng = form.longitude ? Number(form.longitude) : 112.614619;

    // Gunakan nextTick agar Vue memastikan div mapContainer sudah selesai di-render
    nextTick(() => {
        if (mapContainer.value) {
            // Inisialisasi Map
            map = L.map(mapContainer.value).setView([initLat, initLng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);

            marker = L.marker([initLat, initLng], {
                draggable: true,
            }).addTo(map);

            // JURUS PAMUNGKAS: Paksa map me-render ulang ukurannya setelah 200ms
            setTimeout(() => {
                map.invalidateSize();
            }, 200);

            // Event Listeners
            marker.on('dragend', (e) => {
                const position = e.target.getLatLng();
                form.latitude = position.lat;
                form.longitude = position.lng;
            });

            map.on('click', (e: L.LeafletMouseEvent) => {
                marker.setLatLng(e.latlng);
                form.latitude = e.latlng.lat;
                form.longitude = e.latlng.lng;
            });
        }
    });
});

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const getProjectStatusClass = (status: ProjectStatus) =>
    ({
        planning: 'bg-slate-500/15 text-slate-600 ring-1 ring-slate-500/25',
        ongoing: 'bg-blue-500/15 text-blue-600 ring-1 ring-blue-500/25',
        completed:
            'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-500/25',
    })[status];

const getPaymentStatusClass = (status: PaymentStatus) =>
    ({
        pending: 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-500/25',
        partial: 'bg-sky-500/15 text-sky-600 ring-1 ring-sky-500/25',
        paid: 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-500/25',
        overdue: 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-500/25',
    })[status];

const formatProjectStatus = (status: ProjectStatus) =>
    projectStatusOptions.find((option) => option.value === status)?.label ??
    status;

const formatPaymentStatus = (status: PaymentStatus) =>
    paymentStatusOptions.find((option) => option.value === status)?.label ??
    status;

const getMvpStatusClass = (status: string) =>
    ({
        'On Track':
            'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-500/25',
        Warning: 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-500/25',
        Critical: 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-500/25',
        'On Hold': 'bg-slate-500/15 text-slate-600 ring-1 ring-slate-500/25',
    })[status] ?? 'bg-slate-500/15 text-slate-600 ring-1 ring-slate-500/25';

const selectedClient = computed(
    () =>
        props.clients.find(
            (client) => String(client.id) === String(form.client_id),
        ) ?? null,
);

const liveProgressScore = computed(() =>
    Math.max(
        0,
        Math.min(100, Number(props.project.latestProgressPercent ?? 0)),
    ),
);

const progressDisplayValue = computed(() =>
    new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 2,
    }).format(liveProgressScore.value),
);

const progressLabel = computed(() => {
    if (liveProgressScore.value >= 90) return 'Ready for handover';
    if (liveProgressScore.value >= 70) return 'Nearly complete';
    if (liveProgressScore.value >= 40) return 'In progress';
    return 'Early stage';
});

const progressToneClass = computed(() => {
    if (liveProgressScore.value >= 90) return 'bg-emerald-500';
    if (liveProgressScore.value >= 70) return 'bg-blue-500';
    if (liveProgressScore.value >= 40) return 'bg-amber-500';
    return 'bg-slate-500';
});

const submit = () => {
    if (isCreateMode.value) {
        form.post('/projects', { preserveScroll: true });
        return;
    }

    form.patch(`/projects/${props.project.id}`, { preserveScroll: true });
};

const backToProjects = () => {
    router.get('/projects');
};

const openDocument = (document: DocumentItem) => {
    if (!document.url) return;
    router.visit(document.url);
};

const getCurrentLocation = () => {
    if (navigator.geolocation) {
        toast.info('Melacak lokasi saat ini...');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                // Update form state
                form.latitude = lat;
                form.longitude = lng;

                // Update posisi marker dan center map di Leaflet
                if (map && marker) {
                    const newLatLng = new L.LatLng(lat, lng);
                    marker.setLatLng(newLatLng);
                    map.setView(newLatLng, 16); // Zoom lebih dekat
                }

                toast.success('Titik lokasi berhasil diperbarui!');
            },
            (error) => {
                console.error(error);
                toast.error(
                    'Gagal mendapat lokasi. Pastikan izin GPS browser aktif.',
                );
            },
        );
    } else {
        toast.error('Browser tidak mendukung fitur geolokasi.');
    }
};
</script>

<template>
    <Head
        :title="
            isCreateMode
                ? 'Create Project'
                : `Project Detail - ${props.project.name}`
        "
    />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex min-h-[calc(100vh-8rem)] min-w-0 flex-1 flex-col gap-3 rounded-xl p-2 sm:gap-4 sm:p-4"
        >
            <section
                class="flex min-h-0 min-w-0 flex-1 flex-col gap-4 overflow-hidden rounded-xl border border-sidebar-border/70 bg-background/80 p-3 shadow-sm sm:rounded-2xl sm:p-5"
            >
                <div
                    class="grid min-h-0 min-w-0 flex-1 gap-4 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]"
                >
                    <div class="flex min-h-0 min-w-0 flex-col gap-4">
                        <EntityDetailHero
                            back-label="Back to Projects"
                            title="Project Detail"
                            :description="
                                isCreateMode
                                    ? 'Fill in the project data and save it to the database.'
                                    : 'Edit the project details and save your updates to the database.'
                            "
                            :badge-text="props.project.mvpStatus ?? 'On Track'"
                            :badge-class="
                                getMvpStatusClass(
                                    props.project.mvpStatus ?? 'On Track',
                                )
                            "
                            metric-label="BAMC Progress"
                            :metric-value="`${progressDisplayValue}%`"
                            :metric-description="progressLabel"
                            progress-label="Progress snapshot"
                            :progress-value="`${progressDisplayValue}% report progress`"
                            :progress-bar-value="liveProgressScore"
                            :progress-tone-class="progressToneClass"
                            @back="backToProjects"
                        >
                            <template #back>
                                <Button
                                    variant="ghost"
                                    class="mb-3 pl-0 text-muted-foreground"
                                    @click="backToProjects"
                                >
                                    <ArrowLeft class="mr-2 size-4" />
                                    Back to Projects
                                </Button>
                            </template>
                            <template #title-input>
                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p
                                        class="text-xs tracking-[0.18em] text-muted-foreground uppercase"
                                    >
                                        Project Title
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-medium break-words text-foreground"
                                    >
                                        {{ form.name || 'Untitled project' }}
                                    </p>
                                </div>
                            </template>
                            <template #title-meta>
                                <p
                                    class="mt-2 max-w-full truncate text-sm text-muted-foreground"
                                    :title="
                                        selectedClient?.name ??
                                        'Choose a client to connect this project.'
                                    "
                                >
                                    {{
                                        selectedClient?.name ??
                                        'Choose a client to connect this project.'
                                    }}
                                </p>
                            </template>
                            <template #summary>
                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p
                                        class="text-xs tracking-[0.18em] text-muted-foreground uppercase"
                                    >
                                        Client
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-medium break-words text-foreground"
                                    >
                                        {{ form.client_id || '-' }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p
                                        class="text-xs tracking-[0.18em] text-muted-foreground uppercase"
                                    >
                                        Contract No.
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-medium break-words text-foreground"
                                    >
                                        {{ form.contract_number || '-' }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p
                                        class="text-xs tracking-[0.18em] text-muted-foreground uppercase"
                                    >
                                        Value
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-medium break-words text-foreground"
                                    >
                                        {{
                                            formatCurrency(
                                                form.contract_value || 0,
                                            )
                                        }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p
                                        class="text-xs tracking-[0.18em] text-muted-foreground uppercase"
                                    >
                                        Location
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-medium break-words text-foreground"
                                    >
                                        {{ form.location || '-' }}
                                    </p>
                                </div>
                            </template>
                        </EntityDetailHero>

                        <EntityPageSection
                            v-if="
                                !isCreateMode &&
                                (props.project.warnings?.length ?? 0) > 0
                            "
                            title="Early Warnings"
                            :icon="CircleAlert"
                        >
                            <div class="grid gap-2">
                                <div
                                    v-for="warning in props.project.warnings"
                                    :key="`${warning.type}-${warning.message}`"
                                    class="rounded-xl border border-sidebar-border/70 bg-muted/20 p-3 text-sm"
                                >
                                    <Badge
                                        :class="
                                            warning.level === 'critical'
                                                ? 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-500/25'
                                                : 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-500/25'
                                        "
                                    >
                                        {{ warning.level }}
                                    </Badge>
                                    <p class="mt-2 text-foreground">
                                        {{ warning.message }}
                                    </p>
                                </div>
                            </div>
                        </EntityPageSection>

                        <EntityPageSection
                            v-if="!isCreateMode"
                            title="MVP Monitoring Summary"
                            :icon="FileText"
                        >
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p
                                        class="text-xs tracking-[0.18em] text-muted-foreground uppercase"
                                    >
                                        RAB Total
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-medium text-foreground"
                                    >
                                        {{
                                            formatCurrency(
                                                props.project.rabTotal ?? 0,
                                            )
                                        }}
                                    </p>
                                </div>
                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p
                                        class="text-xs tracking-[0.18em] text-muted-foreground uppercase"
                                    >
                                        RAP Total
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-medium text-foreground"
                                    >
                                        {{
                                            formatCurrency(
                                                props.project.rapTotal ?? 0,
                                            )
                                        }}
                                    </p>
                                </div>
                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p
                                        class="text-xs tracking-[0.18em] text-muted-foreground uppercase"
                                    >
                                        Realized Cost
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-medium text-foreground"
                                    >
                                        {{
                                            formatCurrency(
                                                props.project
                                                    .realizedCostTotal ?? 0,
                                            )
                                        }}
                                    </p>
                                </div>
                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p
                                        class="text-xs tracking-[0.18em] text-muted-foreground uppercase"
                                    >
                                        Latest Progress
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-medium text-foreground"
                                    >
                                        {{
                                            props.project
                                                .latestProgressPercent ?? 0
                                        }}%
                                        <span class="text-muted-foreground">
                                            {{
                                                props.project
                                                    .latestProgressApproved
                                                    ? 'approved'
                                                    : 'draft/unofficial'
                                            }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </EntityPageSection>
                        <EntityPageSection>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="flex items-center gap-2 text-sm font-medium text-foreground"
                                    >
                                        <MapPin class="size-4 text-blue-500" />
                                        Geographic Location
                                    </span>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="getCurrentLocation"
                                        class="h-8 text-xs"
                                    >
                                        <LocateFixed
                                            class="mr-2 size-3 text-blue-500"
                                        />
                                        Lokasi Saat Ini
                                    </Button>
                                </div>

                                <p class="text-xs text-muted-foreground">
                                    Geser marker atau klik pada peta untuk
                                    menetapkan titik koordinat.
                                </p>

                                <div
                                    ref="mapContainer"
                                    class="z-10 h-[260px] w-full rounded-xl border border-sidebar-border/70 bg-muted/20"
                                ></div>

                                <div
                                    class="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2"
                                >
                                    <label class="min-w-0 space-y-2">
                                        <span
                                            class="text-xs font-semibold text-muted-foreground uppercase"
                                            >Latitude</span
                                        >
                                        <Input
                                            v-model="form.latitude"
                                            readonly
                                            class="cursor-not-allowed bg-muted/30 font-mono text-muted-foreground"
                                            placeholder="Select on map"
                                        />
                                        <InputError
                                            :message="form.errors.latitude"
                                        />
                                    </label>
                                    <label class="min-w-0 space-y-2">
                                        <span
                                            class="text-xs font-semibold text-muted-foreground uppercase"
                                            >Longitude</span
                                        >
                                        <Input
                                            v-model="form.longitude"
                                            readonly
                                            class="cursor-not-allowed bg-muted/30 font-mono text-muted-foreground"
                                            placeholder="Select on map"
                                        />
                                        <InputError
                                            :message="form.errors.longitude"
                                        />
                                    </label>
                                </div>
                            </div>
                        </EntityPageSection>
                    </div>

                    <div class="flex min-h-0 min-w-0 flex-col gap-4">
                        <EntityPageSection title="Settings" :icon="FileText">
                            <div class="grid min-w-0 gap-4">
                                <label class="min-w-0 space-y-2">
                                    <span
                                        class="text-sm font-medium text-foreground"
                                        >Project Title</span
                                    >
                                    <Input
                                        v-model="form.name"
                                        class="w-full min-w-0"
                                        placeholder="Project name"
                                    />
                                    <InputError :message="form.errors.name" />
                                </label>

                                <label class="min-w-0 space-y-2">
                                    <span
                                        class="text-sm font-medium text-foreground"
                                        >Client</span
                                    >
                                    <Input
                                        v-model="form.client_id"
                                        placeholder="Enter client name"
                                    />
                                    <InputError
                                        :message="form.errors.client_id"
                                    />
                                </label>

                                <label class="min-w-0 space-y-2">
                                    <span
                                        class="text-sm font-medium text-foreground"
                                        >Contract Number</span
                                    >
                                    <Input
                                        v-model="form.contract_number"
                                        placeholder="Contract number"
                                    />
                                    <InputError
                                        :message="form.errors.contract_number"
                                    />
                                </label>

                                <label class="min-w-0 space-y-2">
                                    <span
                                        class="text-sm font-medium text-foreground"
                                        >Contract Value</span
                                    >
                                    <Input
                                        v-model.number="form.contract_value"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                    />
                                    <InputError
                                        :message="form.errors.contract_value"
                                    />
                                </label>

                                <label class="min-w-0 space-y-2">
                                    <span
                                        class="text-sm font-medium text-foreground"
                                        >Location Title</span
                                    >
                                    <Input
                                        v-model="form.location"
                                        class="min-w-0"
                                        placeholder="General project area"
                                    />
                                    <InputError
                                        :message="form.errors.location"
                                    />
                                </label>

                                <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                                    <label class="min-w-0 space-y-2">
                                        <span
                                            class="text-sm font-medium text-foreground"
                                            >Start Date</span
                                        >
                                        <Input
                                            v-model="form.start_date"
                                            type="date"
                                        />
                                        <InputError
                                            :message="form.errors.start_date"
                                        />
                                    </label>

                                    <label class="min-w-0 space-y-2">
                                        <span
                                            class="text-sm font-medium text-foreground"
                                            >End Date</span
                                        >
                                        <Input
                                            v-model="form.end_date"
                                            type="date"
                                        />
                                        <InputError
                                            :message="form.errors.end_date"
                                        />
                                    </label>
                                </div>

                                <label class="min-w-0 space-y-2">
                                    <span
                                        class="text-sm font-medium text-foreground"
                                        >Project Status</span
                                    >
                                    <Select v-model="form.status">
                                        <SelectTrigger class="w-full">
                                            <SelectValue
                                                placeholder="Choose a status"
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="option in projectStatusOptions"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            projectStatusOptions.find(
                                                (item) =>
                                                    item.value === form.status,
                                            )?.hint
                                        }}
                                    </p>
                                    <InputError :message="form.errors.status" />
                                </label>

                                <label class="min-w-0 space-y-2">
                                    <span
                                        class="text-sm font-medium text-foreground"
                                        >Payment Status</span
                                    >
                                    <Select v-model="form.payment_status">
                                        <SelectTrigger class="w-full">
                                            <SelectValue
                                                placeholder="Choose a payment status"
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="option in paymentStatusOptions"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            paymentStatusOptions.find(
                                                (item) =>
                                                    item.value ===
                                                    form.payment_status,
                                            )?.hint
                                        }}
                                    </p>
                                    <InputError
                                        :message="form.errors.payment_status"
                                    />
                                </label>

                                <div
                                    class="my-3 border-t border-sidebar-border/70"
                                ></div>
                            </div>
                        </EntityPageSection>

                        <EntityPageSection
                            title="Project Documents"
                            :icon="FolderOpen"
                        >
                            <div
                                v-if="isCreateMode"
                                class="rounded-xl border border-dashed border-sidebar-border/70 bg-muted/20 p-4 text-sm text-muted-foreground"
                            >
                                Save the project first, then upload documents
                                here.
                            </div>
                            <div v-else class="space-y-4">
                                <div class="space-y-3">
                                    <button
                                        v-for="document in props.documents"
                                        :key="document.label"
                                        type="button"
                                        class="flex w-full items-start justify-between gap-4 rounded-xl border border-sidebar-border/70 bg-background px-4 py-3 text-left transition hover:bg-muted/40"
                                        :class="{ 'opacity-60': !document.url }"
                                        :disabled="!document.url"
                                        @click="openDocument(document)"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="text-sm font-medium text-foreground"
                                            >
                                                {{ document.label }}
                                            </p>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{ document.detail }}
                                            </p>
                                        </div>
                                        <Badge
                                            :variant="
                                                document.status === 'available'
                                                    ? 'default'
                                                    : 'outline'
                                            "
                                            class="shrink-0"
                                        >
                                            {{ document.status }}
                                        </Badge>
                                    </button>

                                    <div
                                        v-if="props.documents.length === 0"
                                        class="rounded-xl border border-dashed border-sidebar-border/70 bg-muted/20 p-4 text-sm text-muted-foreground"
                                    >
                                        No system documents linked yet.
                                    </div>
                                </div>

                                <DocumentUploadPanel
                                    :project-id="props.project.id"
                                    component-type="project"
                                    :connection-options="
                                        props.documentConnections
                                    "
                                    :documents="props.uploadedDocuments"
                                    title="Dokumen & OCR"
                                    description="Unggah dokumen proyek, jalankan OCR bila tersedia, lalu review draft sebelum diterapkan."
                                    empty-text="Belum ada dokumen proyek."
                                />
                            </div>
                        </EntityPageSection>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-sidebar-border/70 bg-background p-4 shadow-sm"
                >
                    <div class="grid gap-3 sm:grid-cols-2">
                        <Button
                            variant="outline"
                            type="button"
                            class="w-full"
                            @click="backToProjects"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            class="w-full"
                            :disabled="form.processing"
                            @click="submit"
                        >
                            <component
                                :is="isCreateMode ? Plus : Save"
                                class="mr-2 size-4"
                            />
                            {{
                                isCreateMode ? 'Create Project' : 'Save Changes'
                            }}
                        </Button>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Penting: Cegah z-index dari Leaflet menutupi dropdown Shadcn / Select */
.leaflet-container {
    z-index: 10 !important;
}
</style>
