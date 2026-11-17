<?php

namespace App\Http\Controllers;

use App\Services\DocumentTraceabilityService;
use Illuminate\View\View;

class TraceabilityController extends Controller
{
    public function __construct(
        protected DocumentTraceabilityService $traceabilityService
    ) {}

    /**
     * Show document traceability flow (شجرة تتبع المستندات صعوداً وهبوطاً).
     */
    public function show(string $type, int $id): View
    {
        $trace = $this->traceabilityService->trace($type, $id);

        return view('traceability.show', [
            'title' => "تتبع شجرة المستند: {$trace['title']}",
            'trace' => $trace,
        ]);
    }
}
