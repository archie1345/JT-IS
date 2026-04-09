<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ArrowLeft, CalendarDays, FileText, FolderOpen, Save, Plus } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
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
    { title: 'Clients', href: '/client' },
    {
        title: isCreateMode.value ? 'Create Client' : props.client.name,
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
    if (progressScore.value >= 90) return 'Strong account';
    if (progressScore.value >= 60) return 'Healthy activity';
    if (progressScore.value >= 30) return 'Some active work';
    return 'New or quiet account';
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
    <Head :title="isCreateMode ? 'Create Client' : `Client Detail - ${props.client.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex min-h-[calc(100vh-8rem)] flex-1 flex-col gap-4 rounded-xl p-4">
            <section class="flex min-h-0 flex-1 flex-col gap-4 rounded-2xl border border-sidebar-border/70 bg-background/80 p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <Button variant="ghost" class="mb-3 pl-0 text-muted-foreground" @click="backToClients">
                            <ArrowLeft class="mr-2 size-4" />
                            Back to Clients
                        </Button>
                        <h1 class="text-3xl font-semibold tracking-tight text-foreground">
                            {{ isCreateMode ? 'Create Client' : 'Client Detail' }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ isCreateMode ? 'Create a new client profile and save it to the database.' : 'Edit the client profile and review the projects connected to this account.' }}
                        </p>
                    </div>

                    <Badge class="bg-blue-500/15 text-blue-600 ring-1 ring-blue-500/25">
                        {{ isCreateMode ? 'New' : `${props.client.projectCount} Projects` }}
                    </Badge>
                </div>

                <div class="grid min-h-0 flex-1 gap-4 lg:grid-cols-[1.3fr_0.95fr]">
                    <div class="flex min-h-0 flex-col gap-4">
                        <div class="rounded-2xl border border-sidebar-border/70 bg-background p-5 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                                        {{ isCreateMode ? 'New Client Name' : 'Client Name' }}
                                    </p>
                                    <Input
                                        v-model="form.name"
                                        class="mt-2 max-w-2xl text-2xl font-semibold"
                                        placeholder="Client name"
                                    />
                                    <InputError :message="form.errors.name" class="mt-2" />
                                    <p class="mt-2 text-sm text-muted-foreground">
                                        {{ form.contact || (isCreateMode ? 'Add the main contact person or email before saving.' : 'Add the main contact person or email.') }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5">
                                <div class="mb-2 flex items-center justify-between text-xs text-muted-foreground">
                                    <span>Account activity</span>
                                    <span v-if="!isCreateMode">{{ props.client.activeProjects }} active / {{ props.client.completedProjects }} completed</span>
                                    <span v-else>New client profile</span>
                                </div>
                                <div class="h-3 overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full rounded-full transition-all"
                                        :class="progressToneClass"
                                        :style="{ width: `${progressScore}%` }"
                                    />
                                </div>
                            </div>

                            <div class="mt-5 grid gap-4 md:grid-cols-2">
                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Primary Contact</p>
                                    <p class="mt-1 text-sm font-medium text-foreground">{{ form.contact || '-' }}</p>
                                </div>

                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Projects</p>
                                    <p class="mt-1 text-sm font-medium text-foreground">{{ props.client.projectCount }}</p>
                                </div>

                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">First Project</p>
                                    <p class="mt-1 text-sm font-medium text-foreground">{{ formatDate(props.client.firstProjectDate) }}</p>
                                </div>

                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Latest Project</p>
                                    <p class="mt-1 text-sm font-medium text-foreground">{{ formatDate(props.client.lastProjectDate) }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="!isCreateMode" class="rounded-2xl border border-sidebar-border/70 bg-background p-5 shadow-sm">
                            <div class="mb-4 flex items-center gap-2">
                                <FolderOpen class="size-4 text-muted-foreground" />
                                <h3 class="text-lg font-semibold text-foreground">Related Projects</h3>
                            </div>

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
                                        {{ project.status }}
                                    </Badge>
                                </button>

                                <div v-if="props.projects.length === 0" class="rounded-xl border border-dashed border-sidebar-border/70 bg-muted/20 p-4 text-sm text-muted-foreground">
                                    No projects linked to this client yet.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex min-h-0 flex-col gap-4">
                        <div class="rounded-2xl border border-sidebar-border/70 bg-background p-5 shadow-sm">
                            <div class="mb-4 flex items-center gap-2">
                                <FileText class="size-4 text-muted-foreground" />
                                <h3 class="text-lg font-semibold text-foreground">{{ isCreateMode ? 'Create Settings' : 'Client Settings' }}</h3>
                            </div>

                            <div class="grid gap-4">
                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-foreground">Client Name</span>
                                    <Input v-model="form.name" placeholder="Client name" />
                                    <InputError :message="form.errors.name" />
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-foreground">Contact</span>
                                    <Input v-model="form.contact" placeholder="Email or phone" />
                                    <InputError :message="form.errors.contact" />
                                </label>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-sidebar-border/70 bg-background p-5 shadow-sm">
                            <div class="mb-4 flex items-center gap-2">
                                <CalendarDays class="size-4 text-muted-foreground" />
                                <h3 class="text-lg font-semibold text-foreground">Date</h3>
                            </div>

                            <div class="space-y-4 text-sm">
                                <div v-if="isCreateMode" class="rounded-xl bg-muted/30 p-4 text-muted-foreground">
                                    Dates and activity will appear after the client is saved and linked to projects.
                                </div>
                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">First Project Date</p>
                                    <p class="mt-1 font-medium text-foreground">{{ formatDate(props.client.firstProjectDate) }}</p>
                                </div>
                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Latest Project Date</p>
                                    <p class="mt-1 font-medium text-foreground">{{ formatDate(props.client.lastProjectDate) }}</p>
                                </div>
                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Last Updated</p>
                                    <p class="mt-1 font-medium text-foreground">{{ formatDate(props.client.lastUpdated) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-sidebar-border/70 bg-background p-5 shadow-sm">
                            <div class="mb-4 flex items-center gap-2">
                                <Save class="size-4 text-muted-foreground" />
                                <h3 class="text-lg font-semibold text-foreground">{{ isCreateMode ? 'Save Client' : 'Quick Links' }}</h3>
                            </div>

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
                                    <Badge variant="secondary" class="shrink-0">Open</Badge>
                                </button>
                            </div>
                            <p v-else class="text-sm text-muted-foreground">
                                Save the client first to unlock related project and invoice links.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-sidebar-border/70 bg-background p-4 shadow-sm">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <Button variant="outline" type="button" class="w-full" @click="backToClients">
                            Cancel
                        </Button>
                        <Button type="button" class="w-full" :disabled="form.processing" @click="submit">
                            <Plus v-if="isCreateMode" class="mr-2 size-4" />
                            <Save v-else class="mr-2 size-4" />
                            {{ isCreateMode ? 'Create Client' : 'Save Changes' }}
                        </Button>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
