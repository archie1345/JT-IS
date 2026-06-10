<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { ProjectDocumentGroup } from '@/types/project';
import { toast } from 'vue-sonner';

const props = defineProps<{
    group: ProjectDocumentGroup | null;
    open: boolean;
    projectId: number | null;
}>();

const emit = defineEmits<{
    'update:open': [open: boolean];
}>();

type DocumentCreateKind = NonNullable<ProjectDocumentGroup['createKind']>;

type DocumentCreateForm = {
    project_id: string;
    document_number: string;
    document_date: string;
    notes: string;
    document_type: string;
    progress_percent: string;
    period_start: string;
    period_end: string;
    report_date: string;
    description: string;
    invoice_number: string;
    amount: string;
    tax_amount: string;
    invoice_date: string;
    due_date: string;
    status: string;
    reference_number: string;
    category: string;
    vendor: string;
    date: string;
    title: string;
    owner: string;
    value: string;
};

const documentFormDefaults = (): DocumentCreateForm => ({
    project_id: props.projectId ? String(props.projectId) : '',
    document_number: '',
    document_date: '',
    notes: '',
    document_type: '',
    progress_percent: '',
    period_start: '',
    period_end: '',
    report_date: '',
    description: '',
    invoice_number: '',
    amount: '',
    tax_amount: '',
    invoice_date: '',
    due_date: '',
    status: '',
    reference_number: '',
    category: '',
    vendor: '',
    date: '',
    title: '',
    owner: '',
    value: '',
});

const documentForm = useForm<DocumentCreateForm>(documentFormDefaults());

const activeCreateKind = computed(() => props.group?.createKind ?? null);

const documentCreateTitle = computed(() =>
    props.group ? `Tambah ${props.group.label}` : 'Tambah Dokumen',
);

const documentCreateDescription = computed(() => {
    switch (activeCreateKind.value) {
        case 'rab':
            return 'Buat RAB baru untuk proyek ini. Item anggaran ditambahkan setelah dokumen dibuat.';
        case 'rap':
            return 'Buat RAP baru untuk proyek ini. Rincian item ditambahkan dari halaman detail RAP.';
        case 'progress_report':
            return 'Catat progress atau BAMC terbaru untuk proyek ini.';
        case 'invoice':
            return 'Buat tagihan baru yang terhubung ke proyek ini.';
        case 'project_cost':
            return 'Catat realisasi biaya atau bukti pengeluaran proyek.';
        case 'pipeline':
            return 'Tambahkan laporan, tender, atau catatan pipeline proyek.';
        case 'fund_request':
            return 'Buat pengajuan dana untuk proyek ini.';
        default:
            return 'Tambahkan data baru yang terhubung ke proyek ini.';
    }
});

const resetDocumentForm = () => {
    Object.assign(documentForm, documentFormDefaults());
    documentForm.clearErrors();
};

watch(
    () => [props.open, props.group?.key, props.projectId],
    () => resetDocumentForm(),
);

const setOpen = (open: boolean) => {
    emit('update:open', open);

    if (!open) {
        resetDocumentForm();
    }
};

const documentCreateEndpoint = (kind: DocumentCreateKind) => {
    if (kind === 'rab') return `/projects/${props.projectId}/rabs`;
    if (kind === 'rap') return `/projects/${props.projectId}/raps`;
    if (kind === 'progress_report') return '/progress-updates';
    if (kind === 'invoice') return '/invoices';
    if (kind === 'project_cost') return '/project-costs';
    if (kind === 'pipeline') return '/pipeline';
    return '/fund-requests';
};

