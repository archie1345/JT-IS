<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, shallowRef, nextTick } from 'vue';
import {
    ArrowLeft,
    CalendarDays,
    CircleAlert,
    FileText,
    FolderOpen,
    Gauge,
    Plus,
    Save,
    MapPin,
    LocateFixed,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import EntityDetailHero from '@/components/entity/EntityDetailHero.vue';
import EntityPageSection from '@/components/entity/EntityPageSection.vue';
import ProjectDocumentUploadPanel from '@/components/ProjectDocumentUploadPanel.vue';
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

import { toast } from 'vue-sonner'; // Pastikan library toast Abang ter-import

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
    uploadedDocuments: UploadedDocument[];
    documentConnections: DocumentConnectionOption[];
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
    progress_percent: props.project.latestProgressPercent ?? 0,
    progress_note: props.project.latestProgressNote ?? '',
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

const selectedClient = computed(
    () =>
        props.clients.find(
            (client) => String(client.id) === String(form.client_id),
        ) ?? null,
);

const liveProgressScore = computed(() => {
    const reportScore = Number(form.progress_percent ?? 0);

    const projectScore = {
        planning: 25,
        ongoing: 65,
        completed: 100,
    }[form.status];

    const paymentScore = {
        pending: 20,
        partial: 55,
        paid: 100,
        overdue: 35,
    }[form.payment_status];

    return Math.max(
        0,
        Math.min(
            100,
            Math.round(
                reportScore * 0.55 + projectScore * 0.25 + paymentScore * 0.2,
            ),
        ),
    );
});

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
            class="flex min-h-[calc(100vh-8rem)] flex-1 flex-col gap-4 rounded-xl p-4"
        >
            <section
                class="flex min-h-0 flex-1 flex-col gap-4 rounded-2xl border border-sidebar-border/70 bg-background/80 p-5 shadow-sm"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <Button
                            variant="ghost"
                            class="mb-3 pl-0 text-muted-foreground"
                            @click="backToProjects"
                        >
                            <ArrowLeft class="mr-2 size-4" />
                            Back to Projects
                        </Button>
                        <h1
                            class="text-3xl font-semibold tracking-tight text-foreground"
                        >
                            {{
                                isCreateMode
                                    ? 'Create Project'
                                    : 'Project Detail'
                            }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{
                                isCreateMode
                                    ? 'Fill in the project data and save it to the database.'
                                    : 'Edit the project details and save your updates to the database.'
                            }}
                        </p>
                    </div>

                    <Badge :class="getProjectStatusClass(form.status)">
                        {{ formatProjectStatus(form.status) }}
                    </Badge>
                </div>

                <div
                    class="grid min-h-0 flex-1 gap-4 lg:grid-cols-[1.3fr_0.95fr]"
                >
                    <div class="flex min-h-0 flex-col gap-4">
                        <EntityPageSection title="Settings" :icon="FileText">
                            <div class="grid gap-4">
                                <label class="space-y-2">
                                    <span
                                        class="text-sm font-medium text-foreground"
                                        >Client</span
                                    >
                                    <Select v-model="form.client_id">
                                        <SelectTrigger class="w-full">
                                            <SelectValue
                                                placeholder="Choose a client"
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="client in props.clients"
                                                :key="client.id"
                                                :value="String(client.id)"
                                            >
                                                {{
                                                    client.name ??
                                                    `Client #${client.id}`
                                                }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        :message="form.errors.client_id"
                                    />
                                </label>

                                <label class="space-y-2">
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

                                <label class="space-y-2">
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

                                <label class="space-y-2">
                                    <span
                                        class="text-sm font-medium text-foreground"
                                        >Location Title</span
                                    >
                                    <Input
                                        v-model="form.location"
                                        placeholder="General project area"
                                    />
                                    <InputError
                                        :message="form.errors.location"
                                    />
                                </label>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="space-y-2">
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

                                    <label class="space-y-2">
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

                                <label class="space-y-2">
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

                                <label class="space-y-2">
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

                                <div class="space-y-3">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="flex items-center gap-2 text-sm font-medium text-foreground"
                                        >
                                            <MapPin
                                                class="size-4 text-blue-500"
                                            />
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

                                    <div class="grid grid-cols-2 gap-4">
                                        <label class="space-y-2">
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
                                        <label class="space-y-2">
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
                            </div>
                        </EntityPageSection>
                    </div>

                    <div class="flex min-h-0 flex-col gap-4">
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
                                <ProjectDocumentUploadPanel
                                    :project-id="props.project.id"
                                    component-type="project"
                                    :connection-options="
                                        props.documentConnections
                                    "
                                    :documents="props.uploadedDocuments"
                                    title="Project files"
                                    description="Upload contracts, approvals, and general project files here."
                                />

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
