<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    Calendar,
    ChevronDown,
    LayoutGrid,
    ScanLine,
    Search,
    X,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { scanner as entranceScanner } from '@/routes/entrance';
import type { NavItem } from '@/types';

const page = usePage();
const userRole = computed(
    () => (page.props.auth as { user?: { role?: string } })?.user?.role,
);

const selectedEventId = computed(
    () => page.props.entranceEventId as number | null,
);
const selectedEventName = computed(
    () => page.props.entranceEventName as string | null,
);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
        { title: 'Scanner', href: entranceScanner(), icon: ScanLine },
        { title: 'Lookup', href: '/entrance/lookup', icon: Search },
    ];

    if (['admin', 'superadmin'].includes(userRole.value ?? '')) {
        items.push({
            title: 'Analytics',
            href: '/entrance/analytics',
            icon: BarChart3,
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [];

// Event selector
interface EventOption {
    id: number;
    name: string;
    start_date: string | null;
    end_date: string | null;
}

const events = ref<EventOption[]>([]);
const showEventPicker = ref(false);
const loadingEvents = ref(false);

async function loadEvents() {
    if (events.value.length > 0) {
        showEventPicker.value = !showEventPicker.value;
        return;
    }

    loadingEvents.value = true;
    try {
        const res = await fetch('/entrance/events', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const data = await res.json();
        events.value = data.events ?? [];
        showEventPicker.value = true;
    } catch {
        events.value = [];
    } finally {
        loadingEvents.value = false;
    }
}

function selectEvent(event: EventOption) {
    router.post(
        '/entrance/events/select',
        { event_id: event.id, event_name: event.name },
        { preserveScroll: true },
    );
    showEventPicker.value = false;
}

function clearEvent() {
    router.delete('/entrance/events/select', { preserveScroll: true });
    showEventPicker.value = false;
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <!-- Event Selector (hidden when sidebar collapsed) -->
            <SidebarGroup class="group-data-[collapsible=icon]:hidden">
                <SidebarGroupLabel>Event</SidebarGroupLabel>
                <SidebarGroupContent>
                    <div class="px-2">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between rounded-md border px-3 py-2 text-sm transition hover:bg-accent"
                            @click="loadEvents"
                        >
                            <span class="flex items-center gap-2 truncate">
                                <Calendar
                                    class="size-4 shrink-0 text-muted-foreground"
                                />
                                <span
                                    v-if="selectedEventName"
                                    class="truncate font-medium"
                                    >{{ selectedEventName }}</span
                                >
                                <span v-else class="text-muted-foreground"
                                    >Select event...</span
                                >
                            </span>
                            <ChevronDown
                                class="size-3 shrink-0 text-muted-foreground"
                            />
                        </button>

                        <!-- Event picker dropdown -->
                        <div
                            v-if="showEventPicker"
                            class="mt-1 space-y-1 rounded-md border bg-popover p-1 shadow-md"
                        >
                            <button
                                v-for="event in events"
                                :key="event.id"
                                type="button"
                                class="flex w-full items-center rounded px-2 py-1.5 text-left text-sm transition hover:bg-accent"
                                :class="
                                    event.id === selectedEventId
                                        ? 'bg-accent font-medium'
                                        : ''
                                "
                                @click="selectEvent(event)"
                            >
                                {{ event.name }}
                            </button>
                            <button
                                v-if="selectedEventId"
                                type="button"
                                class="flex w-full items-center gap-1 rounded px-2 py-1.5 text-left text-sm text-muted-foreground transition hover:bg-accent"
                                @click="clearEvent"
                            >
                                <X class="size-3" /> Clear selection
                            </button>
                            <p
                                v-if="events.length === 0 && !loadingEvents"
                                class="px-2 py-1.5 text-xs text-muted-foreground"
                            >
                                No events available
                            </p>
                        </div>
                    </div>
                </SidebarGroupContent>
            </SidebarGroup>

            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