const documentCreatePayload = (kind: DocumentCreateKind) => {
    const base = { project_id: String(props.projectId) };

    if (kind === 'rab' || kind === 'rap') {
        return {
            ...base,
            document_number: documentForm.document_number,
            document_date: documentForm.document_date,
            notes: documentForm.notes,
        };
    }

    if (kind === 'progress_report') {
        return {
            ...base,
            document_number: documentForm.document_number,
            document_type: documentForm.document_type,
            progress_percent: documentForm.progress_percent,
            period_start: documentForm.period_start,
            period_end: documentForm.period_end,
            report_date: documentForm.report_date,
            description: documentForm.description,
        };
    }

    if (kind === 'invoice') {
        return {
            ...base,
            invoice_number: documentForm.invoice_number,
            amount: documentForm.amount,
            tax_amount: documentForm.tax_amount,
            invoice_date: documentForm.invoice_date,
            due_date: documentForm.due_date,
            status: documentForm.status,
            description: documentForm.description,
        };
    }

    if (kind === 'project_cost') {
        return {
            ...base,
            reference_number: documentForm.reference_number,
            category: documentForm.category,
            vendor: documentForm.vendor,
            amount: documentForm.amount,
            date: documentForm.date,
            description: documentForm.description,
        };
    }

    if (kind === 'pipeline') {
        return {
            ...base,
            title: documentForm.title,
            document_number: documentForm.document_number,
            document_date: documentForm.document_date,
            owner: documentForm.owner,
            value: documentForm.value,
            status: documentForm.status,
            notes: documentForm.notes,
        };
    }

    return {
        ...base,
        amount: documentForm.amount,
    };
};

const submitDocumentCreate = () => {
    const kind = activeCreateKind.value;

    if (!props.projectId || !kind) return;

    documentForm
        .transform(() => documentCreatePayload(kind))
        .post(documentCreateEndpoint(kind), {
            preserveScroll: true,
            onSuccess: () => {
                const label = props.group?.label ?? 'Dokumen';

                setOpen(false);
                toast.success(`${label} berhasil ditambahkan.`);
            },
        });
};
</script>

