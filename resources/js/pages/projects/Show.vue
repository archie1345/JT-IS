<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, shallowRef } from 'vue';
import {
    ArrowLeft,
    CircleAlert,
    FileText,
    Plus,
    Save,
    MapPin,
    LocateFixed,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import EntityDetailHero from '@/components/entity/EntityDetailHero.vue';
import EntityPageSection from '@/components/entity/EntityPageSection.vue';
import ProjectDocumentCreateDialog from '@/components/projects/ProjectDocumentCreateDialog.vue';
import ProjectHealthSummary from '@/components/projects/ProjectHealthSummary.vue';
import ProjectRelatedDataSection from '@/components/projects/ProjectRelatedDataSection.vue';
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
    Mode,
    PaymentStatus,
    ProgressSnapshot,
    ProjectDetails,
    ProjectDocumentGroup,
    ProjectStatus,
    UploadedDocument,
} from '@/types/project';

import { toast } from 'vue-sonner';

import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

const props = defineProps<{
    mode: Mode;
    project: ProjectDetails & {
        latitude?: number | null;
        longitude?: number | null;
    };
    clients: ClientOption[];
    documentGroups: ProjectDocumentGroup[];
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
        title: 'Proyek',
        href: '/projects',
    },
    {
        title: isCreateMode.value ? 'Tambah Proyek' : props.project.name,
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
        label: 'Perencanaan',
        hint: 'Scope awal dan persetujuan',
    },
    { value: 'ongoing', label: 'Berjalan', hint: 'Pekerjaan sedang berjalan' },
    {
        value: 'completed',
        label: 'Selesai',
        hint: 'Pekerjaan dan serah terima selesai',
    },
];

