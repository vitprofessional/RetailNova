<?php

namespace App\Http\Middleware;

use App\Models\BusinessSetup;
use App\Support\BusinessContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/** Sends only back-style redirects from unassigned tenant accounts to a dashboard notice. */
class RequireBusinessAssociation
{
    public function handle(Request $request, Closure $next): Response
    {
        $actor = BusinessContext::actor();
        if ($actor === null || $actor->hasSuperAdminPrivileges()) {
            return $next($request);
        }

        $businessId = BusinessContext::validId($actor->businessId);
        $hasBusiness = $businessId !== null && BusinessSetup::query()->whereKey($businessId)->exists();
        if ($hasBusiness) {
            return $next($request);
        }

        if ($request->routeIs('dashboard')) {
            $this->warn($request, $actor->id, $actor->businessId);
            $request->session()->flash('business_context_warning', __('common.admin_account_not_linked_to_business'));
            return $next($request);
        }

        // Preserve controller and authorization responses such as 403/404; only intercept back().
        $previous = $request->session()->previousUrl();
        $response = $next($request);
        if (!$response instanceof RedirectResponse) {
            return $response;
        }

        $target = $response->getTargetUrl();
        $backStyle = SafeBackRedirect::isUnsafe($target)
            || ($previous !== null && $this->sameLocation($target, $previous));
        if (!$backStyle) {
            return $response;
        }

        $this->warn($request, $actor->id, $actor->businessId);

        return redirect()->route('dashboard')
            ->with('business_context_warning', __('common.admin_account_not_linked_to_business'));
    }

    private function sameLocation(string $left, string $right): bool
    {
        return parse_url($left, PHP_URL_PATH) === parse_url($right, PHP_URL_PATH)
            && parse_url($left, PHP_URL_QUERY) === parse_url($right, PHP_URL_QUERY);
    }

    private function warn(Request $request, int $adminId, mixed $businessId): void
    {
        Log::warning('Admin account has no valid business association.', [
            'admin_user_id' => $adminId,
            'business_id' => $businessId,
            'route' => $request->route()?->getName(),
        ]);
    }
}
