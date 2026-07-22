<?php
namespace App\Http\Controllers;

use App\Mail\Password_Reset_Request;
use App\Mail\Password_Reset_Success;
use App\Models\PasswordReset;
use App\Models\User;
use App\Traits\SendsOtp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends BaseController
{
    use SendsOtp;

    /**
     * Create token password reset
     *
     * @param  [string] email
     * @return [string] message
     */
    public function create(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user || !$user->statut) {
            return response()->json(['status' => false,
                'message' => 'We can\'t find a user with that e-mail address.',
            ]);
        }


        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Str::random(60),
            ]
        );
        if ($user && $passwordReset) {
            $this->Set_config_mail();
        }
        // Set_config_mail => BaseController
        $url = url('/password/find/' . $passwordReset->token);
        Mail::to($user->email)->send(new Password_Reset_Request($passwordReset->token, $url));

        return response()->json(['status' => true,
            'message' => 'We have e-mailed your password reset link!',
        ], 200);
    }
    /**
     * Find token password reset
     *
     * @param  [string] $token
     * @return [string] message
     * @return [json] passwordReset object
     */
    public function find($token)
    {
        $passwordReset = PasswordReset::where('token', $token)
            ->first();
        if (!$passwordReset) {
            return response()->json([
                'message' => 'This password reset token is invalid.',
                'success' => false,
            ]);
        }

        if (Carbon::parse($passwordReset->updated_at)->addMinutes(60)->isPast()) {
            $passwordReset->delete();
            return response()->json([
                'message' => 'This password reset token is invalid.',
                'success' => false,
            ]);
        }

        return view('auth.passwords.reset', ["token" => $token]);
    }
    /**
     * Reset password

     */
    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|confirmed',
            'token' => 'required|string',
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'We can\'t find a user with that e-mail address.',
                'status' => false,
                'code' => 2,
            ]);
        }

        $passwordReset = PasswordReset::where([
            ['token', $request->token],
            ['email', $request->email],
        ])->first();
        if (!$passwordReset) {
            return response()->json([
                'message' => 'This password reset token is invalid.',
                'status' => false,
                'code' => 3,
            ]);
        }

        $user->password = bcrypt($request->password);
        $user->save();
        $passwordReset->delete();
        $this->Set_config_mail(); // Set_config_mail => BaseController
        Mail::to($request->email)->send(new Password_Reset_Success());

        return response()->json([
            'user' => $user,
            'message' => 'Your Password has been changed.',
            'status' => true,
            'code' => 1,
        ]);
    }

    /**
     * Send a password-reset OTP via email or SMS (ClickSend).
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        $channel = $this->resolveChannel($request->identifier);
        $user = $channel === 'sms'
            ? User::where('phone', $request->identifier)->first()
            : User::where('email', $request->identifier)->first();

        if (!$user || !$user->statut) {
            return response()->json([
                'status' => false,
                'message' => $channel === 'sms'
                    ? 'We can\'t find a user with that phone number.'
                    : 'We can\'t find a user with that e-mail address.',
            ]);
        }

        $otp = $this->generateOtp();
        $user->reset_otp_code = $otp;
        $user->reset_otp_expires_at = now()->addMinutes(5);
        $user->save();

        $sent = $channel === 'sms'
            ? $this->sendOtpSms($user->phone, $otp)
            : $this->sendOtpEmail($user->email, $otp, 'Your Password Reset Code');

        if (!$sent) {
            Log::error("PasswordResetController: Failed to send reset OTP via {$channel} for user #{$user->id}");
            return response()->json([
                'status' => false,
                'message' => $channel === 'sms'
                    ? 'Could not send OTP SMS. Please check your SMS settings.'
                    : 'Could not send OTP email. Please check your mail settings.',
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'OTP sent to your ' . ($channel === 'sms' ? 'phone' : 'email'),
        ], 200);
    }

    /**
     * Verify a password-reset OTP and set the new password.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'otp' => 'required|string',
            'password' => 'required|string|confirmed',
        ]);

        $channel = $this->resolveChannel($request->identifier);
        $user = $channel === 'sms'
            ? User::where('phone', $request->identifier)->first()
            : User::where('email', $request->identifier)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'code' => 2,
                'message' => $channel === 'sms'
                    ? 'We can\'t find a user with that phone number.'
                    : 'We can\'t find a user with that e-mail address.',
            ]);
        }

        if (
            !$user->reset_otp_code ||
            (string) $user->reset_otp_code !== (string) $request->otp ||
            !$user->reset_otp_expires_at ||
            $user->reset_otp_expires_at->lt(now())
        ) {
            return response()->json([
                'status' => false,
                'code' => 3,
                'message' => 'This OTP code is invalid or has expired.',
            ]);
        }

        $user->password = bcrypt($request->password);
        $user->reset_otp_code = null;
        $user->reset_otp_expires_at = null;
        $user->save();

        if ($user->email) {
            try {
                $this->Set_config_mail();
                Mail::to($user->email)->send(new Password_Reset_Success());
            } catch (\Throwable $e) {
                Log::error('PasswordResetController: Failed to send reset-success email - ' . $e->getMessage());
            }
        }

        return response()->json([
            'user' => $user,
            'message' => 'Your Password has been changed.',
            'status' => true,
            'code' => 1,
        ]);
    }
}
