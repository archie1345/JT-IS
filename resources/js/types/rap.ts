export type RapRow = {
    id: number;
    document_number?: null | string;
    document_date?: null | string;
    notes?: null | string;
    projectId: number;
    projectName: string;
    totalBudget: number;
    itemCount: number;
    createdAt: string | null;
    updatedAt: string | null;
};
