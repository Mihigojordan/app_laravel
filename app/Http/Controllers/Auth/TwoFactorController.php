<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\SendsOtp;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    use SendsOtp;

    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'otp' => 'required',
        ]);

        $field = $this->resolveChannel($request->email) === 'email' ? 'email' : 'phone';
        $user = User::where($field, $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        \Illuminate\Support\Facades\Log::info("TwoFactorController@verify: Checking OTP for {$request->email}");
        \Illuminate\Support\Facades\Log::info("TwoFactorController@verify: DB Code: '" . $user->two_factor_code . "', Request OTP: '" . $request->otp . "'");
        \Illuminate\Support\Facades\Log::info("TwoFactorController@verify: Expires: " . $user->two_factor_expires_at . ", Now: " . now());

        if ((string)$user->two_factor_code === (string)$request->otp && $user->two_factor_expires_at->gt(now())) {
            // Clear OTP
            $user->two_factor_code = null;
            $user->two_factor_expires_at = null;
            $user->save();

            // Log in
            Auth::login($user);

            return response()->json([
                'status' => true,
                'message' => 'OTP verified successfully',
            ]);
        }

        \Illuminate\Support\Facades\Log::error("TwoFactorController@verify: Verification failed for {$request->email}");
        return response()->json([
            'status' => false,
            'message' => 'Invalid or expired OTP',
        ], 422);
    }
    
    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
        ]);

        $channel = $this->resolveChannel($request->email);
        $field = $channel === 'email' ? 'email' : 'phone';
        $user = User::where($field, $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Generate OTP
        $otp = $this->generateOtp();
        \Illuminate\Support\Facades\Log::info("Login OTP (Resend, {$channel}) for {$user->email}: {$otp}");
        error_log("------------------------------------------");
        error_log("RESEND OTP CODE FOR {$user->email}: {$otp}");
        error_log("------------------------------------------");

        // Save to user
        $user->two_factor_code = $otp;
        $user->two_factor_expires_at = now()->addMinutes(5);
        $user->save();

        $sent = $channel === 'sms'
            ? $this->sendOtpSms($user->phone, $otp)
            : $this->sendOtpEmail($user->email, $otp, 'Your Login OTP Code');

        if (!$sent) {
            return response()->json([
                'status' => false,
                'message' => $channel === 'sms'
                    ? 'Failed to send OTP SMS.'
                    : 'Failed to send OTP email.',
            ], 500);
        }

        return response()->json([
            'status' => true,
            'channel' => $channel,
            'otp' => (config('app.debug') || env('APP_ENV') == 'development') ? $otp : null,
            'message' => 'OTP resent to your ' . ($channel === 'sms' ? 'phone' : 'email'),
        ]);
    }
}
