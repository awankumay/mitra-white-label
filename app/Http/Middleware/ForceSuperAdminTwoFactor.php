<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Symfony\Component\HttpFoundation\Response;

class ForceSuperAdminTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if (
            config('core.auth.two_factor.super_admin_forced') &&
            $user &&
            $user->hasRole('super_admin') &&
            ! $user->hasConfirmedTwoFactor()
        ) {
            /** @var BreezyCore $breezy */
            $breezy = filament('filament-breezy');

            $myProfileRouteName = 'filament.'.Filament::getCurrentOrDefaultPanel()->getId().'.pages.'.$breezy->slug();

            if (! $request->routeIs($myProfileRouteName) && ! str($request->route()?->getName())->contains('logout')) {
                return redirect()->route($myProfileRouteName);
            }
        }

        return $next($request);
    }
}
