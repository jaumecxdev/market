<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TwitterController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except('api');
        $this->middleware('APIToken')->only('api');
    }


    public function api(Request $request)
    {
        Storage::append('api/twitter/' . date('y-m-d') . '_oauth.json', json_encode($request->getMethod()));
        Storage::append('api/twitter/' . date('y-m-d') . '_oauth.json', json_encode($request->all()));

        return true;
    }


}
