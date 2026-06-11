<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ArrowLeft, CalendarDays, FileText, FolderOpen, Save, Plus } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import EntityDetailHero from '@/components/entity/EntityDetailHero.vue';
import EntityPageSection from '@/components/entity/EntityPageSection.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem } from '@/types';
import type { ClientDetail, ClientMode, QuickLink, RelatedProject } from '@/types/client';

const props = defineProps<{
    mode: ClientMode;
    client: ClientDetail;
    projects: RelatedProject[];
    quickLinks: QuickLink[];
}>();

const isCreateMode = computed(() => props.mode === 'create');

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Klien', href: '/client' },
    {
        title: isCreateMode.value ? 'Tambah Klien' : props.client.name,
        href: isCreateMode.value ? '/client/create' : `/client/${props.client.id}`,
    },
]);

const form = useForm({
    name: props.client.name ?? '',
    contact: props.client.contact ?? '',
});

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const formatDate = (value: string | null) => value || '-';

const getProjectStatusClass = (status: RelatedProject['status']) =>
    ({
        planning: 'bg-slate-500/15 text-slate-600 ring-1 ring-slate-500/25',
        ongoing: 'bg-blue-500/15 text-blue-600 ring-1 ring-blue-500/25',
        completed: 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-500/25',
    })[status];

const formatProjectStatus = (status: RelatedProject['status']) =>
    ({
        planning: 'Perencanaan',
        ongoing: 'Berjalan',
        completed: 'Selesai',
    })[status] ?? status;

const progressScore = computed(() => {
    if (props.client.projectCount === 0) return 0;

    return Math.min(
        100,
        Math.round(
            (props.client.activeProjects / props.client.projectCount) * 60 +
            (props.client.completedProjects / props.client.projectCount) * 40,
        ),
    );
});

const progressToneClass = computed(() => {
    if (progressScore.value >= 90) return 'bg-emerald-500';
    if (progressScore.value >= 60) return 'bg-blue-500';
    if (progressScore.value >= 30) return 'bg-amber-500';
    return 'bg-slate-500';
});

const progressLabel = computed(() => {
    if (progressScore.value >= 90) return 'Akun kuat';
    if (progressScore.value >= 60) return 'Aktivitas sehat';
    if (progressScore.value >= 30) return 'Ada pekerjaan aktif';
    return 'Akun baru atau pasif';
});

const backToClients = () => {
    router.get('/client');
};

const openProject = (projectId: number) => {
    router.get(`/projects/${projectId}`);
};

const openQuickLink = (url: string) => {
    router.visit(url);
};

const submit = () => {
    if (isCreateMode.value) {
        form.post('/client', { preserveScroll: true });
        return;
    }

    form.patch(`/client/${props.client.id}`, { preserveScroll: true });
};
</script>

