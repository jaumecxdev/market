<?php

namespace App\Http\Middleware;


use App\Token;
use Closure;
use Illuminate\Support\Facades\Storage;


class TokenAuth
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
        // https://app.mpespecialist.com/api/token/qdyoba4z3z7mmc921cq6g9tbuvk6hi8n
        $path = explode('/', $request->path());
        $token = Token::whereToken($path[2])->first();
        if (isset($token)) {
            $request->token = $token;
            Storage::append('api/token/' .date('Y-m-d'). '.json', json_encode($token));

            return $next($request);
        }

        Storage::append('api/token/' .date('Y-m-d'). '_ERROR.json', json_encode([$request, $next]));
        return response()->json([
            'code'      => 1,
            'type'      => 'token_invalid',
            'message'   => 'Not a valid API Token.',
            'data'      => '',
        ]);
    }
}
