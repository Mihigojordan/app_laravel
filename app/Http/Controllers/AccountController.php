<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends BaseController
{

    //-------------- Get All Account ---------------\\

    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Account::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;

        // Check If User Has Permission View  All Records
        $Accounts= Account::where('deleted_at', '=', null)
            
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('account_num', 'LIKE', "%{$request->search}%")
                    ->orWhere('account_name', 'LIKE', "%{$request->search}%");
                });
            });

        $totalRows = $Accounts->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $account_data = $Accounts->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        $data = [];
        foreach ($account_data as $account) {

            $item['id'] = $account->id;
            $item['account_num'] = $account->account_num;
            $item['account_name'] = $account->account_name;
            $item['balance'] = $account->balance;
            $item['type'] = $account->type;
            $item['is_default'] = $account->is_default;
            $item['note'] = $account->note;
           
            $data[] = $item;
        }

        return response()->json([
            'accounts' => $data,
            'totalRows' => $totalRows,
        ]);

    }

    //-------------- Store New Account ---------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Account::class);

        request()->validate([
            'account_num' => 'required',
            'account_name' => 'required',
            'initial_balance' => 'required',
            'type' => 'required',
        ]);
 
        if ($request['is_default']) {
            Account::where('is_default', true)->update(['is_default' => false]);
        }
 
        Account::create([
            'account_num' => $request['account_num'],
            'account_name' => $request['account_name'],
            'initial_balance' => $request['initial_balance'],
            'balance' => $request['initial_balance'],
            'type' => $request['type'],
            'is_default' => $request['is_default'],
            'note' => $request['note'],
        ]);

        return response()->json(['success' => true], 200);
    }

    //------------ function show -----------\\

    public function show($id){
    //
    
    }

    //-------------- Update Account ---------------\\

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Account::class);
        $Account = Account::findOrFail($id);

        request()->validate([
            'account_num' => 'required',
            'account_name' => 'required',
        ]);

        if ($request['is_default']) {
            Account::where('id', '!=', $id)->where('is_default', true)->update(['is_default' => false]);
        }
 
        $Account->update([
            'account_num' => $request['account_num'],
            'account_name' => $request['account_name'],
            'type' => $request['type'],
            'is_default' => $request['is_default'],
            'note' => $request['note'],
        ]);

        return response()->json(['success' => true], 200);

    }

    //-------------- Delete Account ---------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Account::class);
        $Account = Account::findOrFail($id);

        $Account->update([
            'deleted_at' => Carbon::now(),
        ]);

        return response()->json(['success' => true], 200);
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Account::class);
        $selectedIds = $request->selectedIds;

        foreach ($selectedIds as $account_id) {
            $Account = Account::findOrFail($account_id);

            $Account->update([
                'deleted_at' => Carbon::now(),
            ]);
        }
        return response()->json(['success' => true], 200);
    }

    //-------------- Chart of Accounts ---------------\\

    public function chart_of_accounts(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Account::class);

        $accounts = Account::where('deleted_at', '=', null)
            ->get()
            ->groupBy('type');

        return response()->json([
            'accounts' => $accounts,
        ]);
    }

    //-------------- Accounts Receivable ---------------\\

    public function receivable_accounts(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Account::class);

        $sales = \App\Models\Sale::where('deleted_at', '=', null)
            ->select('client_id', \DB::raw('SUM(GrandTotal) as total_sales'), \DB::raw('SUM(paid_amount) as total_paid'))
            ->groupBy('client_id')
            ->havingRaw('SUM(GrandTotal) > SUM(paid_amount)')
            ->with('client:id,name,phone')
            ->get();
            
        $receivables = [];
        foreach ($sales as $sale) {
            if ($sale->client) {
                $receivables[] = [
                    'id' => $sale->client->id,
                    'name' => $sale->client->name,
                    'phone' => $sale->client->phone,
                    'total_sales' => $sale->total_sales,
                    'paid_amount' => $sale->total_paid,
                    'balance' => $sale->total_sales - $sale->total_paid,
                ];
            }
        }

        return response()->json([
            'receivables' => $receivables,
        ]);
    }

    //-------------- Accounts Payable ---------------\\

    public function payable_accounts(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Account::class);

        $purchases = \App\Models\Purchase::where('deleted_at', '=', null)
            ->select('provider_id', \DB::raw('SUM(GrandTotal) as total_purchases'), \DB::raw('SUM(paid_amount) as total_paid'))
            ->groupBy('provider_id')
            ->havingRaw('SUM(GrandTotal) > SUM(paid_amount)')
            ->with('provider:id,name,phone')
            ->get();

        $payables = [];
        foreach ($purchases as $purchase) {
            if ($purchase->provider) {
                $payables[] = [
                    'id' => $purchase->provider->id,
                    'name' => $purchase->provider->name,
                    'phone' => $purchase->provider->phone,
                    'total_purchases' => $purchase->total_purchases,
                    'paid_amount' => $purchase->total_paid,
                    'balance' => $purchase->total_purchases - $purchase->total_paid,
                ];
            }
        }

        return response()->json([
            'payables' => $payables,
        ]);
    }

    //-------------- Seed Finance Translations (Temporary) ---------------\\

    public function seed_finance_translations(Request $request)
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
            'Choose_Account' => 'Choose Account',
            'ChartOfAccounts' => 'Chart of Accounts',
            'Total_Sales' => 'Total Sales',
            'Total_Purchases' => 'Total Purchases',
            'Balance' => 'Balance',
        ];

        foreach ($keys as $key => $value) {
            \Illuminate\Support\Facades\DB::table('translations')->updateOrInsert(
                ['locale' => 'en', 'key' => $key],
                ['value' => $value, 'is_default' => 1]
            );
        }

        return response()->json(['success' => true, 'message' => 'Translations seeded.']);
    }

}
