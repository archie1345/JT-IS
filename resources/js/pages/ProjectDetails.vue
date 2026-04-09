<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    ArrowLeft,
    CalendarDays,
    CircleAlert,
    FileText,
    FolderOpen,
    Gauge,
    Plus,
    Save,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
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
    DocumentItem,
    Mode,
    PaymentStatus,
    ProgressSnapshot,
    ProjectDetails,
    ProjectStatus,
    UploadedDocument,
} from '@/types/project';


const props = defineProps<{
    mode: Mode;
    project: ProjectDetails;
    clients: ClientOption[];
    documents: DocumentItem[];
    progress: ProgressSnapshot;
    recentReport: {
        date: string | null;
        description: string | null;
        percent: number | null;
    } | null;
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
        href: isCreateMode.value ? '/projects/create' : `/projects/${props.project.id}`,
    },
]);

const projectStatusOptions: Array<{ value: ProjectStatus; label: string; hint: string }> = [
    { value: 'planning', label: 'Planning', hint: 'Initial scope and approvals' },
    { value: 'ongoing', label: 'Ongoing', hint: 'Work is actively running' },
    { value: 'completed', label: 'Completed', hint: 'Delivery and handover done' },
];

const paymentStatusOptions: Array<{ value: PaymentStatus; label: string; hint: string }> = [
    { value: 'pending', label: 'Pending', hint: 'Waiting for billing progress' },
    { value: 'partial', label: 'Partial', hint: 'Some payment has been received' },
    { value: 'paid', label: 'Paid', hint: 'Invoice is fully settled' },
    { value: 'overdue', label: 'Overdue', hint: 'Payment is late' },
];

const form = useForm({
    client_id: props.project.clientId
        ? String(props.project.clientId)
        : (props.clients[0]?.id ? String(props.clients[0].id) : ''),
    name: props.project.name,
    contract_number: props.project.contractNumber ?? '',
    contract_value: props.project.contractValue || 0,
    start_date: props.project.startDate ?? '',
    end_date: props.project.endDate ?? '',
    location: props.project.location ?? '',
    status: props.project.status,
    payment_status: props.project.paymentStatus,
    progress_percent: props.project.latestProgressPercent ?? 0,
    progress_note: props.project.latestProgressNote ?? '',
});

const documentForm = useForm({
    documents: [] as File[],
});
const documentInput = ref<HTMLInputElement | null>(null);

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
        completed: 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-500/25',
    })[status];

const getPaymentStatusClass = (status: PaymentStatus) =>
    ({
        pending: 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-500/25',
        partial: 'bg-sky-500/15 text-sky-600 ring-1 ring-sky-500/25',
        paid: 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-500/25',
        overdue: 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-500/25',
    })[status];

const formatProjectStatus = (status: ProjectStatus) =>
    projectStatusOptions.find((option) => option.value === status)?.label ?? status;

const formatPaymentStatus = (status: PaymentStatus) =>
    paymentStatusOptions.find((option) => option.value === status)?.label ?? status;

const selectedClient = computed(
    () => props.clients.find((client) => String(client.id) === String(form.client_id)) ?? null,
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
        Math.min(100, Math.round((reportScore * 0.55) + (projectScore * 0.25) + (paymentScore * 0.2))),
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

const handleDocumentChange = (event: Event) => {
    const target = event.target;

    if (!(target instanceof HTMLInputElement)) return;

    documentForm.documents = Array.from(target.files ?? []);
};

const uploadDocuments = () => {
    if (!props.project.id || documentForm.documents.length === 0) return;

    documentForm.post(`/projects/${props.project.id}/documents`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            documentForm.reset('documents');

            if (documentInput.value) {
                documentInput.value.value = '';
            }
        },
    });
};

const openDocument = (document: DocumentItem) => {
    if (!document.url) return;
    router.visit(document.url);
};
</script>

