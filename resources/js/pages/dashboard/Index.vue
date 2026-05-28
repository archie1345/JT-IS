<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { GripVertical, Settings2 } from 'lucide-vue-next';
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

type ChartType = 'bar' | 'line' | 'donut' | 'metric';
type ValueFormat = 'number' | 'currency' | 'percent';
type DashboardPoint = {
    label: string;
    value: number;
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
type RecentProject = {
    id: number;
    name: string;
    client: string;
    status: string;
    dbStatus: string;
    approvedProgress: number;
    endDate: string | null;
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
        title: 'Revenue overview',
        description:
            'Placeholder for a graph, KPI block, or any summary widget.',
        heightClass: 'min-h-72',
    },
    {
        id: 'conversion-trend',
        title: 'Conversion trend',
        description: 'Use this slot for a chart, funnel, or timeline widget.',
        heightClass: 'min-h-56',
    },
    {
        id: 'team-activity',
        title: 'Team activity',
        description: 'Good fit for a feed, alerts, or compact table.',
        heightClass: 'min-h-56',
    },
    {
        id: 'projects-health',
        title: 'projects health',
        description: 'Another placeholder area for a graph or metric card.',
        heightClass: 'min-h-56',
    },
    {
        id: 'upcoming-items',
        title: 'Upcoming items',
        description: 'Use this for tasks, reminders, or anything else later.',
        heightClass: 'min-h-56',
    },
];

const dataSourceOptions = [
    {
        value: 'projectStatus',
        label: 'Projects by status',
        defaultFormat: 'number',
    },
    {
        value: 'invoiceStatus',
        label: 'Invoice amount by status',
        defaultFormat: 'currency',
    },
    {
        value: 'costCategory',
        label: 'Costs by category',
        defaultFormat: 'currency',
    },
    {
        value: 'monthlyInvoices',
        label: 'Monthly invoices',
        defaultFormat: 'currency',
    },
    {
        value: 'monthlyCosts',
        label: 'Monthly costs',
        defaultFormat: 'currency',
    },
    {
        value: 'progressTrend',
        label: 'Progress trend',
        defaultFormat: 'percent',
    },
    { value: 'totals', label: 'Executive totals', defaultFormat: 'currency' },
] as const;
const chartTypeOptions: Array<{ value: ChartType; label: string }> = [
    { value: 'bar', label: 'Bar' },
    { value: 'line', label: 'Line' },
    { value: 'donut', label: 'Donut' },
    { value: 'metric', label: 'Metric' },
];
const valueFormatOptions: Array<{ value: ValueFormat; label: string }> = [
    { value: 'number', label: 'Number' },
    { value: 'currency', label: 'Currency' },
    { value: 'percent', label: 'Percent' },
];

const defaultWidgetIds = defaultWidgets.map((widget) => widget.id);
const isChartType = (value: unknown): value is ChartType =>
    chartTypeOptions.some((option) => option.value === value);
const isValueFormat = (value: unknown): value is ValueFormat =>
    valueFormatOptions.some((option) => option.value === value);
const isDataSource = (value: unknown): value is string =>
    typeof value === 'string' &&
    dataSourceOptions.some((option) => option.value === value);
const page = usePage<DashboardPageProps>();
const dashboardList = ref<HTMLElement | null>(null);
const widgets = ref<DashboardWidget[]>([...defaultWidgets]);
const visibleWidgetIds = ref<string[]>([...defaultWidgetIds]);
const widgetSettings = ref<Record<string, WidgetSetting>>({});
const openSettingsWidgetId = ref<string | null>(null);
const userId = computed(() => page.props.auth?.user?.id ?? 'guest');
const userName = computed(() => page.props.auth?.user?.name ?? 'this user');
const storageKey = computed(() => `dashboard-layout:user-${userId.value}`);
const dashboardData = computed<DashboardData>(
    () => page.props.dashboardData ?? {},
);
const mvpSummary = computed(
    () => (dashboardData.value.mvpSummary as DashboardPoint[] | undefined) ?? [],
);
const problemProjects = computed(
    () =>
        (dashboardData.value.problemProjects as ProblemProject[] | undefined) ??
        [],
);
const recentProjects = computed(
    () =>
        (dashboardData.value.recentProjects as RecentProject[] | undefined) ??
        [],
);
const visibleWidgets = computed(() =>
    widgets.value.filter((widget) =>
        visibleWidgetIds.value.includes(widget.id),
    ),
);
let drake: Drake | null = null;
let saveTimeout: ReturnType<typeof setTimeout> | null = null;

