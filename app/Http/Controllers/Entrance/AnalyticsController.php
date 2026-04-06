<?php

namespace App\Http\Controllers\Entrance;

use App\Http\Controllers\Controller;
use App\Services\LanCoreClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Inertia\Inertia;
use Inertia\Response;

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
            return $this->client->getEntranceStats();
        } catch (ConnectionException|RequestException) {
            return [
                'error' => true,
                'message' => 'Unable to load analytics — LanCore is unreachable.',
            ];
        }
    }
}
