<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ScanLine } from 'lucide-vue-next';

defineProps<{
    canRegister: boolean;
}>();
</script>

<template>
    <Head title="LanEntrance — Event Check-In" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header
            class="mx-auto flex w-full max-w-5xl items-center justify-between px-6 py-6"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary"
                >
                    <ScanLine class="h-5 w-5 text-primary-foreground" />
                </div>
                <span class="text-lg font-semibold tracking-tight"
                    >LanEntrance</span
                >
            </div>
            <div class="flex items-center gap-3">
                <Link
                    href="/login"
                    class="rounded-full border border-border px-5 py-2 text-sm font-medium text-muted-foreground transition hover:border-primary hover:text-foreground"
                >
                    Sign in
                </Link>
                <Link
                    v-if="canRegister"
                    href="/register"
                    class="rounded-full bg-primary px-5 py-2 text-sm font-medium text-primary-foreground transition hover:bg-primary/90"
                >
                    Register
                </Link>
            </div>
        </header>

        <main
            class="flex flex-1 flex-col items-center justify-center px-6 text-center"
        >
            <div
                class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10"
            >
                <ScanLine class="h-8 w-8 text-primary" />
            </div>
            <h1
                class="mt-6 max-w-2xl text-4xl leading-tight font-bold sm:text-5xl"
            >
                Fast, smooth check-in for every attendee.
            </h1>
            <p
                class="mt-4 max-w-lg text-lg leading-relaxed text-muted-foreground"
            >
                LanEntrance handles event entry — scan tickets, verify
                identities, and track attendance. Get people through the door
                and into the action.
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                <Link
                    href="/login"
                    class="rounded-xl bg-primary px-7 py-3 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90"
                >
                    Open check-in dashboard
                </Link>
                <a
                    href="/auth/redirect"
                    class="rounded-xl border border-border px-7 py-3 text-sm font-medium text-muted-foreground transition hover:border-primary hover:text-foreground"
                >
                    Login via LanCore
                </a>
            </div>

            <div class="mx-auto mt-20 grid max-w-3xl gap-6 sm:grid-cols-3">
                <div
                    class="rounded-xl border border-border bg-card p-6 text-left"
                >
                    <div
                        class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-primary"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M3 7V5a2 2 0 0 1 2-2h2" />
                            <path d="M17 3h2a2 2 0 0 1 2 2v2" />
                            <path d="M21 17v2a2 2 0 0 1-2 2h-2" />
                            <path d="M7 21H5a2 2 0 0 1-2-2v-2" />
                            <path d="M7 12h10" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold">Ticket Scanning</h3>
                    <p
                        class="mt-2 text-sm leading-relaxed text-muted-foreground"
                    >
                        Scan QR codes or look up tickets manually. Validate in
                        seconds.
                    </p>
                </div>
                <div
                    class="rounded-xl border border-border bg-card p-6 text-left"
                >
                    <div
                        class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-primary"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                            />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold">Attendance Tracking</h3>
                    <p
                        class="mt-2 text-sm leading-relaxed text-muted-foreground"
                    >
                        See who's checked in, who's pending, and real-time
                        headcounts.
                    </p>
                </div>
                <div
                    class="rounded-xl border border-border bg-card p-6 text-left"
                >
                    <div
                        class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-primary"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"
                            />
                            <polyline points="10 17 15 12 10 7" />
                            <line x1="15" x2="3" y1="12" y2="12" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold">LanCore SSO</h3>
                    <p
                        class="mt-2 text-sm leading-relaxed text-muted-foreground"
                    >
                        Staff authenticate via LanCore. Roles and permissions
                        synced automatically.
                    </p>
                </div>
            </div>
        </main>

        <footer class="py-8 text-center text-xs text-muted-foreground">
            Powered by LanEntrance &mdash; Part of the Lan-Software ecosystem
        </footer>
    </div>
</template>
