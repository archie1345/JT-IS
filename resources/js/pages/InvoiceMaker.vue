<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import { ImagePlus, Plus, Printer, Trash2, X } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import OptionSelect from '@/components/prototype/OptionSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';

type SelectOption = {
    value: number;
    label: string;
    hint?: null | string;
};

type InvoiceSource = {
    id: number;
    project_id: number | null;
    project_name: null | string;
    project_location: null | string;
    contract_number: null | string;
    client_name: null | string;
    client_contact: null | string;
    invoice_number: null | string;
    amount: number;
    tax_amount: number;
    invoice_date: null | string;
    due_date: null | string;
    status: null | string;
    description: null | string;
};

type ProjectSource = {
    id: number;
    name: null | string;
    location: null | string;
    contract_number: null | string;
    client_name: null | string;
    client_contact: null | string;
};

type InvoiceOption = SelectOption & {
    invoice: InvoiceSource;
};

type ProjectOption = SelectOption & {
    project: ProjectSource;
};

type InvoiceLine = {
    description: string;
    quantity: number | string;
    unit: string;
    unitPrice: number | string;
};

const props = defineProps<{
    invoiceOptions: InvoiceOption[];
    projectOptions: ProjectOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Billing', href: '/invoices' },
    { title: 'Invoice Maker', href: '/invoice-maker' },
];

const invoiceSelectOptions = computed<SelectOption[]>(() =>
    props.invoiceOptions.map(({ value, label, hint }) => ({
        value,
        label,
        hint,
    })),
);
const projectSelectOptions = computed<SelectOption[]>(() =>
    props.projectOptions.map(({ value, label, hint }) => ({
        value,
        label,
        hint,
    })),
);

const selectedInvoiceId = ref('');
const selectedProjectId = ref('');
const logoInput = ref<HTMLInputElement | null>(null);

const invoice = reactive({
    number: `INV-${new Date().getFullYear()}-001`,
    date: new Date().toISOString().slice(0, 10),
    dueDate: '',
    clientName: '',
    clientContact: '',
    projectName: '',
    projectLocation: '',
    contractNumber: '',
    notes: 'Please make payment according to the bank information below.',
    terms: 'Payment is due according to the date shown on this invoice.',
    bankDetails: 'Bank: \nAccount Name: \nAccount No: ',
});

const company = reactive({
    name: 'PT. Jasa Tirta Energi',
    address: 'Malang, Indonesia',
    phone: '',
    email: '',
});

const template = reactive({
    title: 'INVOICE',
    accentColor: '#0f766e',
    textColor: '#111827',
    paperColor: '#ffffff',
    borderColor: '#d1d5db',
    fontFamily: 'Inter, Arial, sans-serif',
    density: 'comfortable',
    logoUrl: '',
    logoSize: 72,
    showLogo: true,
    showBankDetails: true,
    showNotes: true,
    showSignature: true,
    footerText: 'Thank you for your business.',
});

const lines = ref<InvoiceLine[]>([
    {
        description: 'Project billing',
        quantity: 1,
        unit: 'Ls',
        unitPrice: 0,
    },
]);

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const toNumber = (value: number | string) => {
    const number = Number(value);

    return Number.isFinite(number) ? number : 0;
};

const lineTotal = (line: InvoiceLine) =>
    toNumber(line.quantity) * toNumber(line.unitPrice);

const subtotal = computed(() =>
    lines.value.reduce((total, line) => total + lineTotal(line), 0),
);
const taxAmount = ref(0);
const grandTotal = computed(() => subtotal.value + toNumber(taxAmount.value));
const invoiceTemplateStyle = computed(() => ({
    '--invoice-accent': template.accentColor,
    '--invoice-text': template.textColor,
    '--invoice-paper': template.paperColor,
    '--invoice-border': template.borderColor,
    '--invoice-font': template.fontFamily,
}));
const invoiceBodyClass = computed(() =>
    template.density === 'compact' ? 'gap-4 text-[11px]' : 'gap-6 text-xs',
);

const applyProject = (project: ProjectSource) => {
    invoice.projectName = project.name ?? '';
    invoice.projectLocation = project.location ?? '';
    invoice.contractNumber = project.contract_number ?? '';
    invoice.clientName = project.client_name ?? '';
    invoice.clientContact = project.client_contact ?? '';
};