<template>
    <Dialog :open="open" @update:open="setOpen">
        <DialogContent
            class="flex max-h-[calc(100dvh-2rem)] w-[calc(100vw-2rem)] !max-w-2xl flex-col overflow-hidden p-4 sm:p-6"
        >
            <DialogHeader class="shrink-0">
                <DialogTitle>{{ documentCreateTitle }}</DialogTitle>
                <DialogDescription>
                    {{ documentCreateDescription }}
                </DialogDescription>
            </DialogHeader>

            <form
                class="grid min-h-0 min-w-0 flex-1 gap-4 overflow-y-auto py-2 pr-1 sm:grid-cols-2"
                @submit.prevent="submitDocumentCreate"
            >
                <label
                    v-if="activeCreateKind === 'pipeline'"
                    class="min-w-0 space-y-2 sm:col-span-2"
                >
                    <span class="text-sm font-medium text-foreground">
                        Judul
                    </span>
                    <Input
                        v-model="documentForm.title"
                        placeholder="Judul dokumen / paket"
                    />
                    <InputError :message="documentForm.errors.title" />
                </label>

                <label
                    v-if="
                        activeCreateKind === 'rab' ||
                        activeCreateKind === 'rap' ||
                        activeCreateKind === 'progress_report' ||
                        activeCreateKind === 'pipeline'
                    "
                    class="min-w-0 space-y-2"
                >
                    <span class="text-sm font-medium text-foreground">
                        Nomor Dokumen
                    </span>
                    <Input
                        v-model="documentForm.document_number"
                        placeholder="Nomor dokumen"
                    />
                    <InputError
                        :message="documentForm.errors.document_number"
                    />
                </label>

                <label
                    v-if="activeCreateKind === 'invoice'"
                    class="min-w-0 space-y-2"
                >
                    <span class="text-sm font-medium text-foreground">
                        Nomor Invoice
                    </span>
                    <Input
                        v-model="documentForm.invoice_number"
                        placeholder="Nomor tagihan"
                    />
                    <InputError :message="documentForm.errors.invoice_number" />
                </label>

                <label
                    v-if="activeCreateKind === 'project_cost'"
                    class="min-w-0 space-y-2"
                >
                    <span class="text-sm font-medium text-foreground">
                        No. Referensi
                    </span>
                    <Input
                        v-model="documentForm.reference_number"
                        placeholder="Receipt / PO"
                    />
                    <InputError
                        :message="documentForm.errors.reference_number"
                    />
                </label>

                <label
                    v-if="
                        activeCreateKind === 'rab' ||
                        activeCreateKind === 'rap' ||
                        activeCreateKind === 'pipeline'
                    "
                    class="min-w-0 space-y-2"
                >
                    <span class="text-sm font-medium text-foreground">
                        Tanggal Dokumen
                    </span>
                    <Input v-model="documentForm.document_date" type="date" />
                    <InputError :message="documentForm.errors.document_date" />
                </label>

                <label
                    v-if="activeCreateKind === 'progress_report'"
                    class="min-w-0 space-y-2"
                >
                    <span class="text-sm font-medium text-foreground">
                        Jenis Dokumen
                    </span>
                    <Select v-model="documentForm.document_type">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Pilih jenis" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="BA MC">
                                BA MC / Mutual Check
                            </SelectItem>
                            <SelectItem value="BAHPP">BAHPP</SelectItem>
                            <SelectItem value="C3">C3</SelectItem>
                            <SelectItem value="Laporan Akhir">
                                Laporan Akhir
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="documentForm.errors.document_type" />
                </label>

                <label
                    v-if="activeCreateKind === 'progress_report'"
                    class="min-w-0 space-y-2"
                >
                    <span class="text-sm font-medium text-foreground">
                        Progress (%)
                    </span>
                    <Input
                        v-model="documentForm.progress_percent"
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                    />
                    <InputError
                        :message="documentForm.errors.progress_percent"
                    />
                </label>

                <label
                    v-if="activeCreateKind === 'progress_report'"
                    class="min-w-0 space-y-2"
                >
                    <span class="text-sm font-medium text-foreground">
                        Tanggal Laporan
                    </span>
                    <Input v-model="documentForm.report_date" type="date" />
                    <InputError :message="documentForm.errors.report_date" />
                </label>

                <label
                    v-if="activeCreateKind === 'invoice'"
                    class="min-w-0 space-y-2"
                >
                    <span class="text-sm font-medium text-foreground">
                        Tanggal Invoice
                    </span>
                    <Input v-model="documentForm.invoice_date" type="date" />
                    <InputError :message="documentForm.errors.invoice_date" />
                </label>

                <label
                    v-if="activeCreateKind === 'invoice'"
                    class="min-w-0 space-y-2"
                >
                    <span class="text-sm font-medium text-foreground">
                        Jatuh Tempo
                    </span>
                    <Input v-model="documentForm.due_date" type="date" />
                    <InputError :message="documentForm.errors.due_date" />
                </label>

                <label
                    v-if="activeCreateKind === 'project_cost'"
                    class="min-w-0 space-y-2"
                >
                    <span class="text-sm font-medium text-foreground">
                        Tanggal
                    </span>
                    <Input v-model="documentForm.date" type="date" />
                    <InputError :message="documentForm.errors.date" />
                </label>

                <label
                    v-if="
                        activeCreateKind === 'invoice' ||
                        activeCreateKind === 'project_cost' ||
                        activeCreateKind === 'fund_request'
                    "
                    class="min-w-0 space-y-2"
                >
                    <span class="text-sm font-medium text-foreground">
                        Amount
                    </span>
                    <Input
                        v-model="documentForm.amount"
                        type="number"
                        :min="activeCreateKind === 'fund_request' ? 1 : 0"
                        step="0.01"
                    />
                    <InputError :message="documentForm.errors.amount" />
                </label>

                <label
                    v-if="activeCreateKind === 'invoice'"
                    class="min-w-0 space-y-2"
                >
                    <span class="text-sm font-medium text-foreground">
                        Tax
                    </span>
                    <Input
                        v-model="documentForm.tax_amount"
                        type="number"
                        min="0"
                        step="0.01"
                    />
                    <InputError :message="documentForm.errors.tax_amount" />
                </label>

                <label
                    v-if="activeCreateKind === 'project_cost'"
                    class="min-w-0 space-y-2"
                >
                    <span class="text-sm font-medium text-foreground">
                        Kategori
                    </span>
                    <Input
                        v-model="documentForm.category"
                        placeholder="Material, tenaga kerja..."
                    />
                    <InputError :message="documentForm.errors.category" />
                </label>

                <label
                    v-if="activeCreateKind === 'project_cost'"
                    class="min-w-0 space-y-2"
                >
                    <span class="text-sm font-medium text-foreground">
                        Vendor / Penerima
                    </span>
                    <Input v-model="documentForm.vendor" />
                    <InputError :message="documentForm.errors.vendor" />
                </label>

                <label
                    v-if="activeCreateKind === 'pipeline'"
                    class="min-w-0 space-y-2"
                >
                    <span class="text-sm font-medium text-foreground">
                        Owner / Client
                    </span>
                    <Input v-model="documentForm.owner" />
                    <InputError :message="documentForm.errors.owner" />
                </label>

                <label
                    v-if="activeCreateKind === 'pipeline'"
                    class="min-w-0 space-y-2"
                >
                    <span class="text-sm font-medium text-foreground">
                        Nilai
                    </span>
                    <Input
                        v-model="documentForm.value"
                        type="number"
                        min="0"
                        step="0.01"
                    />
                    <InputError :message="documentForm.errors.value" />
                </label>

                <label
                    v-if="
                        activeCreateKind === 'invoice' ||
                        activeCreateKind === 'pipeline'
                    "
                    class="min-w-0 space-y-2"
                >
                    <span class="text-sm font-medium text-foreground">
                        Status
                    </span>
                    <Select v-model="documentForm.status">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Pilih status" />
                        </SelectTrigger>
                        <SelectContent v-if="activeCreateKind === 'invoice'">
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="paid">Paid</SelectItem>
                            <SelectItem value="overdue">Overdue</SelectItem>
                        </SelectContent>
                        <SelectContent v-else>
                            <SelectItem value="open">Open</SelectItem>
                            <SelectItem value="submitted">Submitted</SelectItem>
                            <SelectItem value="won">Won</SelectItem>
                            <SelectItem value="lost">Lost</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="documentForm.errors.status" />
                </label>

                <label
                    v-if="
                        activeCreateKind !== 'fund_request' &&
                        activeCreateKind !== null
                    "
                    class="min-w-0 space-y-2 sm:col-span-2"
                >
                    <span class="text-sm font-medium text-foreground">
                        Catatan
                    </span>
                    <textarea
                        v-if="
                            activeCreateKind === 'rab' ||
                            activeCreateKind === 'rap' ||
                            activeCreateKind === 'pipeline'
                        "
                        v-model="documentForm.notes"
                        class="min-h-24 w-full min-w-0 rounded-md border border-input bg-background px-3 py-2 text-sm"
                        placeholder="Catatan dokumen"
                    ></textarea>
                    <textarea
                        v-else
                        v-model="documentForm.description"
                        class="min-h-24 w-full min-w-0 rounded-md border border-input bg-background px-3 py-2 text-sm"
                        placeholder="Deskripsi"
                    ></textarea>
                    <InputError
                        :message="
                            documentForm.errors.notes ||
                            documentForm.errors.description
                        "
                    />
                </label>

                <InputError
                    class="sm:col-span-2"
                    :message="documentForm.errors.project_id"
                />

                <DialogFooter class="shrink-0 sm:col-span-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="setOpen(false)"
                    >
                        Batal
                    </Button>
                    <Button type="submit" :disabled="documentForm.processing">
                        Simpan
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
