<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ConvertStringToBoolean
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->has('status')) {
            $status = $request->status;
            if (is_string($status)) {
                if (in_array(strtolower($status), ['true', '1', 'yes', 'y', 'on'])) {
                    $request->merge(['status' => true]);
                } elseif (in_array(strtolower($status), ['false', '0', 'no', 'n', 'off'])) {
                    $request->merge(['status' => false]);
                }
            }
        }
        
        return $next($request);
    }
}