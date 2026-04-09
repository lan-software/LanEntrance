<?php

namespace App\Http\Middleware;

use App\Services\LanCoreAnnouncementFeed;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'branding' => [
                'eventName' => config('entrance.event_name') ?: null,
                'eventLogo' => config('entrance.event_logo') ?: null,
                'primaryColor' => config('entrance.primary_color') ?: null,
            ],
            'entranceEventId' => $request->session()->get('entrance_event_id'),
            'entranceEventName' => $request->session()->get('entrance_event_name'),
            'demoBanner' => fn () => config('app.demo') ? [
                'message' => config('app.demo_banner_message'),
                'mailpit_url' => config('app.demo_mailpit_url'),
            ] : null,
            'announcements' => fn () => app(LanCoreAnnouncementFeed::class)->fetch(),
        ];
    }
}
