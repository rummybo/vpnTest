<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApkChannelStat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApkChannelStatAdminController extends Controller
{
    /**
     * 统计数据列表页面
     */
    public function index(Request $request)
    {
        try {
            $query = ApkChannelStat::query();
            
            // 简化的筛选逻辑
            if ($request->filled('channel_code')) {
                $query->where('channel_code', $request->channel_code);
            }
            if ($request->filled('type')) {
                $query->where('type', (int) $request->type);
            }
            if ($request->filled('device_id')) {
                $query->where('device_id', 'like', '%' . $request->device_id . '%');
            }
            if ($request->filled('user_id')) {
                $query->where('user_id', (int) $request->user_id);
            }
            if ($request->filled('app_version')) {
                $query->where('app_version', $request->app_version);
            }
            if ($request->filled('platform')) {
                $query->where('platform', $request->platform);
            }
            if ($request->filled('ip_address')) {
                $query->where('ip_address', 'like', '%' . $request->ip_address . '%');
            }
            if ($request->filled('start_date')) {
                $query->where('created_at', '>=', strtotime($request->start_date));
            }
            if ($request->filled('end_date')) {
                $query->where('created_at', '<=', strtotime($request->end_date . ' 23:59:59'));
            }

            // 排序
            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            // 分页
            $perPage = (int) $request->get('per_page', 20);
            if ($perPage <= 0 || $perPage > 200) {
                $perPage = 20;
            }
            $stats = $query->paginate($perPage)->appends($request->query());

            // 下拉选项
            $channels = ApkChannelStat::select('channel_code')->distinct()->orderBy('channel_code')->pluck('channel_code');
            $appVersions = ApkChannelStat::whereNotNull('app_version')->select('app_version')->distinct()->orderBy('app_version')->pluck('app_version');
            $platforms = ApkChannelStat::whereNotNull('platform')->select('platform')->distinct()->orderBy('platform')->pluck('platform');

            return view('admin.apk_channel_stats.index', compact('stats', 'channels', 'appVersions', 'platforms'));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * 汇总统计页面
     */
    public function summary(Request $request)
    {
        try {
            $startDate = $request->get('start_date', date('Y-m-d', strtotime('-30 days')));
            $endDate = $request->get('end_date', date('Y-m-d'));
            $startTimestamp = strtotime($startDate);
            $endTimestamp = strtotime($endDate . ' 23:59:59');

            // 简化的汇总数据
            $channelSummary = DB::table('apk_channel_stats')
                ->select([
                    'channel_code',
                    DB::raw('SUM(CASE WHEN type = 1 THEN 1 ELSE 0 END) as download_count'),
                    DB::raw('SUM(CASE WHEN type = 2 THEN 1 ELSE 0 END) as register_count'),
                    DB::raw('SUM(CASE WHEN type = 3 THEN 1 ELSE 0 END) as login_count'),
                    DB::raw('COUNT(DISTINCT device_id) as unique_devices'),
                    DB::raw('COUNT(DISTINCT user_id) as unique_users'),
                    DB::raw('COUNT(*) as total_count'),
                ])
                ->whereBetween('created_at', [$startTimestamp, $endTimestamp])
                ->groupBy('channel_code')
                ->orderBy('total_count', 'desc')
                ->get();

            // 简化的实时统计
            $todayStart = strtotime(date('Y-m-d'));
            $now = time();
            $realTimeStats = [
                'today_total' => ApkChannelStat::whereBetween('created_at', [$todayStart, $now])->count(),
                'today_downloads' => ApkChannelStat::where('type', 1)->whereBetween('created_at', [$todayStart, $now])->count(),
                'today_registers' => ApkChannelStat::where('type', 2)->whereBetween('created_at', [$todayStart, $now])->count(),
                'today_logins' => ApkChannelStat::where('type', 3)->whereBetween('created_at', [$todayStart, $now])->count(),
            ];

            // 空的数据结构，避免视图报错
            $trendData = collect();
            $conversionData = collect();
            $deviceUserStats = [
                'total_devices' => 0,
                'total_users' => 0,
                'platform_stats' => collect(),
                'version_stats' => collect()
            ];

            return view('admin.apk_channel_stats.summary', compact(
                'channelSummary', 'trendData', 'realTimeStats', 'conversionData',
                'deviceUserStats', 'startDate', 'endDate'
            ));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Summary Internal Server Error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * 实时统计仪表盘
     */
    public function dashboard()
    {
        try {
            $todayStart = strtotime(date('Y-m-d'));
            $weekStart = strtotime('monday this week');
            $monthStart = strtotime(date('Y-m-01'));
            $now = time();

            $todayStats = [
                'downloads' => ApkChannelStat::where('type', 1)->whereBetween('created_at', [$todayStart, $now])->count(),
                'registers' => ApkChannelStat::where('type', 2)->whereBetween('created_at', [$todayStart, $now])->count(),
                'logins' => ApkChannelStat::where('type', 3)->whereBetween('created_at', [$todayStart, $now])->count(),
                'unique_devices' => ApkChannelStat::whereBetween('created_at', [$todayStart, $now])->distinct('device_id')->count(),
            ];

            $weekStats = [
                'downloads' => ApkChannelStat::where('type', 1)->whereBetween('created_at', [$weekStart, $now])->count(),
                'registers' => ApkChannelStat::where('type', 2)->whereBetween('created_at', [$weekStart, $now])->count(),
                'logins' => ApkChannelStat::where('type', 3)->whereBetween('created_at', [$weekStart, $now])->count(),
                'unique_devices' => ApkChannelStat::whereBetween('created_at', [$weekStart, $now])->distinct('device_id')->count(),
            ];

            $monthStats = [
                'downloads' => ApkChannelStat::where('type', 1)->whereBetween('created_at', [$monthStart, $now])->count(),
                'registers' => ApkChannelStat::where('type', 2)->whereBetween('created_at', [$monthStart, $now])->count(),
                'logins' => ApkChannelStat::where('type', 3)->whereBetween('created_at', [$monthStart, $now])->count(),
                'unique_devices' => ApkChannelStat::whereBetween('created_at', [$monthStart, $now])->distinct('device_id')->count(),
            ];

            return view('admin.apk_channel_stats.dashboard', compact('todayStats', 'weekStats', 'monthStats'));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Dashboard Internal Server Error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * CSV 导出
     */
    public function export(Request $request)
    {
        try {
            $query = ApkChannelStat::query();
            
            // 应用筛选条件
            if ($request->filled('channel_code')) {
                $query->where('channel_code', $request->channel_code);
            }
            if ($request->filled('type')) {
                $query->where('type', (int) $request->type);
            }
            if ($request->filled('device_id')) {
                $query->where('device_id', 'like', '%' . $request->device_id . '%');
            }
            if ($request->filled('user_id')) {
                $query->where('user_id', (int) $request->user_id);
            }
            if ($request->filled('app_version')) {
                $query->where('app_version', $request->app_version);
            }
            if ($request->filled('platform')) {
                $query->where('platform', $request->platform);
            }
            if ($request->filled('ip_address')) {
                $query->where('ip_address', 'like', '%' . $request->ip_address . '%');
            }
            if ($request->filled('start_date')) {
                $query->where('created_at', '>=', strtotime($request->start_date));
            }
            if ($request->filled('end_date')) {
                $query->where('created_at', '<=', strtotime($request->end_date . ' 23:59:59'));
            }
            
            $stats = $query->orderBy('created_at', 'desc')->limit(50000)->get();

            $filename = 'apk_channel_stats_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($stats) {
                $output = fopen('php://output', 'w');
                // UTF-8 BOM
                fwrite($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($output, ['ID', '渠道代码', '统计类型', '设备ID', '用户ID', 'IP地址', 'UA', '应用版本', '平台', '扩展数据', '创建时间']);
                foreach ($stats as $row) {
                    fputcsv($output, [
                        $row->id,
                        $row->channel_code,
                        ApkChannelStat::getTypeText($row->type),
                        $row->device_id,
                        $row->user_id,
                        $row->ip_address,
                        $row->user_agent,
                        $row->app_version,
                        $row->platform,
                        json_encode($row->extra_data, JSON_UNESCAPED_UNICODE),
                        date('Y-m-d H:i:s', $row->created_at),
                    ]);
                }
                fclose($output);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Export Internal Server Error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * 图表数据接口
     */
    public function chartData(Request $request)
    {
        try {
            $type = $request->get('type');
            $startDate = $request->get('start_date', date('Y-m-d', strtotime('-7 days')));
            $endDate = $request->get('end_date', date('Y-m-d'));
            $startTimestamp = strtotime($startDate);
            $endTimestamp = strtotime($endDate . ' 23:59:59');

            switch ($type) {
                case 'trend':
                    // 趋势数据 - 按日期统计
                    $rows = DB::table('apk_channel_stats')
                        ->select([
                            DB::raw('DATE(FROM_UNIXTIME(created_at)) as date'),
                            'type',
                            DB::raw('COUNT(*) as count')
                        ])
                        ->whereBetween('created_at', [$startTimestamp, $endTimestamp])
                        ->groupBy('date', 'type')
                        ->orderBy('date')
                        ->get();
                    
                    $grouped = [];
                    foreach ($rows as $r) {
                        $date = $r->date;
                        if (!isset($grouped[$date])) {
                            $grouped[$date] = [1 => 0, 2 => 0, 3 => 0];
                        }
                        $grouped[$date][$r->type] = (int) $r->count;
                    }
                    
                    $labels = array_keys($grouped);
                    $downloads = [];
                    $registers = [];
                    $logins = [];
                    foreach ($labels as $d) {
                        $downloads[] = $grouped[$d][1] ?? 0;
                        $registers[] = $grouped[$d][2] ?? 0;
                        $logins[] = $grouped[$d][3] ?? 0;
                    }
                    
                    return response()->json(compact('labels', 'downloads', 'registers', 'logins'));
                    
                case 'channel':
                    // 渠道对比数据
                    $rows = DB::table('apk_channel_stats')
                        ->select([
                            'channel_code',
                            DB::raw('SUM(CASE WHEN type = 1 THEN 1 ELSE 0 END) as download_count'),
                            DB::raw('SUM(CASE WHEN type = 2 THEN 1 ELSE 0 END) as register_count'),
                            DB::raw('SUM(CASE WHEN type = 3 THEN 1 ELSE 0 END) as login_count')
                        ])
                        ->whereBetween('created_at', [$startTimestamp, $endTimestamp])
                        ->groupBy('channel_code')
                        ->orderBy('download_count', 'desc')
                        ->get();
                    
                    $labels = [];
                    $downloads = [];
                    $registers = [];
                    $logins = [];
                    foreach ($rows as $r) {
                        $labels[] = $r->channel_code;
                        $downloads[] = (int) $r->download_count;
                        $registers[] = (int) $r->register_count;
                        $logins[] = (int) $r->login_count;
                    }
                    
                    return response()->json(compact('labels', 'downloads', 'registers', 'logins'));
                    
                case 'conversion':
                    // 转化率数据
                    $rows = DB::table('apk_channel_stats')
                        ->select([
                            'channel_code',
                            DB::raw('COUNT(CASE WHEN type = 1 THEN 1 END) as downloads'),
                            DB::raw('COUNT(CASE WHEN type = 2 THEN 1 END) as registers'),
                            DB::raw('COUNT(CASE WHEN type = 3 THEN 1 END) as logins'),
                            DB::raw('ROUND(COUNT(CASE WHEN type = 2 THEN 1 END) * 100.0 / NULLIF(COUNT(CASE WHEN type = 1 THEN 1 END), 0), 2) as download_to_register_rate'),
                            DB::raw('ROUND(COUNT(CASE WHEN type = 3 THEN 1 END) * 100.0 / NULLIF(COUNT(CASE WHEN type = 2 THEN 1 END), 0), 2) as register_to_login_rate')
                        ])
                        ->whereBetween('created_at', [$startTimestamp, $endTimestamp])
                        ->groupBy('channel_code')
                        ->having(DB::raw('COUNT(CASE WHEN type = 1 THEN 1 END)'), '>', 0)
                        ->get();
                    
                    $labels = [];
                    $download_to_register_rate = [];
                    $register_to_login_rate = [];
                    foreach ($rows as $r) {
                        $labels[] = $r->channel_code;
                        $download_to_register_rate[] = (float) ($r->download_to_register_rate ?? 0);
                        $register_to_login_rate[] = (float) ($r->register_to_login_rate ?? 0);
                    }
                    
                    return response()->json(compact('labels', 'download_to_register_rate', 'register_to_login_rate'));
                    
                case 'platform':
                    // 平台分布数据
                    $rows = DB::table('apk_channel_stats')
                        ->select(['platform', DB::raw('COUNT(*) as count')])
                        ->whereBetween('created_at', [$startTimestamp, $endTimestamp])
                        ->whereNotNull('platform')
                        ->groupBy('platform')
                        ->orderBy('count', 'desc')
                        ->get();
                    
                    $labels = [];
                    $counts = [];
                    foreach ($rows as $r) {
                        $labels[] = $r->platform;
                        $counts[] = (int) $r->count;
                    }
                    
                    return response()->json(compact('labels', 'counts'));
                    
                default:
                    return response()->json(['error' => 'Invalid chart type'], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Chart Data Internal Server Error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}