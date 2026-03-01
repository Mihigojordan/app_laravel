<?php

use App\Models\Translate;

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
    'Expense' => 'Expense'
];

foreach ($keys as $key => $value) {
    Translate::updateOrCreate(
        ['locale' => 'en', 'key' => $key],
        ['value' => $value]
    );
}

echo "Translations seeded successfully.\n";
