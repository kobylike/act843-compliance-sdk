<?php

namespace GhanaCompliance\Act843SDK\Http\Controllers\Api;

use GhanaCompliance\Act843SDK\Http\Controllers\Controller;
use GhanaCompliance\Act843SDK\Services\Security\ComplianceAnalyzer;
use GhanaCompliance\Act843SDK\Compliance\SDK\ComplianceClient; // keep for track method
use Illuminate\Http\Request;

class ComplianceApiController extends Controller
{
    protected ComplianceClient $client;

    public function __construct(ComplianceClient $client)
    {
        $this->client = $client;
    }

    public function track(Request $request)
    {
        $validated = $request->validate([
            'event' => 'required|string',
            'ip' => 'required|ip',
            'attempts' => 'sometimes|integer',
            'route' => 'sometimes|string',
        ]);

        if ($validated['event'] === 'auth.failed') {
            $this->client->trackFailedLogin($validated['ip'], $validated['attempts'] ?? 1);
        }

        return response()->json(['status' => 'tracked']);
    }

    public function analyze(Request $request)
    {
        $validated = $request->validate([
            'ip' => 'required|ip',
            'attempts' => 'required|integer',
        ]);

        // Directly use ComplianceAnalyzer to guarantee explanation field
        $analyzer = new ComplianceAnalyzer();
        $result = $analyzer->analyze([
            'ip' => $validated['ip'],
            'attempts' => $validated['attempts'],
            'type' => 'API_REQUEST',
        ]);

        return response()->json([
            'ip' => $validated['ip'],
            'attempts' => $validated['attempts'],
            'score' => $result['score'],
            'severity' => $result['severity'],
            'explanation' => $result['explanation'],      // ✅ now included
            'analysis' => $result['analysis'],            // optional detailed breakdown
        ]);
    }
}
