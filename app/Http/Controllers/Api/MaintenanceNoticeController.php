<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceNotice;

class MaintenanceNoticeController extends Controller
{
    // GET /api/v1/maintenance-notices
    public function index()
    {
        $items = MaintenanceNotice::query()->active()->ordered()->get([
            'id', 'title', 'content', 'weigh', 'status', 'createtime', 'updatetime',
        ]);
        return response()->json([
            'data' => $items,
        ]);
    }
}