<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use App\Services\ConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SystemConfigController extends Controller
{
    /**
     * 显示系统配置管理页面
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.system_configs.index');
    }

    /**
     * 获取配置列表（用于后台管理）
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetch(Request $request)
    {
        try {
            $group = $request->input('group');
            $type = $request->input('type');
            
            $query = SystemConfig::query();
            
            if ($group) {
                $query->where('group', $group);
            }
            
            if ($type) {
                $query->where('type', $type);
            }
            
            $configs = $query->orderBy('group', 'asc')
                ->orderBy('sort', 'asc')
                ->orderBy('id', 'asc')
                ->get();
            
            // 格式化数据用于前端显示
            $formattedConfigs = $configs->map(function ($config) {
                return [
                    'id' => $config->id,
                    'key' => $config->key,
                    'value' => $config->value,
                    'name' => $config->name,
                    'description' => $config->description,
                    'type' => $config->type,
                    'group' => $config->group,
                    'sort' => $config->sort,
                    'options' => $config->options,
                    'is_system' => $config->is_system,
                    'status' => $config->status,
                    'display_value' => ConfigService::getDisplayValue($config),
                    'created_at' => $config->created_at ? $config->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $config->updated_at ? $config->updated_at->format('Y-m-d H:i:s') : null,
                ];
            });
            
            return response()->json([
                'data' => $formattedConfigs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 保存配置
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'key' => 'required|string|max:100',
                'value' => 'nullable',
                'name' => 'required|string|max:100',
                'description' => 'nullable|string|max:255',
                'type' => 'required|in:switch,text,number,select,textarea',
                'group' => 'required|string|max:50',
                'sort' => 'nullable|integer',
                'options' => 'nullable|json',
                'status' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $data = $request->all();
            $configId = $request->input('id');

            // 验证配置值
            if (!ConfigService::validateValue($data['type'], $data['value'], $data['options'] ? json_decode($data['options'], true) : null)) {
                return response()->json([
                    'message' => '配置值格式不正确'
                ], 400);
            }

            if ($configId) {
                // 更新现有配置
                $config = SystemConfig::findOrFail($configId);
                
                // 系统配置不允许修改某些字段
                if ($config->is_system) {
                    $allowedFields = ['value', 'description', 'sort', 'status'];
                    $data = array_intersect_key($data, array_flip($allowedFields));
                }
                
                $config->update($data);
            } else {
                // 创建新配置
                $data['is_system'] = false; // 新创建的配置默认不是系统配置
                $config = SystemConfig::create($data);
            }

            // 清除缓存
            ConfigService::refresh();

            return response()->json([
                'data' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 删除配置
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function drop(Request $request)
    {
        try {
            $id = $request->input('id');
            
            if (!$id) {
                return response()->json([
                    'message' => '缺少必要参数'
                ], 400);
            }

            $config = SystemConfig::findOrFail($id);
            
            // 系统配置不允许删除
            if ($config->is_system) {
                return response()->json([
                    'message' => '系统配置不允许删除'
                ], 400);
            }

            $config->delete();

            // 清除缓存
            ConfigService::refresh();

            return response()->json([
                'data' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 切换配置状态
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle(Request $request)
    {
        try {
            $id = $request->input('id');
            
            if (!$id) {
                return response()->json([
                    'message' => '缺少必要参数'
                ], 400);
            }

            $config = SystemConfig::findOrFail($id);
            
            if ($config->type === 'switch') {
                // 开关类型切换值
                $config->value = $config->value === '1' ? '0' : '1';
            } else {
                // 其他类型切换状态
                $config->status = !$config->status;
            }
            
            $config->save();

            // 清除缓存
            ConfigService::refresh();

            return response()->json([
                'data' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 批量更新配置
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchUpdate(Request $request)
    {
        try {
            $configs = $request->input('configs', []);
            
            if (!is_array($configs) || empty($configs)) {
                return response()->json([
                    'message' => '配置数据不能为空'
                ], 400);
            }

            foreach ($configs as $key => $value) {
                ConfigService::set($key, $value);
            }

            return response()->json([
                'data' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取配置分组
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function groups()
    {
        try {
            $groups = SystemConfig::select('group')
                ->groupBy('group')
                ->orderBy('group')
                ->pluck('group')
                ->map(function ($group) {
                    return [
                        'value' => $group,
                        'label' => $this->getGroupLabel($group)
                    ];
                });

            return response()->json([
                'data' => $groups
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 刷新配置缓存
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshCache()
    {
        try {
            ConfigService::refresh();
            
            return response()->json([
                'data' => true,
                'message' => '缓存刷新成功'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取分组标签
     */
    private function getGroupLabel($group)
    {
        $labels = [
            'general' => '通用配置',
            'frontend' => '前端配置',
            'system' => '系统配置',
            'payment' => '支付配置',
            'email' => '邮件配置',
            'sms' => '短信配置',
        ];

        return $labels[$group] ?? $group;
    }
}