const applyInvoice = (source: InvoiceSource) => {
    invoice.number = source.invoice_number || `INV-${source.id}`;
    invoice.date = source.invoice_date || invoice.date;
    invoice.dueDate = source.due_date || '';
    invoice.clientName = source.client_name ?? '';
    invoice.clientContact = source.client_contact ?? '';
    invoice.projectName = source.project_name ?? '';
    invoice.projectLocation = source.project_location ?? '';
    invoice.contractNumber = source.contract_number ?? '';
    taxAmount.value = source.tax_amount ?? 0;
    lines.value = [
        {
            description:
                source.description || source.project_name || 'Project billing',
            quantity: 1,
            unit: 'Ls',
            unitPrice: source.amount ?? 0,
        },
    ];

    selectedProjectId.value = source.project_id
        ? String(source.project_id)
        : '';
};

watch(selectedInvoiceId, (value) => {
    const selected = props.invoiceOptions.find(
        (option) => String(option.value) === value,
    );

    if (selected) {
        applyInvoice(selected.invoice);
    }
});

watch(selectedProjectId, (value) => {
    const selected = props.projectOptions.find(
        (option) => String(option.value) === value,
    );

    if (selected) {
        applyProject(selected.project);
    }
});

const addLine = () => {
    lines.value.push({
        description: '',
        quantity: 1,
        unit: 'Ls',
        unitPrice: 0,
    });
};

const removeLine = (index: number) => {
    if (lines.value.length === 1) {
        lines.value = [
            {
                description: '',
                quantity: 1,
                unit: 'Ls',
                unitPrice: 0,
            },
        ];
        return;
    }

    lines.value.splice(index, 1);
};

const chooseLogo = () => {
    logoInput.value?.click();
};

const handleLogoUpload = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) return;

    const reader = new FileReader();
    reader.onload = () => {
        template.logoUrl =
            typeof reader.result === 'string' ? reader.result : '';
        template.showLogo = true;
    };
    reader.readAsDataURL(file);
};

const clearLogo = () => {
    template.logoUrl = '';
};

const printInvoice = () => {
    window.print();
};
</script>

