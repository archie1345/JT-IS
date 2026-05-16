export type ProjectStatus = 'planning' | 'ongoing' | 'completed';
export type PaymentStatus = 'pending' | 'paid' | 'overdue' | 'partial';
export type Mode = 'create' | 'edit';

export type ClientOption = {
    id: number;
    name: string;
    contact: null | string;
};

export type DocumentItem = {
    label: string;
    detail: string;
    status: 'available' | 'missing';
    url: null | string;
};

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

export type ProgressSnapshot = {
    reportScore: number;
    projectStatusScore: number;
    paymentStatusScore: number;
    overallProgress: number;
};

export type ProjectDetails = {
    id: null | number;
    clientId: null | number;
    name: string;
    clientName: null | string;
    clientContact: null | string;
    contractNumber: null | string;
    contractValue: number;
    location: null | string;
    startDate: null | string;
    endDate: null | string;
    status: ProjectStatus;
    paymentStatus: PaymentStatus;
    latestProgressPercent: null | number;
    latestProgressNote: null | string;
};

export type ProjectItem = {
    id: number;
    projectName: string;
    client: string;
    estPrice: number;
    deadline: string;
    paymentStatus: PaymentStatus;
    projectStatus: ProjectStatus;
};

export type ProjectsPageProps = {
    projects?: ProjectItem[];
    data?: ProjectItem[];
    activeClientId?: number | null;
};
