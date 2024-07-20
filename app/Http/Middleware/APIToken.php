<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Config;


class APIToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //Storage::append('api/telegram/' .date('Y-m-d'). '_APIToken.json', json_encode($request->all()));

        // https://app.mpespecialist.com/api/telegram/68BD8DD6789914DD5BC322F5638DE
        $path = explode('/', $request->path());
        if ($path[2] == config('auth.api_access_token'))
            return $next($request);

        return response()->json([
            'code'      => 1,
            'type'      => 'token_invalid',
            'message'   => 'Not a valid API Token.',
            'data'      => '',
        ]);
    }
}