<template>
    <Head :title="isCreateMode ? 'Create Project' : `Project Detail - ${props.project.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex min-h-[calc(100vh-8rem)] flex-1 flex-col gap-4 rounded-xl p-4">
            <section class="flex min-h-0 flex-1 flex-col gap-4 rounded-2xl border border-sidebar-border/70 bg-background/80 p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <Button variant="ghost" class="mb-3 pl-0 text-muted-foreground" @click="backToProjects">
                            <ArrowLeft class="mr-2 size-4" />
                            Back to Projects
                        </Button>
                        <h1 class="text-3xl font-semibold tracking-tight text-foreground">
                            {{ isCreateMode ? 'Create Project' : 'Project Detail' }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ isCreateMode ? 'Fill in the project data and save it to the database.' : 'Edit the project details and save your updates to the database.' }}
                        </p>
                    </div>

                    <Badge :class="getProjectStatusClass(form.status)">
                        {{ formatProjectStatus(form.status) }}
                    </Badge>
                </div>

                <div class="grid min-h-0 flex-1 gap-4 lg:grid-cols-[1.3fr_0.95fr]">
                    <div class="flex min-h-0 flex-col gap-4">
                        <div class="rounded-2xl border border-sidebar-border/70 bg-background p-5 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">Project Title</p>
                                    <Input
                                        v-model="form.name"
                                        class="mt-2 max-w-2xl text-2xl font-semibold"
                                        placeholder="Project name"
                                    />
                                    <InputError :message="form.errors.name" class="mt-2" />
                                    <p class="mt-2 text-sm text-muted-foreground">
                                        {{ selectedClient?.name ?? 'Choose a client to connect this project.' }}
                                    </p>
                                </div>

                                <div class="rounded-2xl border border-sidebar-border/70 bg-muted/30 px-4 py-3">
                                    <p class="text-xs text-muted-foreground">Overall Progress</p>
                                    <p class="text-3xl font-semibold text-foreground">{{ liveProgressScore }}%</p>
                                    <p class="text-xs text-muted-foreground">{{ progressLabel }}</p>
                                </div>
                            </div>

                            <div class="mt-5">
                                <div class="mb-2 flex items-center justify-between text-xs text-muted-foreground">
                                    <span>Progress snapshot</span>
                                    <span>{{ form.progress_percent }}% report progress</span>
                                </div>
                                <div class="h-3 overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full rounded-full transition-all"
                                        :class="progressToneClass"
                                        :style="{ width: `${liveProgressScore}%` }"
                                    />
                                </div>
                            </div>

                            <div class="mt-5 grid gap-4 md:grid-cols-2">
                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Client</p>
                                    <p class="mt-1 text-sm font-medium text-foreground">{{ selectedClient?.name ?? '-' }}</p>
                                    <p class="text-xs text-muted-foreground">{{ selectedClient?.contact ?? '-' }}</p>
                                </div>

                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Contract No.</p>
                                    <p class="mt-1 text-sm font-medium text-foreground">{{ form.contract_number || '-' }}</p>
                                </div>

                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Value</p>
                                    <p class="mt-1 text-sm font-medium text-foreground">{{ formatCurrency(form.contract_value || 0) }}</p>
                                </div>

                                <div class="rounded-xl bg-muted/30 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Location</p>
                                    <p class="mt-1 text-sm font-medium text-foreground">{{ form.location || '-' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-sidebar-border/70 bg-background p-5 shadow-sm">
                            <div class="mb-4 flex items-center gap-2">
                                <Gauge class="size-4 text-muted-foreground" />
                                <h3 class="text-lg font-semibold text-foreground">Progress Summary</h3>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <div class="mb-1 flex items-center justify-between text-sm">
                                        <span class="text-muted-foreground">Report progress</span>
                                        <span class="font-medium text-foreground">{{ form.progress_percent }}%</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-muted">
                                        <div class="h-full rounded-full bg-sky-500" :style="{ width: `${form.progress_percent}%` }" />
                                    </div>
                                </div>

                                <div>
                                    <div class="mb-1 flex items-center justify-between text-sm">
                                        <span class="text-muted-foreground">Project status score</span>
                                        <span class="font-medium text-foreground">{{ props.progress.projectStatusScore }}%</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-muted">
                                        <div class="h-full rounded-full bg-blue-500" :style="{ width: `${props.progress.projectStatusScore}%` }" />
                                    </div>
                                </div>

                                <div>
                                    <div class="mb-1 flex items-center justify-between text-sm">
                                        <span class="text-muted-foreground">Payment status score</span>
                                        <span class="font-medium text-foreground">{{ props.progress.paymentStatusScore }}%</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-muted">
                                        <div class="h-full rounded-full bg-emerald-500" :style="{ width: `${props.progress.paymentStatusScore}%` }" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-sidebar-border/70 bg-background p-5 shadow-sm">
                            <div class="mb-4 flex items-center gap-2">
                                <CalendarDays class="size-4 text-muted-foreground" />
                                <h3 class="text-lg font-semibold text-foreground">Recent Update</h3>
                            </div>

                            <div v-if="props.recentReport" class="space-y-2">
                                <p class="text-sm font-medium text-foreground">
                                    {{ props.recentReport.date ?? '-' }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{ props.recentReport.description ?? 'No report note available.' }}
                                </p>
                                <Badge variant="secondary">
                                    Progress: {{ props.recentReport.percent ?? 0 }}%
                                </Badge>
                            </div>
                            <div v-else class="flex items-start gap-2 text-sm text-muted-foreground">
                                <CircleAlert class="mt-0.5 size-4 shrink-0" />
                                No progress report has been submitted yet.
                            </div>
                        </div>
                    </div>

                    <div class="flex min-h-0 flex-col gap-4">
                        <div class="rounded-2xl border border-sidebar-border/70 bg-background p-5 shadow-sm">
                            <div class="mb-4 flex items-center gap-2">
                                <FileText class="size-4 text-muted-foreground" />
                                <h3 class="text-lg font-semibold text-foreground">Settings</h3>
                            </div>

                            <div class="grid gap-4">
                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-foreground">Client</span>
                                    <Select v-model="form.client_id">
                                        <SelectTrigger class="w-full">
                                            <SelectValue placeholder="Choose a client" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="client in props.clients"
                                                :key="client.id"
                                                :value="String(client.id)"
                                            >
                                                {{ client.name ?? `Client #${client.id}` }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="form.errors.client_id" />
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-foreground">Contract Number</span>
                                    <Input v-model="form.contract_number" placeholder="Contract number" />
                                    <InputError :message="form.errors.contract_number" />
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-foreground">Contract Value</span>
                                    <Input v-model.number="form.contract_value" type="number" min="0" step="0.01" />
                                    <InputError :message="form.errors.contract_value" />
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-foreground">Location</span>
                                    <Input v-model="form.location" placeholder="Project location" />
                                    <InputError :message="form.errors.location" />
                                </label>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="space-y-2">
                                        <span class="text-sm font-medium text-foreground">Start Date</span>
                                        <Input v-model="form.start_date" type="date" />
                                        <InputError :message="form.errors.start_date" />
                                    </label>

                                    <label class="space-y-2">
                                        <span class="text-sm font-medium text-foreground">End Date</span>
                                        <Input v-model="form.end_date" type="date" />
                                        <InputError :message="form.errors.end_date" />
                                    </label>
                                </div>

                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-foreground">Project Status</span>
                                    <Select v-model="form.status">
                                        <SelectTrigger class="w-full">
                                            <SelectValue placeholder="Choose a status" />
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
                                        {{ projectStatusOptions.find((item) => item.value === form.status)?.hint }}
                                    </p>
                                    <InputError :message="form.errors.status" />
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-foreground">Payment Status</span>
                                    <Select v-model="form.payment_status">
                                        <SelectTrigger class="w-full">
                                            <SelectValue placeholder="Choose a payment status" />
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
                                        {{ paymentStatusOptions.find((item) => item.value === form.payment_status)?.hint }}
                                    </p>
                                    <InputError :message="form.errors.payment_status" />
                                </label>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-sidebar-border/70 bg-background p-5 shadow-sm">
                            <div class="mb-4 flex items-center gap-2">
                                <FolderOpen class="size-4 text-muted-foreground" />
                                <h3 class="text-lg font-semibold text-foreground">Project Documents</h3>
                            </div>

                            <div v-if="isCreateMode" class="rounded-xl border border-dashed border-sidebar-border/70 bg-muted/20 p-4 text-sm text-muted-foreground">
                                Save the project first, then upload documents here.
                            </div>
                            <div v-else class="space-y-4">
                                <div class="rounded-xl border border-sidebar-border/70 bg-muted/20 p-4">
                                    <label class="block space-y-2">
                                        <span class="text-sm font-medium text-foreground">Upload documents</span>
                                        <input
                                            ref="documentInput"
                                            type="file"
                                            multiple
                                            class="block w-full rounded-md border border-sidebar-border/70 bg-background px-3 py-2 text-sm text-foreground file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-sm file:font-medium"
                                            @change="handleDocumentChange"
                                        >
                                    </label>

                                    <div class="mt-3 flex items-center justify-between gap-3">
                                        <p class="text-xs text-muted-foreground">
                                            {{ documentForm.documents.length }} file(s) selected
                                        </p>
                                        <Button type="button" :disabled="documentForm.processing || documentForm.documents.length === 0" @click="uploadDocuments">
                                            <Plus class="mr-2 size-4" />
                                            Upload
                                        </Button>
                                    </div>

                                    <InputError :message="documentForm.errors.documents" class="mt-2" />
                                </div>

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
                                            <p class="text-sm font-medium text-foreground">{{ document.label }}</p>
                                            <p class="text-xs text-muted-foreground">{{ document.detail }}</p>
                                        </div>
                                        <Badge
                                            :variant="document.status === 'available' ? 'default' : 'outline'"
                                            class="shrink-0"
                                        >
                                            {{ document.status }}
                                        </Badge>
                                    </button>

                                    <div v-if="props.documents.length === 0" class="rounded-xl border border-dashed border-sidebar-border/70 bg-muted/20 p-4 text-sm text-muted-foreground">
                                        No system documents linked yet.
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="doc in props.uploadedDocuments"
                                        :key="doc.id"
                                        class="flex items-start justify-between gap-4 rounded-xl border border-sidebar-border/70 bg-background px-4 py-3"
                                    >
                                        <div class="min-w-0">
                                            <a :href="doc.url" target="_blank" class="block truncate text-sm font-medium text-foreground hover:underline">
                                                {{ doc.originalName }}
                                            </a>
                                            <p class="text-xs text-muted-foreground">
                                                {{ doc.createdAt ?? '-' }} | {{ doc.mimeType ?? 'unknown type' }}
                                            </p>
                                        </div>
                                        <Badge variant="secondary" class="shrink-0">
                                            {{ doc.size ? Math.round(doc.size / 1024) + ' KB' : 'file' }}
                                        </Badge>
                                    </div>

                                    <div v-if="props.uploadedDocuments.length === 0" class="rounded-xl border border-dashed border-sidebar-border/70 bg-muted/20 p-4 text-sm text-muted-foreground">
                                        No documents uploaded yet.
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="rounded-2xl border border-sidebar-border/70 bg-background p-4 shadow-sm">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <Button variant="outline" type="button" class="w-full" @click="backToProjects">
                            Cancel
                        </Button>
                        <Button type="button" class="w-full" :disabled="form.processing" @click="submit">
                            <component :is="isCreateMode ? Plus : Save" class="mr-2 size-4" />
                            {{ isCreateMode ? 'Create Project' : 'Save Changes' }}
                        </Button>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
