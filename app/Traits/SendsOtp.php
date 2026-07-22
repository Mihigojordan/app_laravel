<?php

namespace App\Traits;

use App\Mail\OtpEmail;
use App\Services\ClickSendService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

trait SendsOtp
{
    /**
     * Generate a 6-digit OTP code.
     */
    protected function generateOtp(): string
    {
        return (string) rand(100000, 999999);
    }

    /**
     * Auto-detect whether a login/reset identifier is an email address or a phone number.
     *
     * @param  string|null  $identifier
     * @return string  'email'|'sms'
     */
    protected function resolveChannel($identifier)
    {
        return filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'sms';
    }

    /**
     * Send an OTP code by email, using the Mailtrap HTTP API when configured,
     * falling back to the app's configured SMTP mailer otherwise.
     */
    protected function sendOtpEmail(string $email, string $otp, string $subject): bool
    {
        $data = [
            'otp' => $otp,
            'subject' => $subject,
        ];

        try {
            if (env('MAILTRAP_API_TOKEN')) {
                $client = new \GuzzleHttp\Client();

                $url = env('MAILTRAP_IS_SANDBOX')
                    ? 'https://sandbox.api.mailtrap.io/api/send/' . env('MAILTRAP_INBOX_ID')
                    : 'https://send.api.mailtrap.io/api/send';

                $response = $client->post($url, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . env('MAILTRAP_API_TOKEN'),
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'from' => ['email' => 'hello@demomailtrap.co', 'name' => 'Mailtrap Test'],
                        'to' => [['email' => $email]],
                        'subject' => $subject,
                        'text' => 'Your OTP code is: ' . $otp,
                        'category' => 'OTP Verification',
                    ],
                ]);

                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    return true;
                }

                Log::error("SendsOtp: Mailtrap error, status " . $response->getStatusCode() . " - " . $response->getBody());
                return false;
            }

            $smtp = new \App\Http\Controllers\BaseController();
            $smtp->Set_config_mail();

            Mail::to($email)->send(new OtpEmail($data));
            return true;
        } catch (\Throwable $e) {
            Log::error('SendsOtp: Failed to send OTP email - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send an OTP code by SMS via ClickSend.
     */
    protected function sendOtpSms(string $phone, string $otp): bool
    {
        return (new ClickSendService())->send($phone, "Your verification code is: {$otp}");
    }
}
