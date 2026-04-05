<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppHeader from '@/components/AppHeader.vue';
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const pageBreadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Billing',
        href: '/billing-test',
    },
];

const sampleBreadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Billing Test',
        href: '/billing-test',
    },
    {
        title: 'Components',
        href: '/billing-test',
    },
];
</script>

<template>
    <Head title="Billing Test" />

    <AppLayout :breadcrumbs="pageBreadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <section class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <h1 class="text-2xl font-semibold tracking-tight">Component Map</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    The real top bar on this page is the sidebar layout header above this content.
                    Everything below is a labeled preview so you can see which component is which.
                </p>
            </section>

            <section class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <Heading
                    title="What is live on this page"
                    description="This page itself is rendered through AppLayout, which uses AppSidebarLayout and places AppSidebarHeader above the slot content."
                />
                <div class="mt-4 rounded-lg border border-dashed border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <p class="text-sm font-medium">Live layout chain</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        AppLayout -> AppSidebarLayout -> AppSidebar + AppContent + AppSidebarHeader -> this page content
                    </p>
                </div>
            </section>

            <section class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <Heading
                    title="Standalone AppSidebarHeader"
                    description="This is the compact header used by the sidebar layout. The actual page header above looks like this component."
                />
                <div class="mt-4 overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                    <AppSidebarHeader :breadcrumbs="sampleBreadcrumbs" />
                </div>
            </section>

            <section class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <Heading
                    title="Standalone AppHeader"
                    description="This is the separate full top navigation header component. If edits here do not affect your current page, that page is not using the header layout."
                />
                <div class="mt-4 overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                    <AppHeader :breadcrumbs="sampleBreadcrumbs" />
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                    <Heading
                        title="AppearanceTabs"
                        description="Theme switcher component"
                    />
                    <div class="mt-4">
                        <AppearanceTabs />
                    </div>
                </section>

                <section class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                    <Heading
                        title="Heading"
                        description="Reusable title and description block"
                    />
                    <div class="mt-4 space-y-4">
                        <Heading
                            title="Default Heading"
                            description="This is the default heading variant."
                        />
                        <Heading
                            title="Small Heading"
                            description="This is the small heading variant."
                            variant="small"
                        />
                    </div>
                </section>

                <section class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                    <Heading
                        title="AppLogo and AppLogoIcon"
                        description="Branding components used in the app shell and header areas"
                    />
                    <div class="mt-4 flex flex-col gap-6">
                        <div>
                            <p class="mb-2 text-sm font-medium">AppLogo</p>
                            <div class="inline-flex items-center rounded-lg bg-sidebar p-3 text-sidebar-foreground">
                                <AppLogo />
                            </div>
                        </div>
                        <div>
                            <p class="mb-2 text-sm font-medium">AppLogoIcon</p>
                            <div class="inline-flex rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border">
                                <AppLogoIcon className="h-10 w-10" />
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                    <Heading
                        title="PlaceholderPattern"
                        description="Background pattern utility used in placeholder cards"
                    />
                    <div class="mt-4">
                        <div class="relative h-48 overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                            <PlaceholderPattern />
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="rounded-md bg-background/90 px-3 py-1 text-sm font-medium backdrop-blur">
                                    PlaceholderPattern preview
                                </span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
