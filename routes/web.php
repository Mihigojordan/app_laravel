<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\BaseController;
use Modules\Store\Http\Controllers\StoreController;
use Laravel\passport\Passport;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

//------------------------------------------------------------------\\
// Passport::routes();

Route::post('/login', [
    'uses' => 'Auth\LoginController@login',
    'middleware' => 'Is_Active',
]);

Route::post('/verify-otp', 'Auth\TwoFactorController@verify');
Route::post('/resend-otp', 'Auth\TwoFactorController@resend');

Route::get('/seed-req', function() {
    $permissions = ['requisitions_view', 'requisitions_add', 'requisitions_edit', 'requisitions_delete', 'Purchases_view', 'Purchases_add', 'Purchases_edit', 'Purchases_delete'];
    foreach ($permissions as $p) {
        \App\Models\Permission::firstOrCreate(['name' => $p]);
    }
    $admin = \App\Models\Role::where('name', 'Admin')->first() ?? \App\Models\Role::where('name', 'admin')->first();
    if ($admin) {
        $pIds = \App\Models\Permission::whereIn('name', $permissions)->pluck('id');
        $admin->permissions()->syncWithoutDetaching($pIds);
        return "Seeded successfully";
    }
    return "Admin role not found";
});



//------------------------------------------------------------------\\

$installed = Storage::disk('public')->exists('installed');

if ($installed === false) {
    Route::get('/setup', [
        'uses' => 'SetupController@viewCheck',
    ])->name('setup');

    // Bypass for databases that are already migrated/seeded: marks the app
    // as installed WITHOUT running migrate:fresh (unlike the wizard's
    // lastStep). Requires SETUP_BYPASS_TOKEN to be set as an env var.
    // Temporary diagnostic: view the current raw .env with secrets masked,
    // to check for corruption from the setup wizard's changeEnv() writes.
    // Remove this route once no longer needed.
    Route::get('/setup/debug-env/{token}', function ($token) {
        $expected = env('SETUP_BYPASS_TOKEN');
        if (! $expected || ! hash_equals($expected, $token)) {
            abort(404);
        }
        $env = file_get_contents(base_path() . '/.env');
        $env = preg_replace(
            '/^((DB_PASSWORD|MAIL_PASSWORD|STRIPE_SECRET|TWILIO_TOKEN|TWILIO_SID|CLICKSEND_API_KEY|AWS_SECRET_ACCESS_KEY|AWS_ACCESS_KEY_ID|PUSHER_APP_SECRET|TERMI_SECRET|NEXMO_SECRET|api_key)=).*/mi',
            '$1[REDACTED]',
            $env
        );
        return response('<pre>' . htmlspecialchars($env) . '</pre>');
    });

    Route::get('/setup/mark-installed/{token}', function ($token) {
        $expected = env('SETUP_BYPASS_TOKEN');
        if (! $expected || ! hash_equals($expected, $token)) {
            abort(404);
        }
        Storage::disk('public')->put('installed', 'OK');
        return 'Marked as installed without running migrations. You can now remove SETUP_BYPASS_TOKEN.';
    });

    Route::get('/setup/step-1', [
        'uses' => 'SetupController@viewStep1',
    ]);

    Route::post('/setup/step-2', [
        'as' => 'setupStep1', 'uses' => 'SetupController@setupStep1',
    ]);

    Route::post('/setup/testDB', [
        'as' => 'testDB', 'uses' => 'TestDbController@testDB',
    ]);

    Route::get('/setup/step-2', [
        'uses' => 'SetupController@viewStep2',
    ]);

    Route::get('/setup/step-3', [
        'uses' => 'SetupController@viewStep3',
    ]);

    Route::get('/setup/finish', function () {

        return view('setup.finishedSetup');
    });

    Route::get('/setup/getNewAppKey', [
        'as' => 'getNewAppKey', 'uses' => 'SetupController@getNewAppKey',
    ]);

    Route::get('/setup/getPassport', [
        'as' => 'getPassport', 'uses' => 'SetupController@getPassport',
    ]);

    Route::get('/setup/getMegrate', [
        'as' => 'getMegrate', 'uses' => 'SetupController@getMegrate',
    ]);

    Route::post('/setup/step-3', [
        'as' => 'setupStep2', 'uses' => 'SetupController@setupStep2',
    ]);

    Route::post('/setup/step-4', [
        'as' => 'setupStep3', 'uses' => 'SetupController@setupStep3',
    ]);

    Route::post('/setup/step-5', [
        'as' => 'setupStep4', 'uses' => 'SetupController@setupStep4',
    ]);

    Route::post('/setup/lastStep', [
        'as' => 'lastStep', 'uses' => 'SetupController@lastStep',
    ]);

    Route::get('setup/lastStep', function () {
        return redirect('/setup', 301);
    });

} else {
    Route::any('/setup/{vue}', function () {
        abort(403);
    });
}

//------------------------------------------------------------------\\

Route::group(['middleware' => ['web', 'auth:web', 'Is_Active']], function () {

    Route::get('/login', function () {
        $installed = Storage::disk('public')->exists('installed');
        if ($installed === false) {
            return redirect('/setup');
        } else {
            return redirect('/login');
        }
    });


    Route::get('/{vue?}',
      function () {
        $installed = Storage::disk('public')->exists('installed');
        $ModulesData = BaseController::get_Module_Info();

        if ($installed === false) {
            return redirect('/setup');
        } else {
            return view('layouts.master' , [
                'ModulesInstalled' => $ModulesData['ModulesInstalled'],
                'ModulesEnabled' => $ModulesData['ModulesEnabled'],
            ]);
        }
    })->where('vue', '^(?!api|setup|update|update_database_module|password|module|store|online_store).*$');
 
});
   
    Auth::routes([
        'register' => false,
    ]);


//------------------------- -UPDATE ----------------------------------------\\

Route::group(['middleware' => ['web', 'auth:web', 'Is_Active']], function () {

    Route::get('update_database_module/{module_name}', 'ModuleSettingsController@update_database_module')->name('update_database_module');


    Route::get('/update', 'UpdateController@viewStep1');

    Route::get('/update/finish', function () {

        return view('update.finishedUpdate');
    });

    Route::post('/update/lastStep', [
        'as' => 'update_lastStep', 'uses' => 'UpdateController@lastStep',
    ]);

});