<template>
    <Head :title="isCreateMode ? 'Tambah Klien' : `Detail Klien - ${props.client.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex min-h-[calc(100vh-8rem)] flex-1 flex-col gap-4 rounded-xl p-4">
            <section class="flex min-h-0 flex-1 flex-col gap-4 rounded-2xl border border-sidebar-border/70 bg-background/80 p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <Button variant="ghost" class="mb-3 pl-0 text-muted-foreground" @click="backToClients">
                            <ArrowLeft class="mr-2 size-4" />
                            Kembali ke Klien
                        </Button>
                        <h1 class="text-3xl font-semibold tracking-tight text-foreground">
                            {{ isCreateMode ? 'Tambah Klien' : 'Detail Klien' }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ isCreateMode ? 'Buat profil klien baru dan simpan ke database.' : 'Edit profil klien dan review proyek yang terhubung ke akun ini.' }}
                        </p>
                    </div>

                    <Badge class="bg-blue-500/15 text-blue-600 ring-1 ring-blue-500/25">
                        {{ isCreateMode ? 'Baru' : `${props.client.projectCount} Proyek` }}
                    </Badge>
                </div>

                <div class="grid min-h-0 flex-1 gap-4 lg:grid-cols-[1.3fr_0.95fr]">
                    <div class="flex min-h-0 flex-col gap-4">
                        <EntityDetailHero
                            back-label="Kembali ke Klien"
                            :title="isCreateMode ? 'Tambah Klien' : 'Detail Klien'"
                            :description="isCreateMode ? 'Buat profil klien baru dan simpan ke database.' : 'Edit profil klien dan review proyek yang terhubung ke akun ini.'"
                            :badge-text="isCreateMode ? 'Baru' : `${props.client.projectCount} Proyek`"
                            badge-class="bg-blue-500/15 text-blue-600 ring-1 ring-blue-500/25"
                            :title-prefix="isCreateMode ? 'Nama Klien Baru' : 'Nama Klien'"
                            metric-label="Aktivitas akun"
                            :metric-value="`${progressScore}%`"
                            :metric-description="progressLabel"
                            progress-label="Aktivitas akun"
                            :progress-value="isCreateMode ? 'Profil klien baru' : `${props.client.activeProjects} aktif / ${props.client.completedProjects} selesai`"
                            :progress-bar-value="progressScore"
                            :progress-tone-class="progressToneClass"
                            @back="backToClients"
                        >
                            <template #back>
                                <Button variant="ghost" class="mb-3 pl-0 text-muted-foreground" @click="backToClients">
                                    <ArrowLeft class="mr-2 size-4" />
                                    Kembali ke Klien
                                </Button>
                            </template>
                            <template #title-input>
                                <Input
                                    v-model="form.name"
                                    class="mt-2 max-w-2xl text-2xl font-semibold"
                                    placeholder="Nama klien"
                                />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </template>
                            <template #title-meta>
                                <p class="mt-2 text-sm text-muted-foreground">
                                    {{ form.contact || (isCreateMode ? 'Tambahkan kontak utama atau email sebelum menyimpan.' : 'Tambahkan kontak utama atau email.') }}
                                </p>
                            </template>
                            <template #summary>
                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Kontak Utama</p>
                                    <p class="mt-1 text-sm font-medium text-foreground">{{ form.contact || '-' }}</p>
                                </div>

                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Proyek</p>
                                    <p class="mt-1 text-sm font-medium text-foreground">{{ props.client.projectCount }}</p>
                                </div>

                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Proyek Pertama</p>
                                    <p class="mt-1 text-sm font-medium text-foreground">{{ formatDate(props.client.firstProjectDate) }}</p>
                                </div>

                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Proyek Terbaru</p>
                                    <p class="mt-1 text-sm font-medium text-foreground">{{ formatDate(props.client.lastProjectDate) }}</p>
                                </div>
                            </template>
                        </EntityDetailHero>

                        <EntityPageSection v-if="!isCreateMode" title="Proyek Terkait" :icon="FolderOpen">
                            <div class="space-y-3">
                                <button
                                    v-for="project in props.projects"
                                    :key="project.id"
                                    type="button"
                                    class="flex w-full items-start justify-between gap-4 rounded-xl border border-sidebar-border/70 bg-background px-4 py-3 text-left transition hover:bg-muted/40"
                                    @click="openProject(project.id)"
                                >
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-foreground">{{ project.name }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ project.contractNumber ?? '-' }} | {{ formatCurrency(project.contractValue) }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDate(project.startDate) }} - {{ formatDate(project.endDate) }}
                                        </p>
                                    </div>
                                    <Badge :class="getProjectStatusClass(project.status)" class="shrink-0">
                                        {{ formatProjectStatus(project.status) }}
                                    </Badge>
                                </button>

                                <div v-if="props.projects.length === 0" class="rounded-xl border border-dashed border-sidebar-border/70 bg-muted/20 p-4 text-sm text-muted-foreground">
                                    Belum ada proyek yang terhubung ke klien ini.
                                </div>
                            </div>
                        </EntityPageSection>
                    </div>

                    <div class="flex min-h-0 flex-col gap-4">
                        <EntityPageSection :title="isCreateMode ? 'Pengaturan Pembuatan' : 'Pengaturan Klien'" :icon="FileText">
                            <div class="grid gap-4">
                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-foreground">Nama Klien</span>
                                    <Input v-model="form.name" placeholder="Nama klien" />
                                    <InputError :message="form.errors.name" />
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-foreground">Kontak</span>
                                    <Input v-model="form.contact" placeholder="Email atau telepon" />
                                    <InputError :message="form.errors.contact" />
                                </label>
                            </div>
                        </EntityPageSection>

                        <EntityPageSection title="Tanggal" :icon="CalendarDays">
                            <div class="space-y-4 text-sm">
                                <div v-if="isCreateMode" class="rounded-xl bg-muted/30 p-4 text-muted-foreground">
                                    Tanggal dan aktivitas akan muncul setelah klien disimpan dan dihubungkan ke proyek.
                                </div>
                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Tanggal Proyek Pertama</p>
                                    <p class="mt-1 font-medium text-foreground">{{ formatDate(props.client.firstProjectDate) }}</p>
                                </div>
                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Tanggal Proyek Terbaru</p>
                                    <p class="mt-1 font-medium text-foreground">{{ formatDate(props.client.lastProjectDate) }}</p>
                                </div>
                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Terakhir Diupdate</p>
                                    <p class="mt-1 font-medium text-foreground">{{ formatDate(props.client.lastUpdated) }}</p>
                                </div>
                            </div>
                        </EntityPageSection>

                        <EntityPageSection :title="isCreateMode ? 'Simpan Klien' : 'Link Cepat'" :icon="Save">
                            <div v-if="!isCreateMode" class="space-y-3">
                                <button
                                    v-for="link in props.quickLinks"
                                    :key="link.label"
                                    type="button"
                                    class="flex w-full items-center justify-between gap-4 rounded-xl border border-sidebar-border/70 bg-muted/20 px-4 py-3 text-left transition hover:bg-muted/40"
                                    @click="openQuickLink(link.url)"
                                >
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-foreground">{{ link.label }}</p>
                                        <p class="text-xs text-muted-foreground">{{ link.detail }}</p>
                                    </div>
                                    <Badge variant="secondary" class="shrink-0">Buka</Badge>
                                </button>
                            </div>
                            <p v-else class="text-sm text-muted-foreground">
                                Simpan klien terlebih dahulu untuk membuka link proyek dan invoice terkait.
                            </p>
                        </EntityPageSection>
                    </div>
                </div>

                <div class="rounded-2xl border border-sidebar-border/70 bg-background p-4 shadow-sm">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <Button variant="outline" type="button" class="w-full" @click="backToClients">
                            Batal
                        </Button>
                        <Button type="button" class="w-full" :disabled="form.processing" @click="submit">
                            <Plus v-if="isCreateMode" class="mr-2 size-4" />
                            <Save v-else class="mr-2 size-4" />
                            {{ isCreateMode ? 'Tambah Klien' : 'Simpan Perubahan' }}
                        </Button>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