const orderWidgets = (widgetIds: string[]) => {
    const widgetMap = new Map(
        defaultWidgets.map((widget) => [widget.id, widget]),
    );
    const ordered = widgetIds
        .map((widgetId) => widgetMap.get(widgetId))
        .filter((widget): widget is DashboardWidget => Boolean(widget));

    const missingWidgets = defaultWidgets.filter(
        (widget) => !widgetIds.includes(widget.id),
    );

    return [...ordered, ...missingWidgets];
};

const normalizeWidgetIds = (widgetIds: string[] | null | undefined) => {
    if (!Array.isArray(widgetIds)) {
        return null;
    }

    const validIds = widgetIds.filter((widgetId) =>
        defaultWidgetIds.includes(widgetId),
    );

    return validIds.length > 0 ? Array.from(new Set(validIds)) : null;
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
            order: normalizeWidgetIds(sqlLayout) ?? [...defaultWidgetIds],
            visible: [...defaultWidgetIds],
        };
    }

    if (
        sqlLayout &&
        typeof sqlLayout === 'object' &&
        !Array.isArray(sqlLayout)
    ) {
        return {
            order: normalizeWidgetIds(sqlLayout.order) ?? [...defaultWidgetIds],
            visible: normalizeWidgetIds(sqlLayout.visible) ?? [
                ...defaultWidgetIds,
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
                order: normalizeWidgetIds(parsedLayout) ?? [
                    ...defaultWidgetIds,
                ],
                visible: [...defaultWidgetIds],
            };
        }

        return {
            order: normalizeWidgetIds(parsedLayout.order) ?? [
                ...defaultWidgetIds,
            ],
            visible: normalizeWidgetIds(parsedLayout.visible) ?? [
                ...defaultWidgetIds,
            ],
            settings: parsedLayout.settings,
        };
    } catch {
        return null;
    }
};

const getLayoutPayload = (): StoredDashboardLayout => ({
    order: widgets.value.map((widget) => widget.id),
    visible: visibleWidgetIds.value,
    settings: widgetSettings.value,
});

const hydrateWidgets = () => {
    const savedLayout = getSavedLayout();

    widgets.value = savedLayout?.order
        ? orderWidgets(savedLayout.order)
        : [...defaultWidgets];
    visibleWidgetIds.value = savedLayout?.visible ?? [...defaultWidgetIds];
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

const syncWidgetsFromDom = () => {
    if (!dashboardList.value) {
        return;
    }

    const widgetIds = Array.from(dashboardList.value.children)
        .map((child) =>
            child instanceof HTMLElement ? child.dataset.widgetId : undefined,
        )
        .filter((widgetId): widgetId is string => Boolean(widgetId));

    widgets.value = orderWidgets(widgetIds);
    queuePersist();
};

const toggleWidgetVisibility = (widgetId: string) => {
    if (visibleWidgetIds.value.includes(widgetId)) {
        if (visibleWidgetIds.value.length === 1) {
            return;
        }

        visibleWidgetIds.value = visibleWidgetIds.value.filter(
            (visibleId) => visibleId !== widgetId,
        );
    } else {
        visibleWidgetIds.value = [...visibleWidgetIds.value, widgetId].sort(
            (left, right) =>
                defaultWidgetIds.indexOf(left) -
                defaultWidgetIds.indexOf(right),
        );
    }

    queuePersist();
};

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
        'On Track': 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-500/25',
        Warning: 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-500/25',
        Critical: 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-500/25',
        'On Hold': 'bg-slate-500/15 text-slate-600 ring-1 ring-slate-500/25',
    })[status] ?? 'bg-slate-500/15 text-slate-600 ring-1 ring-slate-500/25';

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
        syncWidgetsFromDom();
    });

    activeDrake.on('cancel', () => {
        syncWidgetsFromDom();
    });

    drake = activeDrake;
};

