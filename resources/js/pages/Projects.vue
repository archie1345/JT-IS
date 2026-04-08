<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ProjectDataTable, { type ProjectItem } from '@/components/ProjectDataTable.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    projects: ProjectItem[];
    activeClientId?: number | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Projects',
        href: '/projects',
    },
];

const openProject = (project: ProjectItem) => {
    router.get(`/projects/${project.id}`);
};

const createProject = () => {
    router.get('/projects/create');
};
</script>

<template>
    <Head title="Projects" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <ProjectDataTable
            :projects="props.projects"
            :active-client-id="props.activeClientId"
            @open-project="openProject"
            @create-project="createProject"
        />
    </AppLayout>
</template>
