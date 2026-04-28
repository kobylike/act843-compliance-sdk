<?php



namespace GhanaCompliance\Act843SDK\Http\Middleware;

use GhanaCompliance\Act843SDK\Models\ComplianceLog;
use GhanaCompliance\Act843SDK\Services\Security\ComplianceAnalyzer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DetectUnauthorizedAccess
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('admin/*') && !Auth::check()) {

            $analyzer = new ComplianceAnalyzer();

            $analysis = $analyzer->analyze([
                'attempts' => 1,
                'type' => 'UNAUTHORIZED_ACCESS'
            ]);

            ComplianceLog::create([
                'type' => 'UNAUTHORIZED_ACCESS',
                'ip_address' => $request->ip(),
                'score' => $analysis['score'],
                'severity' => $analysis['severity'],
                'attempts' => 1,
                'meta' => [
                    'route' => $request->path()
                ],
            ]);
        }

        return $next($request);
    }
}
