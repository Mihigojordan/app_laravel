<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Artisan::command('send-mail', function () {
    $otp = rand(100000, 999999);
    $email_to = 'mihigojordan8@gmail.com';
    
    $this->info("Attempting to send test OTP email to {$email_to} via Mailtrap...");

    if (env('MAILTRAP_API_TOKEN')) {
        $client = new \GuzzleHttp\Client();
        
        $url = env('MAILTRAP_IS_SANDBOX') 
            ? 'https://sandbox.api.mailtrap.io/api/send/' . env('MAILTRAP_INBOX_ID')
            : 'https://send.api.mailtrap.io/api/send';

        try {
            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('MAILTRAP_API_TOKEN'),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'from' => ['email' => 'hello@demomailtrap.co', 'name' => 'Mailtrap Test'],
                    'to' => [['email' => $email_to]],
                    'subject' => 'Integration Test OTP',
                    'text' => 'Your test OTP code is: ' . $otp,
                    'category' => 'Integration Test',
                ],
            ]);

            $this->info("MAILTRAP STATUS: " . $response->getStatusCode());
            $this->info("RESPONSE: " . $response->getBody());
            
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $this->info("SUCCESS: Check your Mailtrap " . (env('MAILTRAP_IS_SANDBOX') ? "Sandbox Inbox" : "Real Inbox"));
            }
        } catch (\Exception $e) {
            $this->error("ERROR: " . $e->getMessage());
        }
    } else {
        $this->info("MAILTRAP_API_TOKEN not found. Using Native Laravel Mailer...");
        try {
            // Re-set config just in case
            $smtp = new \App\Http\Controllers\BaseController();
            $smtp->Set_config_mail();
            
            $this->info("MAIL DEFAULT: " . config('mail.default'));
            $this->info("MAILER CONFIG: " . json_encode(config('mail.mailers.' . config('mail.default'))));
            
            \Illuminate\Support\Facades\Mail::purge();
            
            $data = [
                'otp' => $otp,
                'subject' => 'Integration Test OTP (Native)',
            ];
            
            \Illuminate\Support\Facades\Mail::to($email_to)->send(new \App\Mail\OtpEmail($data));
            $this->info("SUCCESS: Email sent via Native Mailer (" . config('mail.default') . ")");
        } catch (\Exception $e) {
            $this->error("NATIVE MAILER ERROR: " . $e->getMessage());
            file_put_contents('mail_trace.txt', "ERROR: " . $e->getMessage() . "\n\n" . $e->getTraceAsString());
        }
    }
})->purpose('Send Test Mail');
