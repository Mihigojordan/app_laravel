<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FinanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $keys = [
            'Finance' => 'Finance',
            'Accounts' => 'Accounts',
            'AccountsReceivable' => 'Accounts Receivable',
            'AccountsPayable' => 'Accounts Payable',
            'Account_Type' => 'Account Type',
            'is_default' => 'Is Default',
            'Asset' => 'Asset',
            'Liability' => 'Liability',
            'Revenue' => 'Revenue',
            'Expense' => 'Expense',
            'Link_Account' => 'Link Account',
            'Choose_Account' => 'Choose Account'
        ];

        foreach ($keys as $key => $value) {
            \Illuminate\Support\Facades\DB::table('translations')->updateOrInsert(
                ['locale' => 'en', 'key' => $key],
                ['value' => $value, 'is_default' => 1]
            );
        }
    }
}
