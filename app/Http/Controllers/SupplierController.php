<?php

namespace App\Http\Controllers;

use App\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $suppliers = Supplier::orderBy('name', 'asc')->paginate(100);

        return view('supplier.index', compact('suppliers'));
    }


    public function create()
    {
        return view('supplier.create-edit');
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
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

        return redirect()->route('suppliers.index')->with('status', 'Proveedor creado correctamente.');
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Supplier  $supplier
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Supplier $supplier)
    {
        return $this->edit($supplier);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Supplier  $supplier
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Supplier $supplier)
    {
        return view('supplier.create-edit', compact('supplier'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Supplier  $supplier
     * @return \Illuminate\Http\RedirectResponse
     */
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

        return redirect()->route('suppliers.index')->with('status', 'Proveedor modificado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Supplier  $supplier
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Supplier $supplier)
    {
        try {
            $supplier->delete();
        } catch (QueryException $e) {
            return redirect()->route('suppliers.index')->with('status', $e->getMessage());
        }

        return redirect()->route('suppliers.index')->with('status', 'Proveedor eliminado.');
    }
}
