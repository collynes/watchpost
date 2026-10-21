<?php

namespace App\Http\Controllers;

use App\Http\Requests\IngestEventsRequest;
use App\Services\IngestEvents;
use Illuminate\Http\JsonResponse;

class IngestController extends Controller
{
    public function __invoke(IngestEventsRequest $request, IngestEvents $ingest): JsonResponse
    {
        $result = $ingest->handle($request->attributes->get('source'), $request->validated('events'));

        return response()->json($result, $result['accepted'] > 0 ? 201 : 200);
    }
}
