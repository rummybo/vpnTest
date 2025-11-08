<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Models\UserMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $query = Message::query();
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }
        $messages = $query->orderByDesc('weigh')->orderByDesc('id')->paginate(20);
        return view('admin.messages.index', compact('messages'));
    }

    public function create()
    {
        return view('admin.messages.create');
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'weigh' => 'nullable|integer',
            'status' => 'required|string|in:normal,hidden',
            'to_all' => 'nullable|in:0,1',
            'user_ids' => 'nullable|string'
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $now = time();
        $data['createtime'] = $now;
        $data['updatetime'] = $now;
        $data['to_all'] = (int)($data['to_all'] ?? 0);
        $message = Message::create($data);

        $recipientIds = $this->parseRecipientIds($data);
        $this->syncUserMessages($message->id, $recipientIds, $now);

        return redirect()->route('admin.messages.index')->with('success', '创建成功');
    }

    public function edit($id)
    {
        $message = Message::findOrFail($id);
        return view('admin.messages.edit', compact('message'));
    }

    public function update(Request $request, $id)
    {
        $message = Message::findOrFail($id);
        $data = $request->all();
        $validator = Validator::make($data, [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'weigh' => 'nullable|integer',
            'status' => 'required|string|in:normal,hidden',
            'to_all' => 'nullable|in:0,1',
            'user_ids' => 'nullable|string'
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data['updatetime'] = time();
        $data['to_all'] = (int)($data['to_all'] ?? 0);
        $message->update($data);

        $recipientIds = $this->parseRecipientIds($data);
        $this->syncUserMessages($message->id, $recipientIds, time());

        return redirect()->route('admin.messages.index')->with('success', '更新成功');
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            UserMessage::where('message_id', $id)->delete();
            Message::where('id', $id)->delete();
        });
        return redirect()->route('admin.messages.index')->with('success', '删除成功');
    }

    // v2board 风格接口：获取列表
    public function fetch(Request $request)
    {
        $query = Message::query();
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }
        $query->orderByDesc('weigh')->orderByDesc('id');
        $pageSize = (int)($request->input('page_size', 10));
        $pageNo = (int)($request->input('page_no', 1));
        $total = $query->count();
        $items = $query->forPage($pageNo, $pageSize)->get();
        return response()->json([
            'total' => $total,
            'items' => $items,
        ]);
    }

    // v2board 风格接口：保存（新增或更新）并创建用户消息记录
    public function save(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'id' => 'nullable|integer',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'weigh' => 'nullable|integer',
            'status' => 'required|string|in:normal,hidden',
            'to_all' => 'nullable|in:0,1',
            'user_ids' => 'nullable|string'
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $now = time();
        $data['to_all'] = (int)($data['to_all'] ?? 0);

        DB::beginTransaction();
        try {
            if (!empty($data['id'])) {
                $message = Message::findOrFail($data['id']);
                $data['updatetime'] = $now;
                $message->update($data);
            } else {
                $data['createtime'] = $now;
                $data['updatetime'] = $now;
                $message = Message::create($data);
            }

            $recipientIds = $this->parseRecipientIds($data);
            $this->syncUserMessages($message->id, $recipientIds, $now);

            DB::commit();
            return response()->json(['message' => 'ok', 'data' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => '保存失败: ' . $e->getMessage()], 500);
        }
    }

    // v2board 风格接口：删除
    public function drop(Request $request)
    {
        $id = (int)$request->input('id');
        if (!$id) {
            return response()->json(['message' => 'id 必填'], 422);
        }

        DB::transaction(function () use ($id) {
            UserMessage::where('message_id', $id)->delete();
            Message::where('id', $id)->delete();
        });
        return response()->json(['message' => 'ok']);
    }

    // v2board 风格接口：显示/隐藏
    public function show(Request $request)
    {
        $id = (int)$request->input('id');
        $status = $request->input('status');
        if (!$id || !in_array($status, ['normal','hidden'], true)) {
            return response()->json(['message' => '参数错误'], 422);
        }
        Message::where('id', $id)->update([
            'status' => $status,
            'updatetime' => time(),
        ]);
        return response()->json(['message' => 'ok']);
    }

    // v2board 风格接口：调整排序
    public function sort(Request $request)
    {
        $id = (int)$request->input('id');
        $weigh = (int)$request->input('weigh');
        if (!$id) {
            return response()->json(['message' => 'id 必填'], 422);
        }
        Message::where('id', $id)->update([
            'weigh' => $weigh,
            'updatetime' => time(),
        ]);
        return response()->json(['message' => 'ok']);
    }

    private function parseRecipientIds(array $data): array
    {
        if (!empty($data['to_all']) && (int)$data['to_all'] === 1) {
            return User::query()->pluck('id')->all();
        }
        $idsRaw = $data['user_ids'] ?? '';
        if (!$idsRaw) return [];
        $ids = preg_split('/[\s,，]+/', $idsRaw);
        $ids = array_filter($ids, function ($v) { return is_numeric($v); });
        $ids = array_map('intval', $ids);
        if (empty($ids)) return [];
        // 仅保留存在的用户ID
        $exists = User::query()->whereIn('id', $ids)->pluck('id')->all();
        return array_values(array_unique($exists));
    }

    private function syncUserMessages(int $messageId, array $recipientIds, int $now): void
    {
        // 先清空旧的收件人记录
        UserMessage::where('message_id', $messageId)->delete();
        if (empty($recipientIds)) return;
        $rows = [];
        foreach ($recipientIds as $uid) {
            $rows[] = [
                'message_id' => $messageId,
                'user_id' => $uid,
                'viewed' => 0,
                'viewtime' => null,
                'createtime' => $now,
                'updatetime' => $now,
            ];
        }
        DB::table('v2_user_messages')->insert($rows);
    }
}