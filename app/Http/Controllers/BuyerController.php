<?php

namespace App\Http\Controllers;

use App\Address;
use App\Buyer;
use App\Country;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class BuyerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $buyers = Buyer::orderBy('name', 'asc')->paginate(25);

        return view('buyer.index', compact('buyers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // 'shipping_address_id', 'billing_address_id', 'name', 'email', 'phone', 'company_name', 'tax_region', 'tax_name', 'tax_value'
        // 'country_id', 'fixed', 'name', 'address1', 'address2', 'address3', 'city', 'municipality', 'district', 'state', 'zipcode', 'phone'
        $countries = Country::orderBy('id', 'asc')->get();

        return view('buyer.create-edit', compact('countries'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'email' => 'email',
            'phone' => 'max:64',
            'company_name' => 'max:255',
        ]);

        $shipping_address_validated = $request->validate([
            //'shipping_address_fixed' => 'boolean',
            'shipping_address_name' => 'max:255',
            'shipping_address_address1' => 'max:255',
            'shipping_address_address2' => 'max:255',
            'shipping_address_address3' => 'max:255',
            'shipping_address_city' => 'max:255',
            'shipping_address_municipality' => 'max:255',
            'shipping_address_country_id'   => 'exists:countries,id',
            'shipping_address_district' => 'max:255',
            'shipping_address_state' => 'max:255',
            'shipping_address_zipcode' => 'max:16',
            'shipping_address_phone' => 'max:64',
        ]);

        $billing_address_validated = $request->validate([
            //'billing_address_fixed' => 'boolean',
            'billing_address_name' => 'max:255',
            'billing_address_address1' => 'max:255',
            'billing_address_address2' => 'max:255',
            'billing_address_address3' => 'max:255',
            'billing_address_city' => 'max:255',
            'billing_address_municipality' => 'max:255',
            'billing_address_country_id'   => 'exists:countries,id',
            'billing_address_district' => 'max:255',
            'billing_address_state' => 'max:255',
            'billing_address_zipcode' => 'max:16',
            'billing_address_phone' => 'max:64',
        ]);

        if ($shipping_address_validated['shipping_address_name']) {
            $shipping_address_data = [
                'name' => $shipping_address_validated['shipping_address_name'],
                'address1' => $shipping_address_validated['shipping_address_address1'],
                'address2' => $shipping_address_validated['shipping_address_address2'],
                'address3' => $shipping_address_validated['shipping_address_address3'],
                'city' => $shipping_address_validated['shipping_address_city'],
                'municipality' => $shipping_address_validated['shipping_address_municipality'],
                'country_id' => $shipping_address_validated['shipping_address_country_id'],
                'district' => $shipping_address_validated['shipping_address_district'],
                'state' => $shipping_address_validated['shipping_address_state'],
                'zipcode' => $shipping_address_validated['shipping_address_zipcode'],
                'phone' => $shipping_address_validated['shipping_address_phone'],
            ];
            $shipping_address = Address::create($shipping_address_data);
            $validatedData['shipping_address_id'] = $shipping_address->id;
        }

        if ($billing_address_validated['billing_address_name']) {
            $billing_address_data = [
                'name' => $billing_address_validated['billing_address_name'],
                'address1' => $billing_address_validated['billing_address_address1'],
                'address2' => $billing_address_validated['billing_address_address2'],
                'address3' => $billing_address_validated['billing_address_address3'],
                'city' => $billing_address_validated['billing_address_city'],
                'municipality' => $billing_address_validated['billing_address_municipality'],
                'country_id' => $billing_address_validated['billing_address_country_id'],
                'district' => $billing_address_validated['billing_address_district'],
                'state' => $billing_address_validated['billing_address_state'],
                'zipcode' => $billing_address_validated['billing_address_zipcode'],
                'phone' => $billing_address_validated['billing_address_phone'],
            ];
            $billing_address = Address::create($billing_address_data);
            $validatedData['billing_address_id'] = $billing_address->id;
        }

        $buyer = Buyer::create($validatedData);

        return redirect()->route('buyers.index')->with('status', 'Cliente creado correctamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Buyer  $buyer
     * @return \Illuminate\Http\Response
     */
    public function show(Buyer $buyer)
    {
        return $this->edit($buyer);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Buyer  $buyer
     * @return \Illuminate\Http\Response
     */
    public function edit(Buyer $buyer)
    {
        $countries = Country::orderBy('id', 'asc')->get();

        return view('buyer.create-edit', compact('buyer', 'countries'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Buyer  $buyer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Buyer $buyer)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'email' => 'email',
            'phone' => 'max:64',
            'company_name' => 'max:255',
        ]);

        $shipping_address_validated = $request->validate([
            //'shipping_address_fixed' => 'boolean',
            'shipping_address_name' => 'max:255',
            'shipping_address_address1' => 'max:255',
            'shipping_address_address2' => 'max:255',
            'shipping_address_address3' => 'max:255',
            'shipping_address_city' => 'max:255',
            'shipping_address_municipality' => 'max:255',
            'shipping_address_country_id'   => 'exists:countries,id',
            'shipping_address_district' => 'max:255',
            'shipping_address_state' => 'max:255',
            'shipping_address_zipcode' => 'max:16',
            'shipping_address_phone' => 'max:64',
        ]);

        $billing_address_validated = $request->validate([
            //'billing_address_fixed' => 'boolean',
            'billing_address_name' => 'max:255',
            'billing_address_address1' => 'max:255',
            'billing_address_address2' => 'max:255',
            'billing_address_address3' => 'max:255',
            'billing_address_city' => 'max:255',
            'billing_address_municipality' => 'max:255',
            'billing_address_country_id'   => 'exists:countries,id',
            'billing_address_district' => 'max:255',
            'billing_address_state' => 'max:255',
            'billing_address_zipcode' => 'max:16',
            'billing_address_phone' => 'max:64',
        ]);

        if ($shipping_address_validated['shipping_address_name']) {
            $shipping_address_data = [
                'name' => $shipping_address_validated['shipping_address_name'],
                'address1' => $shipping_address_validated['shipping_address_address1'],
                'address2' => $shipping_address_validated['shipping_address_address2'],
                'address3' => $shipping_address_validated['shipping_address_address3'],
                'city' => $shipping_address_validated['shipping_address_city'],
                'municipality' => $shipping_address_validated['shipping_address_municipality'],
                'country_id' => $shipping_address_validated['shipping_address_country_id'],
                'district' => $shipping_address_validated['shipping_address_district'],
                'state' => $shipping_address_validated['shipping_address_state'],
                'zipcode' => $shipping_address_validated['shipping_address_zipcode'],
                'phone' => $shipping_address_validated['shipping_address_phone'],
            ];

            if ($buyer->shipping_address_id)
                Address::whereId($buyer->shipping_address_id)->update($shipping_address_data);
            else {
                $shipping_address = Address::create($shipping_address_data);
                $validatedData['shipping_address_id'] = $shipping_address->id;
            }
        }

        if ($billing_address_validated['billing_address_name']) {
            $billing_address_data = [
                'name' => $billing_address_validated['billing_address_name'],
                'address1' => $billing_address_validated['billing_address_address1'],
                'address2' => $billing_address_validated['billing_address_address2'],
                'address3' => $billing_address_validated['billing_address_address3'],
                'city' => $billing_address_validated['billing_address_city'],
                'municipality' => $billing_address_validated['billing_address_municipality'],
                'country_id' => $billing_address_validated['billing_address_country_id'],
                'district' => $billing_address_validated['billing_address_district'],
                'state' => $billing_address_validated['billing_address_state'],
                'zipcode' => $billing_address_validated['billing_address_zipcode'],
                'phone' => $billing_address_validated['billing_address_phone'],
            ];
            if ($buyer->billing_address_id)
                Address::whereId($buyer->billing_address_id)->update($billing_address_data);
            else {
                $billing_address = Address::create($billing_address_data);
                $validatedData['billing_address_id'] = $billing_address->id;
            }
        }

        Buyer::whereId($buyer->id)->update($validatedData);

        return redirect()->route('orders.index')->with('status', 'Cliente modificado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Buyer $buyer
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(Buyer $buyer)
    {
        try {
            $buyer->delete();
        } catch (QueryException $e) {
            return redirect()->route('buyers.index')->with('status', $e->getMessage());
        }

        return redirect()->route('buyers.index')->with('status', 'Cliente eliminado.');
    }
}
