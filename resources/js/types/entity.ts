import type { BreadcrumbItem } from './navigation';

export type EntityTableRow = Record<string, unknown>;

export type EntityIndexPageProps = {
    rows: EntityTableRow[];
    columns: {
        key: string;
        label: string;
        accessor?: (row: EntityTableRow) => unknown;
        visible?: boolean;
        sortable?: boolean;
        filterable?: boolean;
        widthClass?: string;
    }[];
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
};
