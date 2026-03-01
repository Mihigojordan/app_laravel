<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Unit;
use App\Models\ProductVariant;
use App\Models\product_warehouse;
use App\Models\Requisition;
use App\Models\RequisitionDetail;
use App\Models\Role;
use App\Models\Warehouse;
use App\Models\User;
use App\utils\helpers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DB;

class RequisitionController extends BaseController
{

    //------------- Show ALL Requisitions ----------\\

    public function index(request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Requisition::class);
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        
        $perPage = $request->limit;
        $order = $request->SortField;
        $dir = $request->SortType;
        $helpers = new helpers();
        
        $param = array(
            0 => 'like',
            1 => 'like',
            2 => '=',
            3 => '=',
        );
        $columns = array(
            0 => 'Ref',
            1 => 'status',
            2 => 'warehouse_id',
            3 => 'date',
        );
        $data = array();

        $Requisitions = Requisition::with('warehouse', 'user')
            ->where('deleted_at', '=', null)
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            });

        $Filtred = $helpers->filter($Requisitions, $columns, $param, $request)
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('status', 'LIKE', "%{$request->search}%");
                });
            });

        $totalRows = $Filtred->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $items = $Filtred->offset(($request->page - 1) * $perPage)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        foreach ($items as $item) {
            $data[] = [
                'id' => $item->id,
                'date' => $item->date,
                'Ref' => $item->Ref,
                'warehouse_name' => $item->warehouse->name,
                'user_name' => $item->user->username,
                'status' => $item->status,
            ];
        }

        $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);

        return response()->json([
            'totalRows' => $totalRows,
            'requisitions' => $data,
            'warehouses' => $warehouses,
        ]);
    }

    //------- Create Requisition ----------\\

    public function create(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Requisition::class);

        $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);

        return response()->json([
            'warehouses' => $warehouses,
        ]);
    }

    //------ Store new Requisition -------------\\

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Requisition::class);

        request()->validate([
            'warehouse_id' => 'required',
            'date' => 'required',
        ]);

        \DB::transaction(function () use ($request) {
            $requisition = new Requisition;

            $requisition->date = $request->date;
            $requisition->Ref = $this->getNumberOrder();
            $requisition->warehouse_id = $request->warehouse_id;
            $requisition->status = 'pending';
            $requisition->notes = $request->notes;
            $requisition->user_id = Auth::user()->id;
            $requisition->save();

            $data = $request['details'];
            foreach ($data as $key => $value) {
                $orderDetails[] = [
                    'requisition_id' => $requisition->id,
                    'quantity' => $value['quantity'],
                    'product_id' => $value['product_id'],
                    'product_variant_id' => $value['product_variant_id'],
                    'unit_id' =>  $value['unit_id'],
                ];
            }
            RequisitionDetail::insert($orderDetails);
        }, 10);

        return response()->json(['success' => true]);
    }

    //--------- Update Requisition  -------------\\

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Requisition::class);

        request()->validate([
            'warehouse_id' => 'required',
        ]);

        \DB::transaction(function () use ($request, $id) {
            $requisition = Requisition::findOrFail($id);

            $requisition->update([
                'date' => $request->date,
                'warehouse_id' => $request->warehouse_id,
                'status' => $request->status,
                'notes' => $request->notes,
            ]);

            RequisitionDetail::where('requisition_id', $id)->delete();
            $data = $request['details'];
            foreach ($data as $key => $value) {
                $orderDetails[] = [
                    'requisition_id' => $id,
                    'quantity' => $value['quantity'],
                    'product_id' => $value['product_id'],
                    'product_variant_id' => $value['product_variant_id'],
                    'unit_id' =>  $value['unit_id'],
                ];
            }
            RequisitionDetail::insert($orderDetails);
        }, 10);

        return response()->json(['success' => true]);
    }

    //--------- Delete Requisition ----------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Requisition::class);

        \DB::transaction(function () use ($id) {
            $requisition = Requisition::findOrFail($id);
            $requisition->delete();
            RequisitionDetail::where('requisition_id', $id)->delete();
        }, 10);

        return response()->json(['success' => true]);
    }

    //------------ Reference Number Requisition ---------------\\

    public function getNumberOrder()
    {
        $last = Requisition::latest('id')->first();
        if (!$last) {
            $number = 0;
        } else {
            $number = $last->id;
        }
        return 'REQ_' . (str_pad($number + 1, 4, '0', STR_PAD_LEFT));
    }

    //------- Show Requisition Details ----------\\

    public function show(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'view', Requisition::class);

        $requisition = Requisition::with('warehouse', 'details.product', 'details.unit')
            ->where('deleted_at', '=', null)
            ->findOrFail($id);

        $details = [];
        foreach ($requisition->details as $detail) {
            $details[] = [
                'id' => $detail->id,
                'quantity' => $detail->quantity,
                'product_id' => $detail->product_id,
                'product_name' => $detail->product->name,
                'unit_name' => $detail->unit->ShortName,
            ];
        }

        return response()->json([
            'requisition' => $requisition,
            'details' => $details,
        ]);
    }

    //------- Edit Requisition ----------\\

    public function edit(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Requisition::class);

        $requisition = Requisition::with('details.product', 'details.unit')
            ->where('deleted_at', '=', null)
            ->findOrFail($id);

        $details = [];
        foreach ($requisition->details as $detail) {
            $details[] = [
                'detail_id' => $detail->id,
                'product_id' => $detail->product_id,
                'name' => $detail->product->name,
                'code' => $detail->product->code,
                'stock' => 0, // Will be updated by warehouse selection in frontend
                'quantity' => $detail->quantity,
                'unit' => $detail->unit->ShortName,
                'unit_id' => $detail->unit_id,
                'product_variant_id' => $detail->product_variant_id,
            ];
        }

        $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);

        return response()->json([
            'requisition' => $requisition,
            'details' => $details,
            'warehouses' => $warehouses,
        ]);
    }
}
