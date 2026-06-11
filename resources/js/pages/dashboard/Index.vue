<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { Check, ChevronDown, GripVertical, Settings2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { Drake } from 'dragula';
import type { Auth, BreadcrumbItem } from '@/types';

interface DashboardWidget {
    id: string;
    title: string;
    description: string;
    heightClass: string;
}

type FixedDashboardSection = {
    id: string;
    title: string;
};
type DashboardItem =
    | (FixedDashboardSection & { type: 'section' })
    | (DashboardWidget & { type: 'widget' });

type ChartType = 'bar' | 'line' | 'donut' | 'metric';
type ValueFormat = 'number' | 'currency' | 'percent';
type SummaryTone = 'neutral' | 'success' | 'warning' | 'critical';
type DashboardPoint = {
    label: string;
    value: number;
    format?: ValueFormat;
    tone?: SummaryTone;
};
type WarningItem = { type: string; level: string; message: string };
type ProblemProject = {
    id: number;
    name: string;
    client: string;
    status: string;
    warnings: WarningItem[];
    contractValue: number;
    realizedCost: number;
    rapTotal: number;
    approvedProgress: number;
};
type RecentProgress = {
    id: number;
    projectId: number;
    projectName: string;
    client: string;
    percent: number;
    date: string | null;
    approved: boolean;
    documentNumber: string | null;
};
type DashboardData = Record<string, unknown>;
type WidgetSetting = {
    chartType: ChartType;
    dataSource: string;
    valueFormat: ValueFormat;
};
type StoredDashboardLayout = {
    order?: string[];
    visible?: string[];
    settings?: Record<string, Partial<WidgetSetting>>;
};

type DashboardPageProps = {
    auth: Auth & {
        user: Auth['user'] & {
            dashboard_layout?: StoredDashboardLayout | string[] | null;
        };
    };
    dashboardData?: DashboardData;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

const defaultWidgets: DashboardWidget[] = [
    {
        id: 'revenue-overview',
        title: 'Ringkasan Kontrak & Invoice',
        description:
            'Ringkasan nilai kontrak, tagihan, dan realisasi biaya untuk monitoring aktif.',
        heightClass: 'min-h-72',
    },
    {
        id: 'conversion-trend',
        title: 'Tren Pipeline dan Tagihan',
        description:
            'Pergerakan tender, invoice, dan cashflow dari data saat ini.',
        heightClass: 'min-h-56',
    },
    {
        id: 'team-activity',
        title: 'Aktivitas Monitoring Terbaru',
        description:
            'Aktivitas proyek, progress, dan finance terbaru yang perlu direview.',
        heightClass: 'min-h-56',
    },
    {
        id: 'projects-health',
        title: 'Kesehatan Proyek',
        description:
            'Distribusi status berdasarkan budget, progress, dan peringatan pembayaran.',
        heightClass: 'min-h-56',
    },
    {
        id: 'upcoming-items',
        title: 'Perlu Perhatian',
        description:
            'Item terbuka yang perlu dicek manajemen sebelum review berikutnya.',
        heightClass: 'min-h-56',
    },
];

const dataSourceOptions = [
    {
        value: 'projectStatus',
        label: 'Proyek berdasarkan status',
        defaultFormat: 'number',
    },
    {
        value: 'invoiceStatus',
        label: 'Nilai invoice berdasarkan status',
        defaultFormat: 'currency',
    },
    {
        value: 'costCategory',
        label: 'Biaya berdasarkan kategori',
        defaultFormat: 'currency',
    },
    {
        value: 'monthlyInvoices',
        label: 'Invoice bulanan',
        defaultFormat: 'currency',
    },
    {
        value: 'monthlyCosts',
        label: 'Biaya bulanan',
        defaultFormat: 'currency',
    },
    {
        value: 'progressTrend',
        label: 'Tren progress',
        defaultFormat: 'percent',
    },
    { value: 'totals', label: 'Total ringkasan', defaultFormat: 'currency' },
] as const;
const chartTypeOptions: Array<{ value: ChartType; label: string }> = [
    { value: 'bar', label: 'Batang' },
    { value: 'line', label: 'Garis' },
    { value: 'donut', label: 'Donut' },
    { value: 'metric', label: 'Metrik' },
];
const valueFormatOptions: Array<{ value: ValueFormat; label: string }> = [
    { value: 'number', label: 'Angka' },
    { value: 'currency', label: 'Mata Uang' },
    { value: 'percent', label: 'Persen' },
];

const defaultWidgetIds = defaultWidgets.map((widget) => widget.id);
const fixedDashboardSections = [
    { id: 'project-summary', title: 'Ringkasan Proyek' },
    { id: 'problem-projects', title: 'Proyek Bermasalah' },
    { id: 'recent-progress', title: 'Progress Terbaru / BAMC' },
] as const;
const fixedDashboardSectionIds = fixedDashboardSections.map(
    (section): string => section.id,
);
const defaultDashboardItemIds = [
    ...fixedDashboardSectionIds,
    ...defaultWidgetIds,
];
const defaultVisibleIds = [...defaultDashboardItemIds];
const isChartType = (value: unknown): value is ChartType =>
    chartTypeOptions.some((option) => option.value === value);
const isValueFormat = (value: unknown): value is ValueFormat =>
    valueFormatOptions.some((option) => option.value === value);
const isDataSource = (value: unknown): value is string =>
    typeof value === 'string' &&
    dataSourceOptions.some((option) => option.value === value);
const page = usePage<DashboardPageProps>();
const dashboardList = ref<HTMLElement | null>(null);
const dashboardItems = ref<string[]>([...defaultDashboardItemIds]);
const visibleWidgetIds = ref<string[]>([...defaultVisibleIds]);
const widgetSettings = ref<Record<string, WidgetSetting>>({});
const openSettingsWidgetId = ref<string | null>(null);
const showDashboardOptions = ref(false);
const userId = computed(() => page.props.auth?.user?.id ?? 'guest');
const storageKey = computed(() => `dashboard-layout:user-${userId.value}`);
const dashboardData = computed<DashboardData>(
    () => page.props.dashboardData ?? {},
);
const projectSummary = computed(
    () =>
        (dashboardData.value.projectSummary as DashboardPoint[] | undefined) ??
        [],
);
const problemProjects = computed(
    () =>
        (dashboardData.value.problemProjects as ProblemProject[] | undefined) ??
        [],
);
const recentProgress = computed(
    () =>
        (dashboardData.value.recentProgress as RecentProgress[] | undefined) ??
        [],
);
const dashboardOptions = computed<DashboardItem[]>(() => [
    ...fixedDashboardSections.map((section) => ({
        ...section,
        type: 'section' as const,
    })),
    ...defaultWidgets.map((widget) => ({ ...widget, type: 'widget' as const })),
]);
const dashboardItemById = computed(
    () => new Map(dashboardOptions.value.map((item) => [item.id, item])),
);
const visibleDashboardItems = computed(() =>
    dashboardItems.value
        .map((itemId) => dashboardItemById.value.get(itemId))
        .filter((item): item is DashboardItem => {
            if (!item) {
                return false;
            }

            return visibleWidgetIds.value.includes(item.id);
        }),
);
let drake: Drake | null = null;
let saveTimeout: ReturnType<typeof setTimeout> | null = null;

const migrateDashboardId = (widgetId: string) =>
    widgetId === 'summary-metrics' ? 'project-summary' : widgetId;

const normalizeDashboardIds = (
    widgetIds: string[] | null | undefined,
    includeMissing = false,
) => {
    if (!Array.isArray(widgetIds)) {
        return null;
    }

    const validIds = widgetIds
        .map(migrateDashboardId)
        .filter((widgetId) => defaultDashboardItemIds.includes(widgetId));
    const uniqueIds = Array.from(new Set(validIds));

    if (includeMissing) {
        const hasStoredSection = uniqueIds.some((widgetId) =>
            fixedDashboardSectionIds.includes(widgetId),
        );
        const missingFixedSections = fixedDashboardSectionIds.filter(
            (widgetId) => !uniqueIds.includes(widgetId),
        );
        const missingWidgets = defaultWidgetIds.filter(
            (widgetId) => !uniqueIds.includes(widgetId),
        );

        if (!hasStoredSection) {
            return [...missingFixedSections, ...uniqueIds, ...missingWidgets];
        }

        return [...uniqueIds, ...missingFixedSections, ...missingWidgets];
    }

    return uniqueIds.length > 0 ? uniqueIds : null;
};

const defaultSettingFor = (widgetId: string): WidgetSetting => {
    const source =
        dataSourceOptions[
            defaultWidgetIds.indexOf(widgetId) % dataSourceOptions.length
        ] ?? dataSourceOptions[0];

    return {
        chartType: widgetId === 'upcoming-items' ? 'metric' : 'bar',
        dataSource: source.value,
        valueFormat: source.defaultFormat as ValueFormat,
    };
};

const normalizeSettings = (
    settings: StoredDashboardLayout['settings'] | null | undefined,
): Record<string, WidgetSetting> =>
    Object.fromEntries(
        defaultWidgetIds.map((widgetId) => {
            const fallback = defaultSettingFor(widgetId);
            const stored = settings?.[widgetId] ?? {};
            const dataSource = isDataSource(stored.dataSource)
                ? stored.dataSource
                : fallback.dataSource;

            return [
                widgetId,
                {
                    chartType: isChartType(stored.chartType)
                        ? stored.chartType
                        : fallback.chartType,
                    dataSource,
                    valueFormat: isValueFormat(stored.valueFormat)
                        ? stored.valueFormat
                        : fallback.valueFormat,
                },
            ];
        }),
    );

const getSavedLayout = (): StoredDashboardLayout | null => {
    const sqlLayout = page.props.auth?.user?.dashboard_layout;

    if (Array.isArray(sqlLayout)) {
        return {
            order: normalizeDashboardIds(sqlLayout, true) ?? [
                ...defaultDashboardItemIds,
            ],
            visible: [...defaultVisibleIds],
        };
    }

    if (
        sqlLayout &&
        typeof sqlLayout === 'object' &&
        !Array.isArray(sqlLayout)
    ) {
        return {
            order: normalizeDashboardIds(sqlLayout.order, true) ?? [
                ...defaultDashboardItemIds,
            ],
            visible: normalizeDashboardIds(sqlLayout.visible) ?? [
                ...defaultVisibleIds,
            ],
            settings: sqlLayout.settings,
        };
    }

    if (typeof window === 'undefined') {
        return null;
    }

    const savedLayout = window.localStorage.getItem(storageKey.value);

    if (!savedLayout) {
        return null;
    }

    try {
        const parsedLayout = JSON.parse(savedLayout) as
            | StoredDashboardLayout
            | string[];

        if (Array.isArray(parsedLayout)) {
            return {
                order: normalizeDashboardIds(parsedLayout, true) ?? [
                    ...defaultDashboardItemIds,
                ],
                visible: [...defaultVisibleIds],
            };
        }

        return {
            order: normalizeDashboardIds(parsedLayout.order, true) ?? [
                ...defaultDashboardItemIds,
            ],
            visible: normalizeDashboardIds(parsedLayout.visible) ?? [
                ...defaultVisibleIds,
            ],
            settings: parsedLayout.settings,
        };
    } catch {
        return null;
    }
};

const getLayoutPayload = (): StoredDashboardLayout => ({
    order: dashboardItems.value,
    visible: visibleWidgetIds.value,
    settings: widgetSettings.value,
});

const hydrateDashboardLayout = () => {
    const savedLayout = getSavedLayout();

    dashboardItems.value = savedLayout?.order ?? [...defaultDashboardItemIds];
    visibleWidgetIds.value = savedLayout?.visible ?? [...defaultVisibleIds];
    widgetSettings.value = normalizeSettings(savedLayout?.settings);
};

const persistWidgetsLocally = () => {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(
        storageKey.value,
        JSON.stringify(getLayoutPayload()),
    );
};

const persistWidgetsToSql = () => {
    router.post(
        '/dashboard/layout',
        {
            layout: getLayoutPayload(),
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onError: () => {
                persistWidgetsLocally();
            },
        },
    );
};

const queuePersist = () => {
    persistWidgetsLocally();

    if (saveTimeout) {
        clearTimeout(saveTimeout);
    }

    saveTimeout = setTimeout(() => {
        persistWidgetsToSql();
    }, 250);
};

const orderDashboardItems = (itemIds: string[]) =>
    normalizeDashboardIds(itemIds, true) ?? [...defaultDashboardItemIds];

const syncDashboardItemsFromDom = () => {
    if (!dashboardList.value) {
        return;
    }

    const itemIds = Array.from(dashboardList.value.children)
        .map((child) =>
            child instanceof HTMLElement
                ? child.dataset.dashboardId
                : undefined,
        )
        .filter((itemId): itemId is string => Boolean(itemId));

    dashboardItems.value = orderDashboardItems(itemIds);
    queuePersist();
};

const toggleDashboardItemVisibility = (widgetId: string) => {
    if (visibleWidgetIds.value.includes(widgetId)) {
        if (visibleWidgetIds.value.length === 1) {
            return;
        }

        visibleWidgetIds.value = visibleWidgetIds.value.filter(
            (visibleId) => visibleId !== widgetId,
        );
    } else {
        visibleWidgetIds.value = [...visibleWidgetIds.value, widgetId];
    }

    queuePersist();
};

const hasAnyVisibleDashboardSection = computed(
    () => visibleDashboardItems.value.length > 0,
);

const widgetData = (widgetId: string): DashboardPoint[] => {
    const source =
        widgetSettings.value[widgetId]?.dataSource ??
        defaultSettingFor(widgetId).dataSource;

    return (dashboardData.value[source] as DashboardPoint[] | undefined) ?? [];
};

const maxValue = (points: DashboardPoint[]) =>
    Math.max(...points.map((point) => Number(point.value) || 0), 1);

const formatValue = (value: number, format: ValueFormat) => {
    if (format === 'currency') {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(value);
    }

    if (format === 'percent') {
        return `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(value)}%`;
    }

    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(
        value,
    );
};

const statusClass = (status: string) =>
    ({
        'On Track':
            'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-500/25',
        Warning: 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-500/25',
        Critical: 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-500/25',
        'On Hold': 'bg-slate-500/15 text-slate-600 ring-1 ring-slate-500/25',
    })[status] ?? 'bg-slate-500/15 text-slate-600 ring-1 ring-slate-500/25';

const statusLabel = (status: string) =>
    ({
        'On Track': 'Sesuai Rencana',
        Warning: 'Perhatian',
        Critical: 'Kritis',
        'On Hold': 'Ditahan',
    })[status] ?? status;

const summaryCardClass = (tone: SummaryTone = 'neutral') =>
    ({
        neutral:
            'border-sidebar-border/70 bg-background/90 dark:border-sidebar-border',
        success: 'border-emerald-500/30 bg-emerald-500/10',
        warning: 'border-amber-500/35 bg-amber-500/10',
        critical: 'border-rose-500/35 bg-rose-500/10',
    })[tone];

const summaryValueClass = (tone: SummaryTone = 'neutral') =>
    ({
        neutral: 'text-foreground',
        success: 'text-emerald-700 dark:text-emerald-300',
        warning: 'text-amber-700 dark:text-amber-300',
        critical: 'text-rose-700 dark:text-rose-300',
    })[tone];

const pointFormat = (point: DashboardPoint): ValueFormat =>
    point.format ??
    ([
        'value',
        'cost',
        'amount',
        'nilai',
        'biaya',
        'tagihan',
        'pembayaran',
    ].some((keyword) => point.label.toLowerCase().includes(keyword))
        ? 'currency'
        : 'number');

const linePoints = (points: DashboardPoint[]) => {
    if (points.length === 0) {
        return '';
    }

    const max = maxValue(points);
    const denominator = Math.max(points.length - 1, 1);

    return points
        .map((point, index) => {
            const x = 8 + (index / denominator) * 84;
            const y = 88 - ((Number(point.value) || 0) / max) * 72;

            return `${x},${y}`;
        })
        .join(' ');
};

const donutSegments = (points: DashboardPoint[]) => {
    const total =
        points.reduce((sum, point) => sum + (Number(point.value) || 0), 0) || 1;
    let offset = 25;

    return points.map((point, index) => {
        const percent = ((Number(point.value) || 0) / total) * 100;
        const segment = {
            point,
            percent,
            offset,
            color: [
                '#0f766e',
                '#2563eb',
                '#d97706',
                '#be123c',
                '#7c3aed',
                '#475569',
            ][index % 6],
        };
        offset -= percent;

        return segment;
    });
};

const updateWidgetSetting = (
    widgetId: string,
    key: keyof WidgetSetting,
    value: string,
) => {
    const current =
        widgetSettings.value[widgetId] ?? defaultSettingFor(widgetId);
    const next = {
        ...current,
        [key]: value,
    } as WidgetSetting;

    if (key === 'dataSource') {
        const source = dataSourceOptions.find(
            (option) => option.value === value,
        );

        if (source) {
            next.valueFormat = source.defaultFormat as ValueFormat;
        }
    }

    widgetSettings.value = {
        ...widgetSettings.value,
        [widgetId]: next,
    };
    queuePersist();
};

const selectValue = (event: Event) =>
    event.target instanceof HTMLSelectElement ? event.target.value : '';

const initializeDragula = async () => {
    if (!dashboardList.value || typeof window === 'undefined') {
        return;
    }

    if (!(window as Window & typeof globalThis & { global?: Window }).global) {
        (window as Window & typeof globalThis & { global?: Window }).global =
            window;
    }

    await import('dragula/dist/dragula.css');
    const dragulaModule = await import('dragula');
    const dragula = dragulaModule.default;

    drake?.destroy();

    const activeDrake = dragula([dashboardList.value], {
        direction: 'vertical',
        revertOnSpill: true,
        ignoreInputTextSelection: false,
        mirrorContainer: document.body,
        moves: (_element, _container, handle) =>
            Boolean(handle?.closest('[data-drag-handle="true"]')),
    });

    activeDrake.on('drop', () => {
        syncDashboardItemsFromDom();
    });

    activeDrake.on('cancel', () => {
        syncDashboardItemsFromDom();
    });

    drake = activeDrake;
};

onMounted(async () => {
    hydrateDashboardLayout();
    await nextTick();
    await initializeDragula();
});

onBeforeUnmount(() => {
    if (saveTimeout) {
        clearTimeout(saveTimeout);
    }

    drake?.destroy();
    drake = null;
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex min-h-[calc(100vh-8rem)] min-w-0 flex-1 flex-col gap-3 overflow-hidden rounded-xl p-2 sm:gap-4 sm:p-4"
        >
            <div
                class="min-w-0 overflow-visible rounded-xl border border-sidebar-border/70 bg-background/70 p-3 sm:rounded-2xl sm:p-4 dark:border-sidebar-border"
            >
                <div
                    class="flex min-w-0 flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
                >
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-foreground">
                            Tampilan analitik
                        </p>
                        <p
                            class="mt-1 text-xs break-words text-muted-foreground sm:text-sm"
                        >
                            Pilih ringkasan operasional yang paling relevan
                            untuk review manajemen.
                        </p>
                    </div>

                    <div class="relative flex justify-start lg:justify-end">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg border border-sidebar-border/70 bg-background/90 px-3 py-2 text-sm font-medium text-foreground shadow-sm transition hover:bg-muted/40 dark:border-sidebar-border"
                            :aria-expanded="showDashboardOptions"
                            aria-controls="dashboard-options"
                            @click="
                                showDashboardOptions = !showDashboardOptions
                            "
                        >
                            <Settings2 class="size-4" />
                            Opsi
                            <ChevronDown
                                class="size-4 transition-transform"
                                :class="{
                                    'rotate-180': showDashboardOptions,
                                }"
                            />
                        </button>

                        <div
                            v-if="showDashboardOptions"
                            id="dashboard-options"
                            class="absolute top-full right-auto left-0 z-30 mt-2 w-72 max-w-[calc(100vw-2rem)] rounded-xl border border-sidebar-border/70 bg-background p-2 shadow-xl lg:right-0 lg:left-auto dark:border-sidebar-border"
                        >
                            <p
                                class="px-2 py-1 text-xs font-semibold text-muted-foreground uppercase"
                            >
                                Tampilkan item
                            </p>
                            <div class="mt-1 max-h-80 overflow-y-auto">
                                <label
                                    v-for="item in dashboardOptions"
                                    :key="`filter-${item.id}`"
                                    class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 text-sm transition hover:bg-muted/40"
                                >
                                    <input
                                        type="checkbox"
                                        class="sr-only"
                                        :checked="
                                            visibleWidgetIds.includes(item.id)
                                        "
                                        :disabled="
                                            visibleWidgetIds.length === 1 &&
                                            visibleWidgetIds.includes(item.id)
                                        "
                                        @change="
                                            toggleDashboardItemVisibility(
                                                item.id,
                                            )
                                        "
                                    />
                                    <span
                                        class="flex size-4 shrink-0 items-center justify-center rounded border text-[10px] font-bold"
                                        :class="
                                            visibleWidgetIds.includes(item.id)
                                                ? 'border-foreground bg-foreground text-background'
                                                : 'border-sidebar-border/70 bg-background text-transparent dark:border-sidebar-border'
                                        "
                                    >
                                        <Check class="size-3" />
                                    </span>
                                    <span class="min-w-0 truncate">
                                        {{ item.title }}
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="!hasAnyVisibleDashboardSection"
                class="rounded-2xl border border-dashed border-sidebar-border/70 bg-background/70 p-8 text-center text-sm text-muted-foreground dark:border-sidebar-border"
            >
                Tidak ada widget yang terlihat. Aktifkan salah satu dari filter
                di atas.
            </div>

            <div
                v-else
                ref="dashboardList"
                class="grid min-w-0 grid-cols-1 gap-4 xl:grid-cols-2"
            >
                <section
                    v-for="widget in visibleDashboardItems"
                    :key="widget.id"
                    :data-dashboard-id="widget.id"
                    class="dashboard-card group min-w-0 overflow-hidden rounded-xl border border-sidebar-border/70 bg-background/90 shadow-sm sm:rounded-2xl dark:border-sidebar-border"
                    :class="{
                        'xl:col-span-2': widget.id === 'project-summary',
                    }"
                >
                    <template v-if="widget.id === 'project-summary'">
                        <div
                            class="border-b border-sidebar-border/70 p-3 sm:p-4 dark:border-sidebar-border"
                        >
                            <div
                                data-drag-handle="true"
                                class="drag-handle flex cursor-grab items-center justify-between gap-4 select-none"
                            >
                                <h2
                                    class="min-w-0 text-sm font-semibold text-foreground"
                                >
                                    Ringkasan Proyek
                                </h2>
                                <span
                                    class="inline-flex shrink-0 items-center justify-center rounded-lg border border-sidebar-border/70 bg-background/80 p-2 text-muted-foreground dark:border-sidebar-border"
                                    aria-label="Geser Ringkasan Proyek"
                                >
                                    <GripVertical class="size-4" />
                                </span>
                            </div>
                        </div>

                        <div
                            class="grid min-w-0 grid-cols-[repeat(auto-fit,minmax(12rem,1fr))] gap-3 p-3 sm:p-4"
                        >
                            <div
                                v-for="point in projectSummary"
                                :key="point.label"
                                class="min-w-0 overflow-hidden rounded-xl border p-3 shadow-sm sm:p-4"
                                :class="summaryCardClass(point.tone)"
                            >
                                <p
                                    class="text-xs break-words text-muted-foreground"
                                >
                                    {{ point.label }}
                                </p>
                                <p
                                    class="mt-2 text-lg font-semibold break-words sm:text-xl"
                                    :class="summaryValueClass(point.tone)"
                                >
                                    {{
                                        formatValue(
                                            point.value ?? 0,
                                            pointFormat(point),
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </template>

                    <template v-else-if="widget.id === 'problem-projects'">
                        <div
                            class="border-b border-sidebar-border/70 p-3 sm:p-4 dark:border-sidebar-border"
                        >
                            <div
                                data-drag-handle="true"
                                class="drag-handle flex cursor-grab items-start justify-between gap-4 select-none"
                            >
                                <div class="min-w-0">
                                    <h2
                                        class="text-sm font-semibold text-foreground"
                                    >
                                        Proyek Bermasalah
                                    </h2>
                                    <p
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        Peringatan dini dari deviasi biaya,
                                        pembayaran, dan progress.
                                    </p>
                                </div>
                                <span
                                    class="inline-flex shrink-0 items-center justify-center rounded-lg border border-sidebar-border/70 bg-background/80 p-2 text-muted-foreground dark:border-sidebar-border"
                                    aria-label="Drag Proyek Bermasalah"
                                >
                                    <GripVertical class="size-4" />
                                </span>
                            </div>
                        </div>

                        <div class="p-3 sm:p-4">
                            <div class="relative min-w-0 overflow-x-hidden">
                                <div
                                    class="table-scrollbar max-w-full overflow-x-auto rounded-md border border-sidebar-border/60 pb-2 dark:border-sidebar-border"
                                >
                                    <table
                                        class="w-max min-w-full text-xs sm:text-sm"
                                    >
                                        <thead
                                            class="sticky top-0 z-10 bg-muted/95 text-left text-xs text-muted-foreground backdrop-blur"
                                        >
                                            <tr>
                                                <th
                                                    class="min-w-[16rem] px-3 py-2 font-medium"
                                                >
                                                    Proyek
                                                </th>
                                                <th
                                                    class="min-w-[7rem] px-3 py-2 font-medium"
                                                >
                                                    Status
                                                </th>
                                                <th
                                                    class="min-w-[9rem] px-3 py-2 font-medium"
                                                >
                                                    RAP
                                                </th>
                                                <th
                                                    class="min-w-[9rem] px-3 py-2 font-medium"
                                                >
                                                    Biaya
                                                </th>
                                                <th
                                                    class="min-w-[14rem] px-3 py-2 font-medium"
                                                >
                                                    Peringatan
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="project in problemProjects"
                                                :key="project.id"
                                                class="border-t border-sidebar-border/70"
                                            >
                                                <td class="px-3 py-3">
                                                    <button
                                                        class="max-w-[15rem] truncate text-left font-medium text-foreground hover:underline"
                                                        :title="project.name"
                                                        @click="
                                                            router.get(
                                                                `/projects/${project.id}`,
                                                            )
                                                        "
                                                    >
                                                        {{ project.name }}
                                                    </button>
                                                    <p
                                                        class="max-w-[15rem] truncate text-xs text-muted-foreground"
                                                    >
                                                        {{ project.client }}
                                                    </p>
                                                </td>
                                                <td class="px-3 py-3">
                                                    <span
                                                        class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                                        :class="
                                                            statusClass(
                                                                project.status,
                                                            )
                                                        "
                                                    >
                                                        {{
                                                            statusLabel(
                                                                project.status,
                                                            )
                                                        }}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-3">
                                                    {{
                                                        formatValue(
                                                            project.rapTotal,
                                                            'currency',
                                                        )
                                                    }}
                                                </td>
                                                <td class="px-3 py-3">
                                                    {{
                                                        formatValue(
                                                            project.realizedCost,
                                                            'currency',
                                                        )
                                                    }}
                                                </td>
                                                <td
                                                    class="px-3 py-3 text-muted-foreground"
                                                >
                                                    <span
                                                        class="block max-w-[13rem] truncate"
                                                        :title="
                                                            project.warnings[0]
                                                                ?.message ?? '-'
                                                        "
                                                    >
                                                        {{
                                                            project.warnings[0]
                                                                ?.message ?? '-'
                                                        }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr
                                                v-if="
                                                    problemProjects.length === 0
                                                "
                                            >
                                                <td
                                                    colspan="5"
                                                    class="px-3 py-6 text-center text-muted-foreground"
                                                >
                                                    Belum ada proyek bermasalah.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template v-else-if="widget.id === 'recent-progress'">
                        <div
                            class="border-b border-sidebar-border/70 p-3 sm:p-4 dark:border-sidebar-border"
                        >
                            <div
                                data-drag-handle="true"
                                class="drag-handle flex cursor-grab items-center justify-between gap-4 select-none"
                            >
                                <h2
                                    class="min-w-0 text-sm font-semibold text-foreground"
                                >
                                    Progress Terbaru / BAMC
                                </h2>
                                <span
                                    class="inline-flex shrink-0 items-center justify-center rounded-lg border border-sidebar-border/70 bg-background/80 p-2 text-muted-foreground dark:border-sidebar-border"
                                    aria-label="Geser Progress Terbaru / BAMC"
                                >
                                    <GripVertical class="size-4" />
                                </span>
                            </div>
                        </div>

                        <div class="min-w-0 space-y-2 p-3 sm:p-4">
                            <button
                                v-for="progress in recentProgress"
                                :key="progress.id"
                                class="flex w-full min-w-0 flex-col items-start justify-between gap-2 rounded-xl border border-sidebar-border/70 bg-muted/20 px-3 py-2 text-left sm:flex-row sm:items-center sm:gap-3"
                                @click="
                                    router.get(
                                        `/projects/${progress.projectId}`,
                                    )
                                "
                            >
                                <span class="min-w-0">
                                    <span
                                        class="block truncate text-sm font-medium text-foreground"
                                        :title="progress.projectName"
                                    >
                                        {{ progress.projectName }}
                                    </span>
                                    <span
                                        class="block truncate text-xs text-muted-foreground"
                                        :title="`${progress.client} | ${progress.date ?? '-'}`"
                                    >
                                        {{ progress.client }} |
                                        {{ progress.date ?? '-' }}
                                    </span>
                                </span>
                                <span
                                    class="shrink-0 rounded-full px-2 py-1 text-xs font-medium"
                                    :class="
                                        progress.approved
                                            ? statusClass('On Track')
                                            : statusClass('Warning')
                                    "
                                >
                                    {{ progress.percent }}%
                                    {{
                                        progress.approved
                                            ? 'disetujui'
                                            : 'draft'
                                    }}
                                </span>
                            </button>
                            <div
                                v-if="recentProgress.length === 0"
                                class="rounded-xl border border-dashed border-sidebar-border/70 p-6 text-center text-sm text-muted-foreground"
                            >
                                Belum ada data progress.
                            </div>
                        </div>
                    </template>

                    <template v-else-if="widget.type === 'widget'">
                        <div
                            class="border-b border-sidebar-border/70 p-3 sm:p-4 dark:border-sidebar-border"
                        >
                            <div
                                data-drag-handle="true"
                                class="drag-handle flex cursor-grab items-start justify-between gap-4 select-none"
                            >
                                <div class="min-w-0">
                                    <h2
                                        class="text-sm font-semibold text-foreground"
                                    >
                                        {{ widget.title }}
                                    </h2>
                                    <p
                                        class="mt-1 max-w-md text-sm text-muted-foreground"
                                    >
                                        {{ widget.description }}
                                    </p>
                                    <div
                                        class="mt-2 flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
                                    >
                                        <span>{{
                                            dataSourceOptions.find(
                                                (option) =>
                                                    option.value ===
                                                    widgetSettings[widget.id]
                                                        ?.dataSource,
                                            )?.label
                                        }}</span>
                                        <span
                                            class="h-1 w-1 rounded-full bg-muted-foreground/50"
                                        />
                                        <span>{{
                                            widgetSettings[widget.id]?.chartType
                                        }}</span>
                                    </div>
                                </div>

                                <div class="flex shrink-0 gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-lg border border-sidebar-border/70 bg-background/80 p-2 text-muted-foreground transition hover:text-foreground dark:border-sidebar-border"
                                        :aria-label="`Atur ${widget.title}`"
                                        @click.stop="
                                            openSettingsWidgetId =
                                                openSettingsWidgetId ===
                                                widget.id
                                                    ? null
                                                    : widget.id
                                        "
                                    >
                                        <Settings2 class="size-4" />
                                    </button>
                                    <span
                                        class="inline-flex items-center justify-center rounded-lg border border-sidebar-border/70 bg-background/80 p-2 text-muted-foreground dark:border-sidebar-border"
                                        :aria-label="`Geser ${widget.title}`"
                                    >
                                        <GripVertical class="size-4" />
                                    </span>
                                </div>
                            </div>

                            <div
                                v-if="openSettingsWidgetId === widget.id"
                                class="mt-3 grid gap-3 rounded-xl border border-sidebar-border/70 bg-muted/20 p-3 sm:grid-cols-3"
                            >
                                <label class="space-y-1.5">
                                    <span
                                        class="text-xs font-medium text-muted-foreground"
                                        >Grafik</span
                                    >
                                    <select
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                        :value="
                                            widgetSettings[widget.id]?.chartType
                                        "
                                        @change="
                                            updateWidgetSetting(
                                                widget.id,
                                                'chartType',
                                                selectValue($event),
                                            )
                                        "
                                    >
                                        <option
                                            v-for="option in chartTypeOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </label>
                                <label class="space-y-1.5">
                                    <span
                                        class="text-xs font-medium text-muted-foreground"
                                        >Nilai</span
                                    >
                                    <select
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                        :value="
                                            widgetSettings[widget.id]
                                                ?.dataSource
                                        "
                                        @change="
                                            updateWidgetSetting(
                                                widget.id,
                                                'dataSource',
                                                selectValue($event),
                                            )
                                        "
                                    >
                                        <option
                                            v-for="option in dataSourceOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </label>
                                <label class="space-y-1.5">
                                    <span
                                        class="text-xs font-medium text-muted-foreground"
                                        >Format</span
                                    >
                                    <select
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                        :value="
                                            widgetSettings[widget.id]
                                                ?.valueFormat
                                        "
                                        @change="
                                            updateWidgetSetting(
                                                widget.id,
                                                'valueFormat',
                                                selectValue($event),
                                            )
                                        "
                                    >
                                        <option
                                            v-for="option in valueFormatOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </label>
                            </div>
                        </div>

                        <div
                            class="relative overflow-hidden p-4"
                            :class="widget.heightClass"
                        >
                            <div
                                v-if="widgetData(widget.id).length === 0"
                                class="flex h-full min-h-40 items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 text-sm text-muted-foreground"
                            >
                                Belum ada data.
                            </div>

                            <div
                                v-else-if="
                                    widgetSettings[widget.id]?.chartType ===
                                    'metric'
                                "
                                class="grid h-full min-h-40 content-center gap-3 sm:grid-cols-2"
                            >
                                <div
                                    v-for="point in widgetData(widget.id)"
                                    :key="point.label"
                                    class="rounded-xl border border-sidebar-border/70 bg-muted/20 p-4"
                                >
                                    <p class="text-xs text-muted-foreground">
                                        {{ point.label }}
                                    </p>
                                    <p
                                        class="mt-2 text-2xl font-semibold text-foreground"
                                    >
                                        {{
                                            formatValue(
                                                point.value,
                                                widgetSettings[widget.id]
                                                    ?.valueFormat ?? 'number',
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-else-if="
                                    widgetSettings[widget.id]?.chartType ===
                                    'bar'
                                "
                                class="flex min-h-48 items-end gap-3 overflow-x-auto px-1 pb-2"
                            >
                                <div
                                    v-for="point in widgetData(widget.id)"
                                    :key="point.label"
                                    class="flex min-w-16 flex-1 flex-col items-center gap-2"
                                >
                                    <div
                                        class="flex h-40 w-full items-end rounded-t-lg bg-muted/40"
                                    >
                                        <div
                                            class="w-full rounded-t-lg bg-teal-600 transition-all"
                                            :style="{
                                                height: `${Math.max(6, (point.value / maxValue(widgetData(widget.id))) * 100)}%`,
                                            }"
                                        />
                                    </div>
                                    <p
                                        class="max-w-24 truncate text-xs text-muted-foreground"
                                        :title="point.label"
                                    >
                                        {{ point.label }}
                                    </p>
                                    <p
                                        class="text-xs font-medium text-foreground"
                                    >
                                        {{
                                            formatValue(
                                                point.value,
                                                widgetSettings[widget.id]
                                                    ?.valueFormat ?? 'number',
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-else-if="
                                    widgetSettings[widget.id]?.chartType ===
                                    'line'
                                "
                                class="min-h-48"
                            >
                                <svg
                                    viewBox="0 0 100 100"
                                    class="h-56 w-full overflow-visible"
                                >
                                    <polyline
                                        :points="
                                            linePoints(widgetData(widget.id))
                                        "
                                        fill="none"
                                        stroke="#0f766e"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="3"
                                    />
                                    <circle
                                        v-for="point in linePoints(
                                            widgetData(widget.id),
                                        ).split(' ')"
                                        :key="point"
                                        :cx="Number(point.split(',')[0])"
                                        :cy="Number(point.split(',')[1])"
                                        r="1.8"
                                        fill="#0f766e"
                                    />
                                </svg>
                                <div
                                    class="mt-2 flex justify-between gap-3 text-xs text-muted-foreground"
                                >
                                    <span>{{
                                        widgetData(widget.id)[0]?.label
                                    }}</span>
                                    <span>{{
                                        widgetData(widget.id)[
                                            widgetData(widget.id).length - 1
                                        ]?.label
                                    }}</span>
                                </div>
                            </div>

                            <div
                                v-else
                                class="grid min-h-48 gap-4 sm:grid-cols-[14rem_minmax(0,1fr)] sm:items-center"
                            >
                                <svg
                                    viewBox="0 0 42 42"
                                    class="mx-auto size-52"
                                >
                                    <circle
                                        cx="21"
                                        cy="21"
                                        r="15.915"
                                        fill="transparent"
                                        stroke="hsl(var(--muted))"
                                        stroke-width="6"
                                    />
                                    <circle
                                        v-for="segment in donutSegments(
                                            widgetData(widget.id),
                                        )"
                                        :key="segment.point.label"
                                        cx="21"
                                        cy="21"
                                        r="15.915"
                                        fill="transparent"
                                        :stroke="segment.color"
                                        stroke-width="6"
                                        :stroke-dasharray="`${segment.percent} ${100 - segment.percent}`"
                                        :stroke-dashoffset="segment.offset"
                                    />
                                </svg>
                                <div class="space-y-2">
                                    <div
                                        v-for="(
                                            segment, index
                                        ) in donutSegments(
                                            widgetData(widget.id),
                                        )"
                                        :key="segment.point.label"
                                        class="flex items-center justify-between gap-3 rounded-lg bg-muted/30 px-3 py-2 text-sm"
                                    >
                                        <span
                                            class="flex min-w-0 items-center gap-2"
                                        >
                                            <span
                                                class="size-2.5 shrink-0 rounded-full"
                                                :style="{
                                                    backgroundColor:
                                                        segment.color,
                                                }"
                                            />
                                            <span class="truncate">{{
                                                segment.point.label
                                            }}</span>
                                        </span>
                                        <span class="font-medium">{{
                                            formatValue(
                                                segment.point.value,
                                                widgetSettings[widget.id]
                                                    ?.valueFormat ?? 'number',
                                            )
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </section>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.drag-handle:active {
    cursor: grabbing;
}

:global(.gu-mirror) {
    width: min(100%, 72rem);
    border-radius: 1rem;
    background: hsl(var(--background));
    box-shadow: 0 24px 60px rgb(15 23 42 / 0.18);
}

:global(.gu-transit) {
    opacity: 0.35;
}
</style>
