<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index()
    {
        $suppliers = Auth::user()->getSuppliers();

        return view('saas.supplier.index', compact('suppliers'));
    }


    public function create()
    {
        return view('saas.supplier.create-edit');
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'code'          => 'required|max:64',
            'name'          => 'required|max:64',
            'locale'        => 'nullable|max:64',
            'type_import'   => 'required|max:64',
            'ws'            => 'nullable|max:64',

            'config'        => 'nullable',  // json
        ]);

        $supplier = Supplier::create($validatedData);
        if (isset($supplier->ws) && !isset($supplier->config)) {
            $supplier->config = json_encode(('App\\Libraries\\'.$supplier->ws)::DEFAULT_CONFIG);
            $supplier->save();
        }

        return redirect()->route('saas.suppliers')->with('status', 'Proveedor creado correctamente.');
    }


    public function show(Supplier $supplier)
    {
        return $this->edit($supplier);
    }


    public function edit(Supplier $supplier)
    {
        return view('saas.supplier.create-edit', compact('supplier'));
    }


    public function update(Request $request, Supplier $supplier)
    {
        $validatedData = $request->validate([
            'code'          => 'required|max:64',
            'name'          => 'required|max:64',
            'locale'        => 'nullable|max:64',
            'type_import'   => 'required|max:64',
            'ws'            => 'nullable|max:64',

            'config'        => 'nullable',  // json
        ]);

        $supplier = Supplier::whereId($supplier->id)->update($validatedData);

        return redirect()->route('saas.suppliers')->with('status', 'Proveedor modificado correctamente.');
    }


    public function destroy(Supplier $supplier)
    {
        try {
            $supplier->delete();
        } catch (QueryException $e) {
            return redirect()->route('saas.suppliers')->with('status', $e->getMessage());
        }

        return redirect()->route('saas.suppliers')->with('status', 'Proveedor eliminado.');
    }

}
