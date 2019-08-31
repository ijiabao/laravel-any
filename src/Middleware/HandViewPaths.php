<?php

namespace Ijiabao\Laravel\Middleware;

use \Closure;

class HandViewPaths
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
        $prefix = \Route::current()->getPrefix();
        // 取第一段URI
        if(preg_match('/^[^\/]+/', trim($prefix,'/'), $mat)){
            $location = resource_path('views'.DIRECTORY_SEPARATOR.$mat[0]);
            prepend_view_path($location);
        }

        return $next($request);
    }
}
