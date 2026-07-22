<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ClickSendService
{
    /**
     * Send an SMS via the ClickSend v3 REST API.
     *
     * @param  string  $to
     * @param  string  $message
     * @return bool
     */
    public function send(string $to, string $message): bool
    {
        $username = env('CLICKSEND_USERNAME');
        $apiKey = env('CLICKSEND_API_KEY');

        if (!$username || !$apiKey) {
            Log::error('ClickSendService: Missing CLICKSEND_USERNAME/CLICKSEND_API_KEY in .env');
            return false;
        }

        $to = $this->normalizeNumber($to);

        $smsMessage = array_filter([
            'source' => 'php',
            'to' => $to,
            'body' => $message,
            'from' => env('CLICKSEND_FROM') ?: null,
        ]);

        error_log("------------------------------------------");
        error_log("CLICKSEND: Sending SMS to {$to}: " . json_encode($smsMessage));

        try {
            $client = new Client();
            $response = $client->post('https://rest.clicksend.com/v3/sms/send', [
                'auth' => [$username, $apiKey],
                'json' => ['messages' => [$smsMessage]],
            ]);

            $status = $response->getStatusCode();
            $rawBody = (string) $response->getBody();

            error_log("CLICKSEND: HTTP {$status} response - {$rawBody}");
            error_log("------------------------------------------");
            Log::info("ClickSendService: HTTP {$status} response for {$to} - {$rawBody}");

            if ($status < 200 || $status >= 300) {
                Log::error("ClickSendService: Unexpected status {$status} - {$rawBody}");
                return false;
            }

            $body = json_decode($rawBody, true);
            $messageStatus = $body['data']['messages'][0]['status'] ?? null;

            // ClickSend can return HTTP 200 with a per-message failure (e.g. insufficient
            // balance, blocked/invalid number) buried in the response body, so the HTTP
            // status alone isn't enough to confirm the SMS actually went out.
            if ($messageStatus !== null && strtoupper($messageStatus) !== 'SUCCESS') {
                error_log("CLICKSEND: Message not accepted, status={$messageStatus}");
                Log::error("ClickSendService: Message to {$to} not accepted, status={$messageStatus} - {$rawBody}");
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            error_log("CLICKSEND: Exception - " . $e->getMessage());
            Log::error('ClickSendService: Failed to send SMS - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ensure the number is in full international (E.164-ish) format, since
     * this ClickSend account requires "+<country code><number>" and does not
     * auto-convert local-format numbers (e.g. "0791813289") like some
     * accounts do.
     */
    private function normalizeNumber(string $to): string
    {
        $to = trim($to);

        if (str_starts_with($to, '+')) {
            return $to;
        }

        $countryCode = env('CLICKSEND_DEFAULT_COUNTRY_CODE');
        if (!$countryCode) {
            return $to;
        }

        return '+' . $countryCode . ltrim($to, '0');
    }
}
