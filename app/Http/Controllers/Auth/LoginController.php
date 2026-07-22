<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use \Nwidart\Modules\Facades\Module;
use App\Models\Setting;
use App\Traits\SendsOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers, SendsOtp;

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        set_time_limit(60);
        Log::info("[" . now()->toDateTimeString() . "] LoginController: authenticated method called for user: " . $user->email);
        $settings = Setting::first();
        Log::info("LoginController: Settings found, 2FA status: " . ($settings ? ($settings->two_factor_auth ? 'Enabled' : 'Disabled') : 'Settings Null'));

        if ($settings && $settings->two_factor_auth) {
            Log::info("LoginController: Initializing 2FA flow");

            $channel = $this->resolveChannel($request->input($this->username()));

            // Generate OTP
            $otp = $this->generateOtp();
            Log::info("Login OTP ({$channel}) for {$user->email}: {$otp}");
            error_log("------------------------------------------");
            error_log("OTP CODE FOR {$user->email}: {$otp}");
            error_log("------------------------------------------");

            // Save to user
            $user->two_factor_code = $otp;
            $user->two_factor_expires_at = now()->addMinutes(5);
            $user->save();

            $sent = $channel === 'sms'
                ? $this->sendOtpSms($user->phone, $otp)
                : $this->sendOtpEmail($user->email, $otp, 'Your Login OTP Code');

            if (!$sent) {
                Log::error("LoginController: Failed to send OTP via {$channel} for {$user->email}");
                // In local/dev environments, we should still allow the user to see the OTP step
                // since the OTP is already logged in the terminal above.
                if (config('app.debug') || env('APP_ENV') == 'development') {
                    Log::info("Development mode: Proceeding to OTP step despite send failure");
                    Auth::logout();
                    $request->session()->regenerateToken();
                    return response()->json([
                        'otp_required' => true,
                        'email' => $user->email,
                        'channel' => $channel,
                        'otp' => $otp,
                        'csrf_token' => csrf_token(),
                        'message' => 'OTP generated (' . ($channel === 'sms' ? 'SMS' : 'Mail') . ' failed, check console logs)',
                    ]);
                }

                Auth::logout();
                return response()->json([
                    'status' => false,
                    'message' => $channel === 'sms'
                        ? 'Could not send OTP SMS. Please check your SMS settings.'
                        : 'Could not send OTP email. Please check your mail settings.',
                ], 500);
            }

            // Log out for now, frontend will handle the next step
            Auth::logout();
            $request->session()->regenerateToken();
            Log::info("LoginController: Returning otp_required response with new CSRF token");

            return response()->json([
                'otp_required' => true,
                'email' => $user->email,
                'channel' => $channel,
                'otp' => (config('app.debug') || env('APP_ENV') == 'development') ? $otp : null,
                'csrf_token' => csrf_token(),
                'message' => 'OTP sent to your ' . ($channel === 'sms' ? 'phone' : 'email'),
            ]);
        }
        Log::info("LoginController: 2FA not required, proceeding with normal login");

        // If 2FA is disabled, proceed as normal
        return response()->json([
            'status' => true,
            'user' => $user,
        ]);
    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */

     /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(\Illuminate\Http\Request $request)
    {
        $login = $request->{$this->username()};
        $field = $this->resolveChannel($login) === 'email' ? 'email' : 'phone';

        return [$field => $login, 'password' => $request->password, 'statut' => 1];
    }

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm(){
        $allModules = Module::all();
        $allEnabledModules = Module::allEnabled();

        $ModulesInstalled = [];
        $ModulesEnabled = [];

        foreach($allModules as $key => $modules_name){
            $ModulesInstalled[] = $key;
        }

        foreach($allEnabledModules as $key => $modules_name){
            $ModulesEnabled[] = $key;
        }

        return view('auth.login',[
            'ModulesInstalled' => $ModulesInstalled, 
            'ModulesEnabled' => $ModulesEnabled, 
        ]);
    }
}
