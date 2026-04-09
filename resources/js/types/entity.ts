import type { BreadcrumbItem } from './navigation';
import type { SpreadsheetColumn } from '@/components/ProjectDataTable.vue';

export type EntityTableRow = Record<string, unknown>;

export type EntityIndexPageProps = {
    rows: EntityTableRow[];
    columns: SpreadsheetColumn[];
    headTitle: string;
    title: string;
    breadcrumbs: BreadcrumbItem[];
    description?: string;
    note?: string;
    rowKeyField?: string;
    createLabel?: string;
    emptyText?: string;
    stretchToViewport?: boolean;
    showCreateButton?: boolean;
    introTitle?: string;
    introDescription?: string;
    introBadge?: string;
};