const paymentStatusOptions: Array<{
    value: PaymentStatus;
    label: string;
    hint: string;
}> = [
    {
        value: 'pending',
        label: 'Menunggu',
        hint: 'Menunggu progress tagihan',
    },
    {
        value: 'partial',
        label: 'Sebagian',
        hint: 'Sebagian pembayaran sudah diterima',
    },
    { value: 'paid', label: 'Lunas', hint: 'Invoice sudah lunas' },
    { value: 'overdue', label: 'Terlambat', hint: 'Pembayaran melewati jatuh tempo' },
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

const isDocumentCreateOpen = ref(false);
const activeDocumentGroup = ref<ProjectDocumentGroup | null>(null);

const mapContainer = shallowRef<HTMLElement | null>(null);
let map: L.Map;
let marker: L.Marker;

onMounted(() => {
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

    nextTick(() => {
        if (mapContainer.value) {
            map = L.map(mapContainer.value).setView([initLat, initLng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);

            marker = L.marker([initLat, initLng], {
                draggable: true,
            }).addTo(map);

            setTimeout(() => {
                map.invalidateSize();
            }, 200);

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

const formatProjectHealthStatus = (status: string) =>
    ({
        'On Track': 'Sesuai Rencana',
        Warning: 'Perhatian',
        Critical: 'Kritis',
        'On Hold': 'Ditahan',
    })[status] ?? status;

const getProjectHealthStatusClass = (status: string) =>
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
    if (liveProgressScore.value >= 90) return 'Siap serah terima';
    if (liveProgressScore.value >= 70) return 'Hampir selesai';
    if (liveProgressScore.value >= 40) return 'Sedang berjalan';
    return 'Tahap awal';
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

const openGroupCreate = (group: ProjectDocumentGroup) => {
    if (!group.createKind) {
        return;
    }

    activeDocumentGroup.value = group;
    isDocumentCreateOpen.value = true;
};

const setDocumentCreateOpen = (open: boolean) => {
    isDocumentCreateOpen.value = open;

    if (!open) {
        activeDocumentGroup.value = null;
    }
};

const getCurrentLocation = () => {
    if (navigator.geolocation) {
        toast.info('Melacak lokasi saat ini...');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                form.latitude = lat;
                form.longitude = lng;

                if (map && marker) {
                    const newLatLng = new L.LatLng(lat, lng);
                    marker.setLatLng(newLatLng);
                    map.setView(newLatLng, 16);
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
                ? 'Tambah Proyek'
                : `Detail Proyek - ${props.project.name}`
        "
    />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex min-h-[calc(100vh-8rem)] min-w-0 flex-1 flex-col gap-3 rounded-xl p-2 sm:gap-4 sm:p-4"
        >
            <section
                class="flex min-h-0 min-w-0 flex-1 flex-col gap-4 overflow-hidden rounded-xl border border-sidebar-border/70 bg-background/80 p-3 shadow-sm sm:rounded-2xl sm:p-5"
            >
                <ProjectHealthSummary
                    v-if="!isCreateMode"
                    :project="props.project"
                />

                <div
                    class="grid min-h-0 min-w-0 flex-1 gap-4 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]"
                >
                    <div class="flex min-h-0 min-w-0 flex-col gap-4">
                        <EntityDetailHero
                            back-label="Kembali ke Proyek"
                            title="Detail Proyek"
                            :description="
                                isCreateMode
                                    ? 'Isi data proyek dan simpan ke database.'
                                    : 'Edit detail proyek dan simpan update ke database.'
                            "
                            :badge-text="
                                formatProjectHealthStatus(
                                    props.project.projectHealthStatus ??
                                        'On Track',
                                )
                            "
                            :badge-class="
                                getProjectHealthStatusClass(
                                    props.project.projectHealthStatus ??
                                        'On Track',
                                )
                            "
                            metric-label="Progress BAMC"
                            :metric-value="`${progressDisplayValue}%`"
                            :metric-description="progressLabel"
                            progress-label="Snapshot progress"
                            :progress-value="`${progressDisplayValue}% progress laporan`"
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
                                    Kembali ke Proyek
                                </Button>
                            </template>
                            <template #title-input>
                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p
                                        class="text-xs tracking-[0.18em] text-muted-foreground uppercase"
                                    >
                                        Nama Proyek
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-medium break-words text-foreground"
                                    >
                                        {{ form.name || 'Proyek tanpa nama' }}
                                    </p>
                                </div>
                            </template>
                            <template #title-meta>
                                <p
                                    class="mt-2 max-w-full truncate text-sm text-muted-foreground"
                                    :title="
                                        selectedClient?.name ??
                                        'Pilih klien untuk menghubungkan proyek ini.'
                                    "
                                >
                                    {{
                                        selectedClient?.name ??
                                        'Pilih klien untuk menghubungkan proyek ini.'
                                    }}
                                </p>
                            </template>
                            <template #summary>
                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p
                                        class="text-xs tracking-[0.18em] text-muted-foreground uppercase"
                                    >
                                        Klien
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
                                        No. Kontrak
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
                                        Nilai
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
                                        Lokasi
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
                            title="Peringatan Awal"
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

                        <EntityPageSection>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="flex items-center gap-2 text-sm font-medium text-foreground"
                                    >
                                        <MapPin class="size-4 text-blue-500" />
                                        Lokasi Geografis
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
                                            placeholder="Pilih di peta"
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
                                            placeholder="Pilih di peta"
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
                        <EntityPageSection title="Pengaturan" :icon="FileText">
                            <div class="grid min-w-0 gap-4">
                                <label class="min-w-0 space-y-2">
                                    <span
                                        class="text-sm font-medium text-foreground"
                                        >Nama Proyek</span
                                    >
                                    <Input
                                        v-model="form.name"
                                        class="w-full min-w-0"
                                        placeholder="Nama proyek"
                                    />
                                    <InputError :message="form.errors.name" />
                                </label>

                                <label class="min-w-0 space-y-2">
                                    <span
                                        class="text-sm font-medium text-foreground"
                                        >Klien</span
                                    >
                                    <Input
                                        v-model="form.client_id"
                                        placeholder="Masukkan nama klien"
                                    />
                                    <InputError
                                        :message="form.errors.client_id"
                                    />
                                </label>

                                <label class="min-w-0 space-y-2">
                                    <span
                                        class="text-sm font-medium text-foreground"
                                        >Nomor Kontrak</span
                                    >
                                    <Input
                                        v-model="form.contract_number"
                                        placeholder="Nomor kontrak"
                                    />
                                    <InputError
                                        :message="form.errors.contract_number"
                                    />
                                </label>

                                <label class="min-w-0 space-y-2">
                                    <span
                                        class="text-sm font-medium text-foreground"
                                        >Nilai Kontrak</span
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
                                        >Nama Lokasi</span
                                    >
                                    <Input
                                        v-model="form.location"
                                        class="min-w-0"
                                        placeholder="Area umum proyek"
                                    />
                                    <InputError
                                        :message="form.errors.location"
                                    />
                                </label>

                                <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                                    <label class="min-w-0 space-y-2">
                                        <span
                                            class="text-sm font-medium text-foreground"
                                            >Tanggal Mulai</span
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
                                            >Tanggal Selesai</span
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
                                        >Status Proyek</span
                                    >
                                    <Select v-model="form.status">
                                        <SelectTrigger class="w-full">
                                            <SelectValue
                                                placeholder="Pilih status"
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
                                        >Status Pembayaran</span
                                    >
                                    <Select v-model="form.payment_status">
                                        <SelectTrigger class="w-full">
                                            <SelectValue
                                                placeholder="Pilih status pembayaran"
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
                    </div>
                </div>

                <ProjectRelatedDataSection
                    :connection-options="props.documentConnections"
                    :documents="props.uploadedDocuments"
                    :groups="props.documentGroups"
                    :is-create-mode="isCreateMode"
                    :project-id="props.project.id"
                    @create="openGroupCreate"
                />

                <ProjectDocumentCreateDialog
                    :group="activeDocumentGroup"
                    :open="isDocumentCreateOpen"
                    :project-id="props.project.id"
                    @update:open="setDocumentCreateOpen"
                />

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
                            Batal
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
                                isCreateMode ? 'Tambah Proyek' : 'Simpan Perubahan'
                            }}
                        </Button>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
.leaflet-container {
    z-index: 10 !important;
}
</style>
