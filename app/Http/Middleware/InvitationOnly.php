<?php

namespace App\Http\Middleware;

use App\Models\SupportCase;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvitationOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('invitation_accepted', false)) {
            abort(404);
        }

        if ($caseId = $request->session()->get('support_case_id')) {
            $identityRoutes = $request->routeIs('identity.show', 'identity.continue', 'code.show');

            if (! $identityRoutes) {
                abort_unless(SupportCase::whereKey($caseId)->value('access_enabled'), 404);
            }
        }

        return $next($request);
    }
}
