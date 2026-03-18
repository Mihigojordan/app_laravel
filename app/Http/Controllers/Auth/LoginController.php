<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use \Nwidart\Modules\Facades\Module;
use App\Models\Setting;
use App\Mail\OtpEmail;
use Illuminate\Support\Facades\Mail;
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

    use AuthenticatesUsers;

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
            // Generate OTP
            $otp = rand(100000, 999999);
            Log::info("Login OTP for {$user->email}: {$otp}");
            error_log("------------------------------------------");
            error_log("OTP CODE FOR {$user->email}: {$otp}");
            error_log("------------------------------------------");
            
            // Save to user
            $user->two_factor_code = $otp;
            $user->two_factor_expires_at = now()->addMinutes(5);
            $user->save();

            // Send Email
            $data = [
                'otp' => $otp,
                'subject' => 'Your Login OTP Code',
            ];

            try {
                Log::info("[" . now()->toDateTimeString() . "] LoginController: Attempting to send OTP email");
                if (env('MAILTRAP_API_TOKEN')) {
                    Log::info("LoginController: Sending OTP via Mailtrap API" . (env('MAILTRAP_IS_SANDBOX') ? " (Sandbox)" : ""));
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
                            'to' => [['email' => $user->email]],
                            'subject' => 'Your Login OTP Code',
                            'text' => 'Your OTP code is: ' . $otp,
                            'category' => 'OTP Verification',
                        ],
                    ]);

                    if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                        error_log("MAILTRAP: Email sent successfully to {$user->email}");
                    } else {
                        error_log("MAILTRAP ERROR: Status " . $response->getStatusCode() . " - " . $response->getBody());
                    }
                } else {
                    // Set mail config if needed
                    $smtp = new \App\Http\Controllers\BaseController();
                    $smtp->Set_config_mail();
                    
                    Mail::to($user->email)->send(new OtpEmail($data));
                }
                Log::info("[" . now()->toDateTimeString() . "] LoginController: OTP email sent successfully");
            } catch (\Throwable $e) {
                Log::error("[" . now()->toDateTimeString() . "] LoginController: Failed to send OTP email: " . $e->getMessage());
                // In local/dev environments, we should still allow the user to see the OTP step
                // since the OTP is already logged in the terminal above.
                if (config('app.debug') || env('APP_ENV') == 'development') {
                   Log::info("Development mode: Proceeding to OTP step despite mail failure");
                   Auth::logout();
                   $request->session()->regenerateToken();
                   return response()->json([
                       'otp_required' => true,
                       'email' => $user->email,
                       'otp' => $otp,
                       'csrf_token' => csrf_token(),
                       'message' => 'OTP generated (Mail failed, check console logs)',
                   ]);
                }
                
                Auth::logout();
                return response()->json([
                    'status' => false,
                    'message' => 'Could not send OTP email. Please check your mail settings.',
                ], 500);
            }

            // Log out for now, frontend will handle the next step
            Auth::logout();
            $request->session()->regenerateToken();
            Log::info("LoginController: Returning otp_required response with new CSRF token");

            return response()->json([
                'otp_required' => true,
                'email' => $user->email,
                'otp' => (config('app.debug') || env('APP_ENV') == 'development') ? $otp : null,
                'csrf_token' => csrf_token(),
                'message' => 'OTP sent to your email',
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
        return ['email' => $request->{$this->username()}, 'password' => $request->password, 'statut' => 1];
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
