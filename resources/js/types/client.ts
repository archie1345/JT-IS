export type ClientItem = {
    id: number;
    name: string | null;
    contact: string | null;
    projectCount: number;
    totalProjectValue: number;
};

export type ClientsPageProps = {
    clients?: ClientItem[];
    data?: ClientItem[];
};

export type ClientMode = 'create' | 'edit';

export type ClientDetail = {
    id: number | null;
    name: string;
    contact: string | null;
    projectCount: number;
    totalProjectValue: number;
    activeProjects: number;
    completedProjects: number;
    firstProjectDate: string | null;
    lastProjectDate: string | null;
    lastUpdated: string | null;
};

export type RelatedProject = {
    id: number;
    name: string;
    contractNumber: string | null;
    contractValue: number;
    status: 'planning' | 'ongoing' | 'completed';
    startDate: string | null;
    endDate: string | null;
};

export type QuickLink = {
    label: string;
    detail: string;
    url: string;
};
