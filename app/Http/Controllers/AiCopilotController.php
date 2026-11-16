<?php

namespace App\Http\Controllers;

use App\Services\CateringAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AiCopilotController extends Controller
{
    public function __construct(
        protected CateringAiService $aiService
    ) {}

    public function ask(Request $request): JsonResponse
    {
        Gate::authorize('use-ai-copilot');

        $validated = $request->validate([
            'prompt' => 'required|string|max:1000',
        ]);

        $response = $this->aiService->ask($validated['prompt']);

        return response()->json([
            'success' => true,
            'response' => $response,
        ]);
    }
}
