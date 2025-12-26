<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessGithubPushJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * GithubWebhookController
 *
 * Handles incoming GitHub webhook requests.
 * Only processes "push" events, ignores all others.
 *
 * Security:
 * - Validates GitHub webhook signature using HMAC SHA-256
 * - Rate limited via middleware
 * - Logs all incoming requests for audit
 */
class GithubWebhookController extends Controller
{
    /**
     * Handle incoming GitHub webhook.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        // Get the event type from header
        $eventType = $request->header('X-GitHub-Event');
        $deliveryId = $request->header('X-GitHub-Delivery');

        Log::info("GitHub webhook received", [
            'event' => $eventType,
            'delivery_id' => $deliveryId,
            'ip' => $request->ip(),
        ]);

        // Validate the webhook signature
        if (!$this->validateSignature($request)) {
            Log::warning("GitHub webhook signature validation failed", [
                'delivery_id' => $deliveryId,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid signature',
            ], 401);
        }

        // Handle ping event (sent when webhook is first configured)
        if ($eventType === 'ping') {
            Log::info("GitHub webhook ping received", [
                'zen' => $request->input('zen'),
                'hook_id' => $request->input('hook_id'),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Pong! Webhook configured successfully.',
            ]);
        }

        // Only process push events
        if ($eventType !== 'push') {
            Log::info("Ignoring non-push GitHub event", [
                'event' => $eventType,
                'delivery_id' => $deliveryId,
            ]);

            return response()->json([
                'status' => 'ignored',
                'message' => "Event type '{$eventType}' is not processed.",
            ]);
        }

        // Get the payload
        $payload = $request->all();

        // Validate required fields
        if (!isset($payload['repository']) || !isset($payload['commits'])) {
            Log::warning("GitHub push webhook missing required fields", [
                'delivery_id' => $deliveryId,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid payload structure',
            ], 400);
        }

        // Dispatch job to process the push asynchronously
        ProcessGithubPushJob::dispatch($payload);

        Log::info("GitHub push event queued for processing", [
            'repo' => $payload['repository']['full_name'] ?? 'unknown',
            'commits_count' => count($payload['commits']),
            'delivery_id' => $deliveryId,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Push event queued for processing.',
            'commits_received' => count($payload['commits']),
        ]);
    }

    /**
     * Validate GitHub webhook signature.
     *
     * GitHub sends a signature in the X-Hub-Signature-256 header.
     * We verify it using our webhook secret.
     *
     * @param Request $request
     * @return bool
     */
    protected function validateSignature(Request $request): bool
    {
        $secret = env('GITHUB_WEBHOOK_SECRET');

        // If no secret configured, skip validation (not recommended for production)
        if (empty($secret)) {
            Log::warning("GITHUB_WEBHOOK_SECRET not configured. Skipping signature validation.");
            return true;
        }

        $signature = $request->header('X-Hub-Signature-256');

        if (empty($signature)) {
            // Try older SHA-1 signature
            $signature = $request->header('X-Hub-Signature');

            if (empty($signature)) {
                return false;
            }

            // Validate SHA-1 signature
            $payload = $request->getContent();
            $expectedSignature = 'sha1=' . hash_hmac('sha1', $payload, $secret);

            return hash_equals($expectedSignature, $signature);
        }

        // Validate SHA-256 signature
        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
