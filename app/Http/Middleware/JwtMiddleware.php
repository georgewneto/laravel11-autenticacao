<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try
        {
            $uri = urldecode(json_encode($request->server('REQUEST_URI')));
            $precisa_autenticar = true;
            if (str_contains($uri, 'api\/login')) {
                $precisa_autenticar = false;
            }
            if (str_contains($uri, 'api\/register')) {
                $precisa_autenticar = false;
            }
            if (str_contains($uri, 'api\/forgotpassword')) {
                $precisa_autenticar = false;
            }
            if ($precisa_autenticar == true)
            {
                $user = JWTAuth::parseToken()->authenticate();
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token not valid'], 401);
        }

        return $next($request);
    }
}
