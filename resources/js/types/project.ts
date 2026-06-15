export type ProjectStatus = 'planning' | 'ongoing' | 'completed';
export type ProjectHealthStatus =
    | 'On Track'
    | 'Warning'
    | 'Critical'
    | 'On Hold';
export type PaymentStatus = 'pending' | 'paid' | 'overdue' | 'partial';
export type Mode = 'create' | 'edit';

export type UploadedDocument = {
    id: number;
    projectId?: number | null;
    projectName?: string | null;
    name: string;
    originalName: string;
    url: string;
    mimeType: null | string;
    size: null | number;
    documentType?: null | string;
    componentType?: null | string;
    componentId?: null | number;
    ocrText?: null | string;
    ocrEngine?: null | string;
    ocrProcessedAt?: null | string;
    ocrStatus?: 'failed' | 'not_configured' | 'not_processed' | 'processed';
    createdAt: null | string;
};

export type DocumentConnectionOption = {
    value: string;
    label: string;
    hint?: null | string;
    componentType: string;
    componentId?: null | number;
    projectId?: null | number;
};

export type ProjectDocumentGroupRecord = {
    id: number;
    title: string;
    detail: string;
    value?: null | string;
    url: string;
};

export type ProjectDocumentGroup = {
    key: string;
    label: string;
    description: string;
    count: number;
    listUrl: string;
    createKind?:
        | 'rab'
        | 'rap'
        | 'progress_report'
        | 'invoice'
        | 'project_cost'
        | 'pipeline'
        | 'fund_request';
    records: ProjectDocumentGroupRecord[];
};

export type ProgressSnapshot = {
    reportScore: number;
    projectStatusScore: number;
    paymentStatusScore: number;
    overallProgress: number;
};

export type ProjectDetails = {
    id: null | number;
    name: string;
    contractNumber: null | string;
    contractValue: number;
    location: null | string;
    startDate: null | string;
    endDate: null | string;
    status: ProjectStatus;
    projectHealthStatus?: ProjectHealthStatus;
    warnings?: Array<{ type: string; level: string; message: string }>;
    rabTotal?: number;
    rapTotal?: number;
    realizedCostTotal?: number;
    paymentStatus: PaymentStatus;
    latestProgressPercent: null | number;
    latestProgressNote: null | string;
    latestProgressApproved?: boolean;
    latestApprovedProgressPercent?: null | number;
};

export type ProjectItem = {
    id: number;
    projectName: string;
    estPrice: number;
    deadline: string;
    paymentStatus: PaymentStatus;
    projectStatus: ProjectStatus;
    projectHealthStatus?: ProjectHealthStatus;
};

export type ProjectsPageProps = {
    projects?: ProjectItem[];
    data?: ProjectItem[];
};