<template>
    <Head title="Invoice Maker" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="invoice-maker flex min-h-[calc(100vh-8rem)] flex-1 flex-col gap-4 rounded-xl p-2 sm:p-4"
        >
            <section
                class="no-print flex flex-col gap-3 border-b border-sidebar-border/70 pb-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Invoice Maker
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Build a printable invoice from billing records or custom
                        fields.
                    </p>
                </div>
                <Button @click="printInvoice">
                    <Printer class="mr-2 size-4" />
                    Create PDF
                </Button>
            </section>

            <div class="grid gap-4 xl:grid-cols-[24rem_minmax(0,1fr)]">
                <aside class="no-print space-y-4">
                    <section
                        class="rounded-lg border border-sidebar-border/70 bg-background p-4 shadow-sm"
                    >
                        <h2 class="text-sm font-medium">Source</h2>
                        <div class="mt-4 space-y-3">
                            <div class="space-y-1.5">
                                <Label>Use Existing Invoice</Label>
                                <OptionSelect
                                    v-model="selectedInvoiceId"
                                    :options="invoiceSelectOptions"
                                    placeholder="Select invoice"
                                    empty-label="Manual invoice"
                                    allow-empty
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label>Project</Label>
                                <OptionSelect
                                    v-model="selectedProjectId"
                                    :options="projectSelectOptions"
                                    placeholder="Select project"
                                    empty-label="No project"
                                    allow-empty
                                />
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-lg border border-sidebar-border/70 bg-background p-4 shadow-sm"
                    >
                        <h2 class="text-sm font-medium">Invoice Fields</h2>
                        <div class="mt-4 grid gap-3">
                            <div class="space-y-1.5">
                                <Label for="invoice_number">Invoice No.</Label>
                                <Input
                                    id="invoice_number"
                                    v-model="invoice.number"
                                />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <Label for="invoice_date">Date</Label>
                                    <Input
                                        id="invoice_date"
                                        v-model="invoice.date"
                                        type="date"
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="due_date">Due</Label>
                                    <Input
                                        id="due_date"
                                        v-model="invoice.dueDate"
                                        type="date"
                                    />
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="client_name">Client</Label>
                                <Input
                                    id="client_name"
                                    v-model="invoice.clientName"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="client_contact"
                                    >Client Contact</Label
                                >
                                <textarea
                                    id="client_contact"
                                    v-model="invoice.clientContact"
                                    class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="project_name">Project</Label>
                                <Input
                                    id="project_name"
                                    v-model="invoice.projectName"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="contract_number"
                                    >Contract No.</Label
                                >
                                <Input
                                    id="contract_number"
                                    v-model="invoice.contractNumber"
                                />
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-lg border border-sidebar-border/70 bg-background p-4 shadow-sm"
                    >
                        <h2 class="text-sm font-medium">Template</h2>
                        <div class="mt-4 grid gap-3">
                            <div class="space-y-1.5">
                                <Label for="template_title">Title</Label>
                                <Input
                                    id="template_title"
                                    v-model="template.title"
                                />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <Label for="accent_color">Accent</Label>
                                    <Input
                                        id="accent_color"
                                        v-model="template.accentColor"
                                        type="color"
                                        class="h-10 p-1"
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="text_color">Text</Label>
                                    <Input
                                        id="text_color"
                                        v-model="template.textColor"
                                        type="color"
                                        class="h-10 p-1"
                                    />
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="logo_size">Logo Size</Label>
                                <Input
                                    id="logo_size"
                                    v-model.number="template.logoSize"
                                    type="range"
                                    min="36"
                                    max="140"
                                />
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="chooseLogo"
                                >
                                    <ImagePlus class="mr-2 size-4" />
                                    Logo
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="clearLogo"
                                >
                                    <X class="mr-2 size-4" />
                                    Clear
                                </Button>
                            </div>
                            <input
                                ref="logoInput"
                                type="file"
                                accept="image/*"
                                class="sr-only"
                                @change="handleLogoUpload"
                            />
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="template.showLogo"
                                    type="checkbox"
                                    class="size-4 rounded border-input"
                                />
                                Show logo
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="template.showBankDetails"
                                    type="checkbox"
                                    class="size-4 rounded border-input"
                                />
                                Show bank details
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="template.showSignature"
                                    type="checkbox"
                                    class="size-4 rounded border-input"
                                />
                                Show signature
                            </label>
                        </div>
                    </section>
                </aside>

                <main class="space-y-4">
                    <section
                        class="no-print rounded-lg border border-sidebar-border/70 bg-background p-4 shadow-sm"
                    >
                        <div
                            class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
                        >
                            <div>
                                <h2 class="text-sm font-medium">Line Items</h2>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    These rows are only for this printable
                                    invoice draft.
                                </p>
                            </div>
                            <Button type="button" size="sm" @click="addLine">
                                <Plus class="mr-2 size-4" />
                                Add Row
                            </Button>
                        </div>

                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-[48rem] text-sm">
                                <thead
                                    class="text-left text-xs text-muted-foreground"
                                >
                                    <tr>
                                        <th class="px-2 py-2">Description</th>
                                        <th class="px-2 py-2">Qty</th>
                                        <th class="px-2 py-2">Unit</th>
                                        <th class="px-2 py-2">Unit Price</th>
                                        <th class="px-2 py-2">Total</th>
                                        <th class="px-2 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(line, index) in lines"
                                        :key="index"
                                        class="border-t border-sidebar-border/70"
                                    >
                                        <td class="px-2 py-2">
                                            <Input
                                                v-model="line.description"
                                                class="min-w-64"
                                            />
                                        </td>
                                        <td class="px-2 py-2">
                                            <Input
                                                v-model="line.quantity"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                class="w-24"
                                            />
                                        </td>
                                        <td class="px-2 py-2">
                                            <Input
                                                v-model="line.unit"
                                                class="w-20"
                                            />
                                        </td>
                                        <td class="px-2 py-2">
                                            <Input
                                                v-model="line.unitPrice"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                class="w-36"
                                            />
                                        </td>
                                        <td class="px-2 py-2 font-medium">
                                            {{
                                                formatCurrency(lineTotal(line))
                                            }}
                                        </td>
                                        <td class="px-2 py-2 text-right">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon-sm"
                                                @click="removeLine(index)"
                                            >
                                                <Trash2 class="size-4" />
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 grid gap-3 lg:grid-cols-3">
                            <div class="space-y-1.5">
                                <Label for="tax_amount">Tax Amount</Label>
                                <Input
                                    id="tax_amount"
                                    v-model.number="taxAmount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                />
                            </div>
                            <div class="space-y-1.5 lg:col-span-2">
                                <Label for="bank_details">Bank Details</Label>
                                <textarea
                                    id="bank_details"
                                    v-model="invoice.bankDetails"
                                    class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                                />
                            </div>
                            <div class="space-y-1.5 lg:col-span-3">
                                <Label for="notes">Notes</Label>
                                <textarea
                                    id="notes"
                                    v-model="invoice.notes"
                                    class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                                />
                            </div>
                        </div>
                    </section>

                    <section class="invoice-print-area">
                        <div
                            class="invoice-sheet mx-auto flex w-full max-w-[210mm] flex-col shadow-lg"
                            :style="invoiceTemplateStyle"
                        >
                            <div
                                class="flex items-start justify-between gap-6 border-b px-8 py-7"
                                style="border-color: var(--invoice-border)"
                            >
                                <div class="flex min-w-0 items-start gap-4">
                                    <div
                                        v-if="template.showLogo"
                                        class="flex shrink-0 items-center justify-center overflow-hidden rounded border bg-white"
                                        :style="{
                                            width: `${template.logoSize}px`,
                                            height: `${template.logoSize}px`,
                                            borderColor:
                                                'var(--invoice-border)',
                                        }"
                                    >
                                        <img
                                            v-if="template.logoUrl"
                                            :src="template.logoUrl"
                                            alt="Invoice logo"
                                            class="h-full w-full object-contain"
                                        />
                                        <span
                                            v-else
                                            class="px-2 text-center text-[10px] text-gray-400"
                                        >
                                            LOGO
                                        </span>
                                    </div>
                                    <div class="min-w-0">
                                        <h2 class="text-lg font-semibold">
                                            {{ company.name }}
                                        </h2>
                                        <p
                                            class="mt-1 text-xs whitespace-pre-line opacity-75"
                                        >
                                            {{ company.address }}
                                        </p>
                                        <p class="mt-1 text-xs opacity-75">
                                            {{ company.phone }}
                                            <span
                                                v-if="
                                                    company.phone &&
                                                    company.email
                                                "
                                            >
                                                |
                                            </span>
                                            {{ company.email }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <h1
                                        class="text-3xl font-bold tracking-normal"
                                        style="color: var(--invoice-accent)"
                                    >
                                        {{ template.title }}
                                    </h1>
                                    <p class="mt-2 text-sm font-medium">
                                        {{ invoice.number }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="grid px-8 py-7"
                                :class="invoiceBodyClass"
                            >
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <p
                                            class="text-[11px] font-semibold tracking-wide uppercase"
                                            style="color: var(--invoice-accent)"
                                        >
                                            Bill To
                                        </p>
                                        <p class="mt-2 text-base font-semibold">
                                            {{ invoice.clientName || '-' }}
                                        </p>
                                        <p
                                            class="mt-1 whitespace-pre-line opacity-75"
                                        >
                                            {{ invoice.clientContact }}
                                        </p>
                                    </div>
                                    <div
                                        class="grid gap-2 sm:justify-end sm:text-right"
                                    >
                                        <p>
                                            <span class="font-medium"
                                                >Date:</span
                                            >
                                            {{ invoice.date || '-' }}
                                        </p>
                                        <p>
                                            <span class="font-medium"
                                                >Due:</span
                                            >
                                            {{ invoice.dueDate || '-' }}
                                        </p>
                                        <p>
                                            <span class="font-medium"
                                                >Contract:</span
                                            >
                                            {{ invoice.contractNumber || '-' }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="rounded border p-4"
                                    style="border-color: var(--invoice-border)"
                                >
                                    <p class="font-medium">
                                        {{ invoice.projectName || 'Project' }}
                                    </p>
                                    <p class="mt-1 opacity-75">
                                        {{ invoice.projectLocation }}
                                    </p>
                                </div>

                                <div class="overflow-hidden rounded border">
                                    <table class="w-full border-collapse">
                                        <thead
                                            style="
                                                background: var(
                                                    --invoice-accent
                                                );
                                                color: white;
                                            "
                                        >
                                            <tr>
                                                <th class="px-4 py-3 text-left">
                                                    Description
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-right"
                                                >
                                                    Qty
                                                </th>
                                                <th class="px-4 py-3 text-left">
                                                    Unit
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-right"
                                                >
                                                    Unit Price
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-right"
                                                >
                                                    Amount
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(line, index) in lines"
                                                :key="`preview-${index}`"
                                                class="border-b"
                                                style="
                                                    border-color: var(
                                                        --invoice-border
                                                    );
                                                "
                                            >
                                                <td class="px-4 py-3">
                                                    {{
                                                        line.description || '-'
                                                    }}
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-right"
                                                >
                                                    {{ line.quantity }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    {{ line.unit }}
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-right"
                                                >
                                                    {{
                                                        formatCurrency(
                                                            toNumber(
                                                                line.unitPrice,
                                                            ),
                                                        )
                                                    }}
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-right"
                                                >
                                                    {{
                                                        formatCurrency(
                                                            lineTotal(line),
                                                        )
                                                    }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div
                                    class="grid gap-5 sm:grid-cols-[1fr_18rem]"
                                >
                                    <div class="space-y-4">
                                        <div v-if="template.showBankDetails">
                                            <p class="font-medium">
                                                Bank Details
                                            </p>
                                            <p
                                                class="mt-1 whitespace-pre-line opacity-75"
                                            >
                                                {{ invoice.bankDetails }}
                                            </p>
                                        </div>
                                        <div v-if="template.showNotes">
                                            <p class="font-medium">Notes</p>
                                            <p
                                                class="mt-1 whitespace-pre-line opacity-75"
                                            >
                                                {{ invoice.notes }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="flex justify-between gap-4">
                                            <span>Subtotal</span>
                                            <span>{{
                                                formatCurrency(subtotal)
                                            }}</span>
                                        </div>
                                        <div class="flex justify-between gap-4">
                                            <span>Tax</span>
                                            <span>{{
                                                formatCurrency(
                                                    toNumber(taxAmount),
                                                )
                                            }}</span>
                                        </div>
                                        <div
                                            class="mt-3 flex justify-between gap-4 border-t pt-3 text-base font-semibold"
                                            style="
                                                border-color: var(
                                                    --invoice-border
                                                );
                                            "
                                        >
                                            <span>Total</span>
                                            <span>{{
                                                formatCurrency(grandTotal)
                                            }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="template.showSignature"
                                    class="mt-4 flex justify-end"
                                >
                                    <div class="w-48 text-center">
                                        <div
                                            class="mb-16 border-t"
                                            style="
                                                border-color: var(
                                                    --invoice-border
                                                );
                                            "
                                        ></div>
                                        <p class="font-medium">
                                            Authorized Signature
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="mt-auto border-t px-8 py-4 text-center text-xs opacity-70"
                                style="border-color: var(--invoice-border)"
                            >
                                {{ template.footerText }}
                            </div>
                        </div>
                    </section>
                </main>
            </div>
        </div>
    </AppLayout>
</template>

<style>
.invoice-sheet {
    min-height: 297mm;
    background: var(--invoice-paper);
    color: var(--invoice-text);
    font-family: var(--invoice-font);
}

@media print {
    @page {
        size: A4;
        margin: 0;
    }

    body * {
        visibility: hidden;
    }

    .invoice-print-area,
    .invoice-print-area * {
        visibility: visible;
    }

    .invoice-print-area {
        position: absolute;
        inset: 0;
        background: white;
    }

    .invoice-sheet {
        width: 210mm;
        min-height: 297mm;
        box-shadow: none !important;
    }

    .no-print {
        display: none !important;
    }
}
</style>