onMounted(async () => {
    hydrateWidgets();
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
            class="flex min-h-[calc(100vh-8rem)] flex-1 flex-col gap-3 rounded-xl p-2 sm:gap-4 sm:p-4"
        >
            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                <div
                    v-for="point in mvpSummary"
                    :key="point.label"
                    class="rounded-xl border border-sidebar-border/70 bg-background/90 p-4 shadow-sm"
                >
                    <p class="text-xs text-muted-foreground">
                        {{ point.label }}
                    </p>
                    <p class="mt-2 text-xl font-semibold text-foreground">
                        {{
                            point.label.toLowerCase().includes('value') ||
                            point.label.toLowerCase().includes('cost') ||
                            point.label.toLowerCase().includes('amount')
                                ? formatValue(point.value, 'currency')
                                : formatValue(point.value, 'number')
                        }}
                    </p>
                </div>
            </section>

            <section
                class="grid gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]"
            >
                <div
                    class="rounded-2xl border border-sidebar-border/70 bg-background/90 p-4 shadow-sm"
                >
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-foreground">
                                Problem Projects
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Budget, payment, and progress warnings for the
                                demo flow.
                            </p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-muted-foreground">
                                <tr>
                                    <th class="py-2 pr-3 font-medium">Project</th>
                                    <th class="py-2 pr-3 font-medium">Status</th>
                                    <th class="py-2 pr-3 font-medium">RAP</th>
                                    <th class="py-2 pr-3 font-medium">Cost</th>
                                    <th class="py-2 pr-3 font-medium">Warning</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="project in problemProjects"
                                    :key="project.id"
                                    class="border-t border-sidebar-border/70"
                                >
                                    <td class="py-3 pr-3">
                                        <button
                                            class="text-left font-medium text-foreground hover:underline"
                                            @click="router.get(`/projects/${project.id}`)"
                                        >
                                            {{ project.name }}
                                        </button>
                                        <p class="text-xs text-muted-foreground">
                                            {{ project.client }}
                                        </p>
                                    </td>
                                    <td class="py-3 pr-3">
                                        <span
                                            class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                            :class="statusClass(project.status)"
                                        >
                                            {{ project.status }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-3">
                                        {{ formatValue(project.rapTotal, 'currency') }}
                                    </td>
                                    <td class="py-3 pr-3">
                                        {{ formatValue(project.realizedCost, 'currency') }}
                                    </td>
                                    <td class="py-3 pr-3 text-muted-foreground">
                                        {{ project.warnings[0]?.message ?? '-' }}
                                    </td>
                                </tr>
                                <tr v-if="problemProjects.length === 0">
                                    <td colspan="5" class="py-6 text-center text-muted-foreground">
                                        No problem projects.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-sidebar-border/70 bg-background/90 p-4 shadow-sm"
                >
                    <h2 class="text-sm font-semibold text-foreground">
                        Recent Projects / Progress
                    </h2>
                    <div class="mt-3 space-y-2">
                        <button
                            v-for="project in recentProjects"
                            :key="project.id"
                            class="flex w-full items-center justify-between gap-3 rounded-xl border border-sidebar-border/70 bg-muted/20 px-3 py-2 text-left"
                            @click="router.get(`/projects/${project.id}`)"
                        >
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium text-foreground">
                                    {{ project.name }}
                                </span>
                                <span class="text-xs text-muted-foreground">
                                    {{ project.approvedProgress }}% approved |
                                    {{ project.endDate ?? '-' }}
                                </span>
                            </span>
                            <span
                                class="shrink-0 rounded-full px-2 py-1 text-xs font-medium"
                                :class="statusClass(project.status)"
                            >
                                {{ project.status }}
                            </span>
                        </button>
                        <div
                            v-if="recentProjects.length === 0"
                            class="rounded-xl border border-dashed border-sidebar-border/70 p-6 text-center text-sm text-muted-foreground"
                        >
                            No data yet.
                        </div>
                    </div>
                </div>
            </section>

            <div
                class="rounded-2xl border border-sidebar-border/70 bg-background/70 p-4 dark:border-sidebar-border"
            >
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
                >
                    <div>
                        <p class="text-sm font-medium text-foreground">
                            Drag your widgets into place
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Widget order, visibility, chart type, and data
                            source are saved in SQL for
                            {{ userName }}
                            and mirrored to
                            <code class="rounded bg-muted px-1.5 py-0.5 text-xs"
                                >localStorage</code
                            >
                            as a fallback.
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-sidebar-border/70 bg-background/80 p-3 lg:min-w-80 dark:border-sidebar-border"
                    >
                        <p
                            class="text-xs font-semibold tracking-[0.2em] text-muted-foreground uppercase"
                        >
                            Filter widgets
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <label
                                v-for="widget in defaultWidgets"
                                :key="`filter-${widget.id}`"
                                class="inline-flex cursor-pointer items-center gap-2 rounded-full border px-3 py-2 text-sm transition-colors"
                                :class="
                                    visibleWidgetIds.includes(widget.id)
                                        ? 'border-foreground/20 bg-foreground text-background'
                                        : 'border-sidebar-border/70 bg-background text-foreground dark:border-sidebar-border'
                                "
                            >
                                <input
                                    type="checkbox"
                                    class="sr-only"
                                    :checked="
                                        visibleWidgetIds.includes(widget.id)
                                    "
                                    :disabled="
                                        visibleWidgetIds.length === 1 &&
                                        visibleWidgetIds.includes(widget.id)
                                    "
                                    @change="toggleWidgetVisibility(widget.id)"
                                />
                                <span
                                    class="size-2.5 rounded-full"
                                    :class="
                                        visibleWidgetIds.includes(widget.id)
                                            ? 'bg-background'
                                            : 'bg-muted-foreground/50'
                                    "
                                />
                                <span>{{ widget.title }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="visibleWidgets.length === 0"
                class="rounded-2xl border border-dashed border-sidebar-border/70 bg-background/70 p-8 text-center text-sm text-muted-foreground dark:border-sidebar-border"
            >
                No widgets are visible. Turn one back on from the filter above.
            </div>

            <div
                v-else
                ref="dashboardList"
                class="grid grid-cols-1 gap-4 xl:grid-cols-2"
            >
                <section
                    v-for="widget in visibleWidgets"
                    :key="widget.id"
                    :data-widget-id="widget.id"
                    class="dashboard-card group overflow-hidden rounded-2xl border border-sidebar-border/70 bg-background/90 shadow-sm dark:border-sidebar-border"
                >
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
                                    :aria-label="`Configure ${widget.title}`"
                                    @click.stop="
                                        openSettingsWidgetId =
                                            openSettingsWidgetId === widget.id
                                                ? null
                                                : widget.id
                                    "
                                >
                                    <Settings2 class="size-4" />
                                </button>
                                <span
                                    class="inline-flex items-center justify-center rounded-lg border border-sidebar-border/70 bg-background/80 p-2 text-muted-foreground dark:border-sidebar-border"
                                    :aria-label="`Drag ${widget.title}`"
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
                                    >Graph</span
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
                                    >Value</span
                                >
                                <select
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    :value="
                                        widgetSettings[widget.id]?.dataSource
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
                                        widgetSettings[widget.id]?.valueFormat
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
                            No data yet.
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
                                widgetSettings[widget.id]?.chartType === 'bar'
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
                                <p class="text-xs font-medium text-foreground">
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
                                widgetSettings[widget.id]?.chartType === 'line'
                            "
                            class="min-h-48"
                        >
                            <svg
                                viewBox="0 0 100 100"
                                class="h-56 w-full overflow-visible"
                            >
                                <polyline
                                    :points="linePoints(widgetData(widget.id))"
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
                            <svg viewBox="0 0 42 42" class="mx-auto size-52">
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
                                    v-for="(segment, index) in donutSegments(
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
                                                backgroundColor: segment.color,
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
