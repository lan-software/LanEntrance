<?php

namespace App\Http\Controllers\Entrance;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use LanSoftware\LanCoreClient\Exceptions\LanCoreException;
use LanSoftware\LanCoreClient\LanCoreClient;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly LanCoreClient $client,
    ) {}

    public function __invoke(): Response
    {
        $stats = $this->fetchStats();

        return Inertia::render('entrance/Analytics', [
            'stats' => $stats,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchStats(): array
    {
        try {
            return $this->client->entrance()->stats(session('entrance_event_id'));
        } catch (LanCoreException) {
            return [
                'error' => true,
                'message' => 'Unable to load analytics — LanCore is unreachable.',
            ];
        }
    }
}
