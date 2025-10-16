<?php

namespace Modules\Approval\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Approval\Entities\ApprovalRuleUser;

class CheckApprovalAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, $role = null)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->withErrors('Silakan login terlebih dahulu.');
        }

        // Cari apakah user ada di ApprovalRuleUsers
        $approvalAccess = ApprovalRuleUser::where('user_id', $user->id)
            ->where('is_active', true);

        // Jika role diberikan di middleware, filter sesuai role
        if ($role) {
            $approvalAccess->where('role', $role);
        }

        $hasAccess = $approvalAccess->exists();

        if (!$hasAccess) {
            abort(403, 'Anda tidak memiliki akses ke approval system.');
        }

        return $next($request);
    }
}
