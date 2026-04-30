<?php

namespace GhanaCompliance\Act843SDK\Http\Controllers\Api;

use Illuminate\Routing\Controller;  // Use Laravel's base controller
use GhanaCompliance\Act843SDK\Services\Security\ComplianceAnalyzer;
use GhanaCompliance\Act843SDK\Compliance\SDK\ComplianceClient;
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
        // ... unchanged
    }

    public function analyze(Request $request)
    {
        // ... unchanged
    }
}
