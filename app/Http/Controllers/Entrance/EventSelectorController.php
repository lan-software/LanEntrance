<?php

namespace App\Http\Controllers\Entrance;

use App\Http\Controllers\Controller;
use App\Services\LanCoreClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EventSelectorController extends Controller
{
    public function __construct(private readonly LanCoreClient $client) {}

    public function index(): JsonResponse
    {
        try {
            $events = $this->client->getEvents();
        } catch (\Throwable) {
            $events = [];
        }

        return response()->json(['events' => $events]);
    }

    public function select(Request $request): RedirectResponse
    {
        $request->validate([
            'event_id' => ['required', 'integer'],
            'event_name' => ['required', 'string', 'max:255'],
        ]);

        $request->session()->put('entrance_event_id', (int) $request->input('event_id'));
        $request->session()->put('entrance_event_name', $request->input('event_name'));

        return back();
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->session()->forget(['entrance_event_id', 'entrance_event_name']);

        return back();
    }
}
