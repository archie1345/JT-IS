export type ProjectStatus = 'planning' | 'ongoing' | 'completed';
export type PaymentStatus = 'pending' | 'partial' | 'paid' | 'overdue';
export type Mode = 'create' | 'edit';

export type ProjectDetails = {
    id: number | null;
    clientId: number | null;
    name: string;
    clientName: string | null;
    clientContact: string | null;
    contractNumber: string | null;
    contractValue: number;
    location: string | null;
    startDate: string | null;
    endDate: string | null;
    status: ProjectStatus;
    paymentStatus: PaymentStatus;
    latestProgressPercent: number | null;
    latestProgressNote: string | null;
};

export type ClientOption = {
    id: number;
    name: string | null;
    contact: string | null;
};

export type DocumentItem = {
    label: string;
    detail: string;
    status: 'available' | 'missing';
    url?: string | null;
};

export type UploadedDocument = {
    id: number;
    name: string;
    originalName: string;
    url: string;
    mimeType: string | null;
    size: number | null;
    createdAt: string | null;
};

export type ProgressSnapshot = {
    reportScore: number;
    projectStatusScore: number;
    paymentStatusScore: number;
    overallProgress: number;
};