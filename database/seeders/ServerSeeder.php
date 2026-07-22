<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       // Insert some stuff
        DB::table('servers')->insertOrIgnore(
            array(
                'id' => 1,
                'mail_mailer' => 'smtp',
                'host' => 'smtp.sendgrid.net',
                'port' => '587',
                'sender_name' => 'Admin',
                'username' => 'apikey',
                'password' => 'YOUR_SENDGRID_API_KEY',
                'encryption' => 'tls',
            )
            
        );
    }
}
