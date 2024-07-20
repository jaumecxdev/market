<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;


class TokenController extends Controller
{
    public function __construct()
    {
        //$this->middleware('auth')->except('csv');
        $this->middleware('TokenAuth')->only(['token']);
    }


    public function token(Request $request)
    {
        return $this->{$request->token->type}($request);
    }


    private function json(Request $request)
    {
        try {
            // header('Content-Type', 'application/json')
            return response()->json([
                'code'      => 0,
                'type'      => 'json',
                'message'   => '',
                'data'      => Storage::get($request->token->params),
            ]);

        } catch (Throwable $th) {
            return $this->traitThrowable('csv', $th);
        }
    }


    private function csv(Request $request)
    {
        try {
            return response(Storage::get($request->token->params))->header('Content-Type', 'text/csv');       //'mp/csv/' .$shop->code. '.csv');

        } catch (Throwable $th) {
            return $this->traitThrowable('csv', $th);
        }
    }


    private function text(Request $request)
    {
        try {
            return response(Storage::get($request->token->params))->header('Content-Type', 'text/plain');       //'mp/csv/' .$shop->code. '.csv');

        } catch (Throwable $th) {
            return $this->traitThrowable('text', $th);
        }
    }


    private function download(Request $request)
    {
        try {
            //'mp/csv/' .$shop->code. '.csv');
            return Storage::download($request->token->params);
            //return response()->download(storage_path('app/'.$request->token->params));

        } catch (Throwable $th) {
            return $this->traitThrowable('download', $th);
        }
    }


    private function traitThrowable($type, $th)
    {
        Storage::append('api/token/' .date('Y-m-d'). '_'.$type.'_ERRORS.json', json_encode($th->getMessage()));
        return response()->json([
            'code'      => 2,
            'type'      => 'resource_not_found',
            'message'   => $type. ' resource not found.',
            'data'      => '',
        ]);
    }



}
