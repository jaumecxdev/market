<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class ConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index()
    {
        return view('config.index');
    }


    public function getRequest(Request $request)
    {
        return redirect()->route('config')->with('status', 'Consulta no encontrada.');
    }






}
