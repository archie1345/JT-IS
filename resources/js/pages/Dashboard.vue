<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { GripVertical } from 'lucide-vue-next';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
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

type StoredDashboardLayout = {
    order?: string[];
    visible?: string[];
};

type DashboardPageProps = {
    auth: Auth & {
        user: Auth['user'] & {
            dashboard_layout?: StoredDashboardLayout | string[] | null;
        };
    };
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
        description: 'Placeholder for a graph, KPI block, or any summary widget.',
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

const defaultWidgetIds = defaultWidgets.map((widget) => widget.id);
const page = usePage<DashboardPageProps>();
const dashboardList = ref<HTMLElement | null>(null);
const widgets = ref<DashboardWidget[]>([...defaultWidgets]);
const visibleWidgetIds = ref<string[]>([...defaultWidgetIds]);
const userId = computed(() => page.props.auth?.user?.id ?? 'guest');
const userName = computed(() => page.props.auth?.user?.name ?? 'this user');
const storageKey = computed(() => `dashboard-layout:user-${userId.value}`);
const visibleWidgets = computed(() =>
    widgets.value.filter((widget) => visibleWidgetIds.value.includes(widget.id)),
);
let drake: Drake | null = null;
let saveTimeout: ReturnType<typeof setTimeout> | null = null;

const orderWidgets = (widgetIds: string[]) => {
    const widgetMap = new Map(defaultWidgets.map((widget) => [widget.id, widget]));
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

const getSavedLayout = (): StoredDashboardLayout | null => {
    const sqlLayout = page.props.auth?.user?.dashboard_layout;

    if (Array.isArray(sqlLayout)) {
        return {
            order: normalizeWidgetIds(sqlLayout) ?? [...defaultWidgetIds],
            visible: [...defaultWidgetIds],
        };
    }

    if (sqlLayout && typeof sqlLayout === 'object' && !Array.isArray(sqlLayout)) {
        return {
            order: normalizeWidgetIds(sqlLayout.order) ?? [...defaultWidgetIds],
            visible: normalizeWidgetIds(sqlLayout.visible) ?? [...defaultWidgetIds],
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
                order: normalizeWidgetIds(parsedLayout) ?? [...defaultWidgetIds],
                visible: [...defaultWidgetIds],
            };
        }

        return {
            order: normalizeWidgetIds(parsedLayout.order) ?? [...defaultWidgetIds],
            visible: normalizeWidgetIds(parsedLayout.visible) ?? [...defaultWidgetIds],
        };
    } catch {
        return null;
    }
};

const getLayoutPayload = (): StoredDashboardLayout => ({
    order: widgets.value.map((widget) => widget.id),
    visible: visibleWidgetIds.value,
});

const hydrateWidgets = () => {
    const savedLayout = getSavedLayout();

    widgets.value = savedLayout?.order
        ? orderWidgets(savedLayout.order)
        : [...defaultWidgets];
    visibleWidgetIds.value = savedLayout?.visible ?? [...defaultWidgetIds];
};

const persistWidgetsLocally = () => {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(storageKey.value, JSON.stringify(getLayoutPayload()));
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
        visibleWidgetIds.value = [
            ...visibleWidgetIds.value,
            widgetId,
        ].sort(
            (left, right) =>
                defaultWidgetIds.indexOf(left) - defaultWidgetIds.indexOf(right),
        );
    }

    queuePersist();
};

const initializeDragula = async () => {
    if (!dashboardList.value || typeof window === 'undefined') {
        return;
    }

    if (!(window as Window & typeof globalThis & { global?: Window }).global) {
        (window as Window & typeof globalThis & { global?: Window }).global = window;
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
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="rounded-2xl border border-sidebar-border/70 bg-background/70 p-4 dark:border-sidebar-border">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-medium text-foreground">
                            Drag your widgets into place
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Widget order and visibility are now saved in SQL for
                            {{ userName }}
                            and mirrored to
                            <code class="rounded bg-muted px-1.5 py-0.5 text-xs">localStorage</code>
                            as a fallback.
                        </p>
                    </div>

                    <div class="rounded-xl border border-sidebar-border/70 bg-background/80 p-3 dark:border-sidebar-border lg:min-w-80">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                            Filter widgets
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <label
                                v-for="widget in defaultWidgets"
                                :key="`filter-${widget.id}`"
                                class="inline-flex cursor-pointer items-center gap-2 rounded-full border px-3 py-2 text-sm transition-colors"
                                :class="visibleWidgetIds.includes(widget.id)
                                    ? 'border-foreground/20 bg-foreground text-background'
                                    : 'border-sidebar-border/70 bg-background text-foreground dark:border-sidebar-border'"
                            >
                                <input
                                    type="checkbox"
                                    class="sr-only"
                                    :checked="visibleWidgetIds.includes(widget.id)"
                                    :disabled="visibleWidgetIds.length === 1 && visibleWidgetIds.includes(widget.id)"
                                    @change="toggleWidgetVisibility(widget.id)"
                                >
                                <span
                                    class="size-2.5 rounded-full"
                                    :class="visibleWidgetIds.includes(widget.id)
                                        ? 'bg-background'
                                        : 'bg-muted-foreground/50'"
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

            <div v-else ref="dashboardList" class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <section
                    v-for="widget in visibleWidgets"
                    :key="widget.id"
                    :data-widget-id="widget.id"
                    class="dashboard-card group overflow-hidden rounded-2xl border border-sidebar-border/70 bg-background/90 shadow-sm dark:border-sidebar-border"
                >
                    <div
                        data-drag-handle="true"
                        class="drag-handle flex cursor-grab select-none items-start justify-between gap-4 border-b border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <div>
                            <h2 class="text-sm font-semibold text-foreground">
                                {{ widget.title }}
                            </h2>
                            <p class="mt-1 max-w-md text-sm text-muted-foreground">
                                {{ widget.description }}
                            </p>
                        </div>

                        <span
                            class="inline-flex shrink-0 items-center justify-center rounded-lg border border-sidebar-border/70 bg-background/80 p-2 text-muted-foreground dark:border-sidebar-border"
                            :aria-label="`Drag ${widget.title}`"
                        >
                            <GripVertical class="size-4" />
                        </span>
                    </div>

                    <div
                        class="relative flex items-center justify-center overflow-hidden p-4"
                        :class="widget.heightClass"
                    >
                        <PlaceholderPattern />
                        <div class="relative z-10 rounded-xl border border-dashed border-sidebar-border/80 bg-background/85 px-4 py-3 text-center backdrop-blur-sm dark:border-sidebar-border">
                            <p class="text-sm font-medium text-foreground">
                                Drop a graph here later
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                For now this placeholder block is fully draggable.
                            </p>
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

