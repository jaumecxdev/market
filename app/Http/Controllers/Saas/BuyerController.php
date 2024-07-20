<?php

namespace App\Http\Controllers\Saas;

use App\Address;
use App\Buyer;
use App\Country;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class BuyerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function show(Buyer $buyer)
    {
        return $this->edit($buyer);
    }


    public function edit(Buyer $buyer)
    {
        $countries = Country::orderBy('id', 'asc')->get();

        return view('saas.buyer.create-edit', compact('buyer', 'countries'));
    }


    public function update(Request $request, Buyer $buyer)
    {
        $validatedData = $request->validate([
            'name'          => 'required|max:255',
            'email'         => 'nullable|email',
            'phone'         => 'nullable|max:64',
            'company_name'  => 'nullable|max:255',
        ]);

        $shipping_address_validated = $request->validate([
            //'shipping_address_fixed' => 'boolean',
            'shipping_address_name'         => 'nullable|max:255',
            'shipping_address_address1'     => 'nullable|max:255',
            'shipping_address_address2'     => 'nullable|max:255',
            'shipping_address_address3'     => 'nullable|max:255',
            'shipping_address_city'         => 'nullable|max:255',
            'shipping_address_municipality' => 'nullable|max:255',
            'shipping_address_country_id'   => 'nullable|exists:countries,id',
            'shipping_address_district'     => 'nullable|max:255',
            'shipping_address_state'        => 'nullable|max:255',
            'shipping_address_zipcode'      => 'nullable|max:16',
            'shipping_address_phone'        => 'nullable|max:64',
        ]);

        $billing_address_validated = $request->validate([
            //'billing_address_fixed' => 'boolean',
            'billing_address_name'          => 'nullable|max:255',
            'billing_address_address1'      => 'nullable|max:255',
            'billing_address_address2'      => 'nullable|max:255',
            'billing_address_address3'      => 'nullable|max:255',
            'billing_address_city'          => 'nullable|max:255',
            'billing_address_municipality'  => 'nullable|max:255',
            'billing_address_country_id'    => 'nullable|exists:countries,id',
            'billing_address_district'      => 'nullable|max:255',
            'billing_address_state'         => 'nullable|max:255',
            'billing_address_zipcode'       => 'nullable|max:16',
            'billing_address_phone'         => 'nullable|max:64',
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
                $shipping_address_data['type'] = 'shipping';
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
                $shipping_address_data['type'] = 'billing';
                $billing_address = Address::create($billing_address_data);
                $validatedData['billing_address_id'] = $billing_address->id;
            }
        }

        Buyer::whereId($buyer->id)->update($validatedData);

        return redirect()->route('saas.orders')->with('status', 'Cliente modificado correctamente.');
    }

}
