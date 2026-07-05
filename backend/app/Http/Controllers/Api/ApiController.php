<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;

/** Thin base for every API controller — auth/validation traits + JSON helpers. */
abstract class ApiController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    protected function ok(mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }

    protected function fail(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
