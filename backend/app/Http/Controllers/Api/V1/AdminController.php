<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function ping(): JsonResponse
    {
        return ApiResponse::success(
            ['authorized' => true],
            'Administrator authorization successful.'
        );
    }
}
