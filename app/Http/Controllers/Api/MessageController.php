<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\UserMessage;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * 获取消息列表（支持按用户过滤）
     * GET /api/v1/messages
     */
    public function index(Request $request)
    {
        try {
            $userId = $request->query('user_id');

            $messages = Message::query()
                ->active()
                ->ordered()
                ->get(['id', 'title', 'content', 'weigh', 'status', 'createtime', 'updatetime', 'to_all', 'user_ids']);

            // 过滤与用户相关的消息
            if ($userId) {
                $messages = $messages->filter(function ($m) use ($userId) {
                    if ($m->to_all) return true;
                    if (!$m->user_ids) return false;
                    $ids = array_filter(array_map('trim', explode(',', $m->user_ids)));
                    return in_array((string)$userId, array_map('strval', $ids), true);
                })->values();
            } else {
                // 未提供用户ID仅返回全体消息
                $messages = $messages->where('to_all', 1)->values();
            }

            // 附加阅读状态
            if ($userId) {
                $messages = $messages->map(function ($m) use ($userId) {
                    $um = UserMessage::query()
                        ->where('message_id', $m->id)
                        ->where('user_id', $userId)
                        ->first();
                    $m->viewed = $um ? (int)$um->viewed : 0;
                    $m->viewtime = $um ? (int)$um->viewtime : null;
                    // 移除不必要的内部字段
                    unset($m->user_ids);
                    return $m;
                });
            } else {
                $messages = $messages->map(function ($m) {
                    unset($m->user_ids);
                    return $m;
                });
            }

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => $messages,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'Internal Server Error',
                'data' => [],
            ], 500);
        }
    }

    /**
     * 获取单条消息详情（附带阅读状态）
     * GET /api/v1/messages/{id}
     */
    public function show(Request $request, $id)
    {
        try {
            $userId = $request->query('user_id');
            $m = Message::query()->where('id', $id)->first(['id', 'title', 'content', 'weigh', 'status', 'createtime', 'updatetime', 'to_all', 'user_ids']);
            if (!$m || $m->status !== 'normal') {
                return response()->json([
                    'code' => 404,
                    'message' => 'message not found',
                    'data' => null,
                ], 404);
            }

            // 校验可见性（如果提供了 user_id）
            if ($userId) {
                $visible = $m->to_all ? true : ($m->user_ids && in_array((string)$userId, array_map('strval', array_filter(array_map('trim', explode(',', $m->user_ids)))), true));
                if (!$visible) {
                    return response()->json([
                        'code' => 403,
                        'message' => 'forbidden',
                        'data' => null,
                    ], 403);
                }
                $um = UserMessage::query()
                    ->where('message_id', $m->id)
                    ->where('user_id', $userId)
                    ->first();
                $m->viewed = $um ? (int)$um->viewed : 0;
                $m->viewtime = $um ? (int)$um->viewtime : null;
            }
            unset($m->user_ids);

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => $m,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'Internal Server Error',
                'data' => null,
            ], 500);
        }
    }

    /**
     * 标记消息为已读
     * POST /api/v1/messages/mark-read
     */
    public function markRead(Request $request)
    {
        $messageId = (int)($request->input('message_id'));
        $userId = (int)($request->input('user_id'));

        if (!$messageId || !$userId) {
            return response()->json([
                'code' => 422,
                'message' => 'message_id and user_id are required',
                'data' => null,
            ], 422);
        }

        $m = Message::query()->where('id', $messageId)->first();
        if (!$m || $m->status !== 'normal') {
            return response()->json([
                'code' => 404,
                'message' => 'message not found',
                'data' => null,
            ], 404);
        }

        // 由于 to_all 不会预生成用户消息记录，这里需要 upsert
        $now = now()->timestamp;
        $um = UserMessage::query()
            ->where('message_id', $messageId)
            ->where('user_id', $userId)
            ->first();

        if ($um) {
            $um->viewed = 1;
            $um->viewtime = $now;
            $um->updatetime = $now;
            $um->save();
        } else {
            $um = new UserMessage();
            $um->message_id = $messageId;
            $um->user_id = $userId;
            $um->viewed = 1;
            $um->viewtime = $now;
            $um->createtime = $now;
            $um->updatetime = $now;
            $um->save();
        }

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => ['message_id' => $messageId, 'user_id' => $userId, 'viewed' => 1],
        ]);
    }

    /**
     * 获取未读消息数量
     * GET /api/v1/messages/unread-count?user_id=
     */
    public function unreadCount(Request $request)
    {
        $userId = (int)$request->query('user_id');
        if (!$userId) {
            return response()->json([
                'code' => 422,
                'message' => 'user_id is required',
                'data' => ['count' => 0],
            ], 422);
        }

        try {
            $messages = Message::query()
                ->active()
                ->ordered()
                ->get(['id', 'to_all', 'user_ids']);

            $messages = $messages->filter(function ($m) use ($userId) {
                if ($m->to_all) return true;
                if (!$m->user_ids) return false;
                $ids = array_filter(array_map('trim', explode(',', $m->user_ids)));
                return in_array((string)$userId, array_map('strval', $ids), true);
            });

            $count = 0;
            foreach ($messages as $m) {
                $um = UserMessage::query()
                    ->where('message_id', $m->id)
                    ->where('user_id', $userId)
                    ->first();
                if (!$um || !$um->viewed) {
                    $count++;
                }
            }

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => ['count' => $count],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'Internal Server Error',
                'data' => ['count' => 0],
            ], 500);
        }
    }
}