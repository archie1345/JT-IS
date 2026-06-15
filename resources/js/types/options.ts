export type ProjectOption = {
    value: number;
    label: string;
    hint?: null | string;
};

export type ConnectionOption = {
    value: string;
    label: string;
    hint?: null | string;
    componentType: string;
    componentId?: null | number;
    projectId?: null | number;
};