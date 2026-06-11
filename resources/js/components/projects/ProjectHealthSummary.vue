<script setup lang="ts">
import { computed } from 'vue';
import { FileText } from 'lucide-vue-next';
import EntityPageSection from '@/components/entity/EntityPageSection.vue';
import { Badge } from '@/components/ui/badge';
import type { ProjectDetails } from '@/types/project';

const props = defineProps<{
    project: ProjectDetails;
}>();

type HealthTone = 'critical' | 'good' | 'neutral' | 'warning';

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const projectHealthStatus = computed(
    () => props.project.projectHealthStatus ?? 'On Track',
);

const projectHealthStatusLabel = computed(
    () =>
        ({
            'On Track': 'Sesuai Rencana',
            Warning: 'Perhatian',
            Critical: 'Kritis',
            'On Hold': 'Ditahan',
        })[projectHealthStatus.value] ?? projectHealthStatus.value,
);

const projectHealthPanelClass = computed(
    () =>
        ({
            'On Track':
                'border-emerald-500/25 bg-emerald-500/10 ring-1 ring-emerald-500/15',
            Warning:
                'border-amber-500/25 bg-amber-500/10 ring-1 ring-amber-500/15',
            Critical:
                'border-rose-500/25 bg-rose-500/10 ring-1 ring-rose-500/15',
            'On Hold':
                'border-slate-500/25 bg-slate-500/10 ring-1 ring-slate-500/15',
        })[projectHealthStatus.value] ??
        'border-slate-500/25 bg-slate-500/10 ring-1 ring-slate-500/15',
);

const getProjectHealthStatusClass = (status: string) =>
    ({
        'On Track':
            'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-500/25',
        Warning: 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-500/25',
        Critical: 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-500/25',
        'On Hold': 'bg-slate-500/15 text-slate-600 ring-1 ring-slate-500/25',
    })[status] ?? 'bg-slate-500/15 text-slate-600 ring-1 ring-slate-500/25';

const healthCardClass = (tone: HealthTone) =>
    ({
        good: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700',
        warning: 'border-amber-500/20 bg-amber-500/10 text-amber-700',
        critical: 'border-rose-500/20 bg-rose-500/10 text-rose-700',
        neutral: 'border-sky-500/20 bg-sky-500/10 text-sky-700',
    })[tone];

const realizedCostTone = computed<HealthTone>(() => {
    const rapTotal = Number(props.project.rapTotal ?? 0);
    const realized = Number(props.project.realizedCostTotal ?? 0);

    if (rapTotal <= 0) return 'neutral';
    if (realized > rapTotal) return 'critical';
    if (realized / rapTotal >= 0.9) return 'warning';
    return 'good';
});

const liveProgressScore = computed(() =>
    Math.max(
        0,
        Math.min(100, Number(props.project.latestProgressPercent ?? 0)),
    ),
);

const progressHealthTone = computed<HealthTone>(() => {
    if (liveProgressScore.value >= 70) return 'good';
    if (liveProgressScore.value >= 40) return 'neutral';
    if (liveProgressScore.value > 0) return 'warning';
    return 'critical';
});
</script>

<template>
    <EntityPageSection
        title="Ringkasan Kesehatan Proyek"
        description="Ringkasan kondisi proyek berdasarkan anggaran, realisasi biaya, dan progress."
        :icon="FileText"
        :class="projectHealthPanelClass"
    >
        <div class="flex flex-wrap items-center gap-2 pb-3">
            <Badge :class="getProjectHealthStatusClass(projectHealthStatus)">
                {{ projectHealthStatusLabel }}
            </Badge>
            <span class="text-xs text-muted-foreground">
                Warna berubah mengikuti kondisi proyek.
            </span>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border p-4" :class="healthCardClass('good')">
                <p class="text-xs font-semibold uppercase">RAB Total</p>
                <p class="mt-1 text-sm font-semibold">
                    {{ formatCurrency(project.rabTotal ?? 0) }}
                </p>
            </div>

            <div
                class="rounded-xl border p-4"
                :class="healthCardClass('neutral')"
            >
                <p class="text-xs font-semibold uppercase">RAP Total</p>
                <p class="mt-1 text-sm font-semibold">
                    {{ formatCurrency(project.rapTotal ?? 0) }}
                </p>
            </div>

            <div
                class="rounded-xl border p-4"
                :class="healthCardClass(realizedCostTone)"
            >
                <p class="text-xs font-semibold uppercase">Realisasi Biaya</p>
                <p class="mt-1 text-sm font-semibold">
                    {{ formatCurrency(project.realizedCostTotal ?? 0) }}
                </p>
            </div>

            <div
                class="rounded-xl border p-4"
                :class="healthCardClass(progressHealthTone)"
            >
                <p class="text-xs font-semibold uppercase">Progress Terbaru</p>
                <p class="mt-1 text-sm font-semibold">
                    {{ project.latestProgressPercent ?? 0 }}%
                    <span class="font-normal opacity-80">
                        {{
                            project.latestProgressApproved
                                ? 'disetujui'
                                : 'draft'
                        }}
                    </span>
                </p>
            </div>
        </div>
    </EntityPageSection>
</template>
