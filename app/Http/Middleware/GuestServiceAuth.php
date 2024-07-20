<?php

namespace App\Http\Middleware;

use App\Models\Guest\GuestService;
use Closure;
use Illuminate\Support\Facades\Storage;


class GuestServiceAuth
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
        // https://app.mpespecialist.com/guest?token=qdyoba4z3z7mmc921cq6g9tbuvk6hi8n
        // https://app.mpespecialist.com/guest/{order}/track/{order_item}?token=qdyoba4z3z7mmc921cq6g9tbuvk6hi8n
        // http://market.test:8080/guest/order/{order}/track/{order_item}?token=12345
        if ($token = $request->input('token')) {
            $guest_service = GuestService::firstWhere('token', $token);
            if (isset($guest_service)) {
                $request->guest_service = $guest_service;
                return $next($request);
            }
        }

        Storage::append('guest/' .date('Y-m-d'). '_UNAUTHORIZED.json', json_encode([$request->toArray(), $request->getRequestUri()]));
        return response('Unauthorized', 401)->header('Content-Type', 'text/plain');
    }
}
