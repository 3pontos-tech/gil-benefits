<?php

namespace App\Http\Middleware;

use App\Filament\FilamentPanel;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class RestrictPartnerCollaboratorAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): BaseResponse
    {
        $user = $request->user();
        
        // If user is not authenticated, let the request continue
        if (!$user) {
            return $next($request);
        }

        // If user is a partner collaborator, ensure they can only access User Panel
        if ($user->isPartnerCollaborator()) {
            $currentPanel = Filament::getCurrentPanel();
            
            // If trying to access a panel other than User Panel, redirect to User Panel
            if ($currentPanel && $currentPanel->getId() !== FilamentPanel::User->value) {
                return redirect('/app');
            }
        }

        return $next($request);
    }
}