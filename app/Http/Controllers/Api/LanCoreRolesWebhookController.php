<?php

namespace App\Http\Controllers\Api;

use App\Actions\SyncUserRolesFromLanCore;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LanCoreRolesWebhookController extends Controller
{
    public function __invoke(Request $request, SyncUserRolesFromLanCore $syncRoles): JsonResponse
    {
        $body = $request->getContent();
        $secret = (string) config('lancore.roles_webhook_secret', '');
        $signature = $request->header('X-Webhook-Signature');

        if ($secret !== '') {
            abort_unless(is_string($signature) && str_starts_with($signature, 'sha256='), 403, 'Invalid signature.');

            $expected = 'sha256='.hash_hmac('sha256', $body, $secret);
            abort_unless(hash_equals($expected, $signature), 403, 'Invalid signature.');
        }

        abort_unless($request->header('X-Webhook-Event') === 'user.roles_updated', 400, 'Unsupported webhook event.');

        $userId = $request->integer('user.id');
        $roles = $request->input('user.roles');

        abort_unless($userId > 0 && is_array($roles), 422, 'Invalid payload.');

        $user = User::query()->where('lancore_user_id', $userId)->first();

        if ($user === null) {
            return response()->json(['status' => 'ignored'], 202);
        }

        $syncRoles->handle($user, array_values(array_filter($roles, 'is_string')));

        return response()->json(['status' => 'ok']);
    }
}