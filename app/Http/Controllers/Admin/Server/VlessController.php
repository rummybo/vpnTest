<?php

namespace App\Http\Controllers\Admin\Server;

use App\Http\Requests\Admin\ServerVlessSave;
use App\Http\Requests\Admin\ServerVlessUpdate;
use App\Models\ServerVless;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VlessController extends Controller
{
    public function save(ServerVlessSave $request)
    {
        $params = $request->validated();
        if ($request->input('id')) {
            $server = ServerVless::find($request->input('id'));
            if (!$server) {
                abort(500, '服务器不存在');
            }
            try {
                $server->update($params);
            } catch (\Exception $e) {
                abort(500, '保存失败');
            }
            return response(['data' => true]);
        }

        if (!ServerVless::create($params)) {
            abort(500, '创建失败');
        }

        return response(['data' => true]);
    }

    public function drop(Request $request)
    {
        if ($request->input('id')) {
            $server = ServerVless::find($request->input('id'));
            if (!$server) {
                abort(500, '节点ID不存在');
            }
        }
        return response(['data' => $server->delete()]);
    }

    public function update(ServerVlessUpdate $request)
    {
        $params = $request->only(['show']);
        $server = ServerVless::find($request->input('id'));
        if (!$server) {
            abort(500, '该服务器不存在');
        }
        try {
            $server->update($params);
        } catch (\Exception $e) {
            abort(500, '保存失败');
        }
        return response(['data' => true]);
    }

    public function copy(Request $request)
    {
        $server = ServerVless::find($request->input('id'));
        $server->show = 0;
        if (!$server) {
            abort(500, '服务器不存在');
        }
        if (!ServerVless::create($server->toArray())) {
            abort(500, '复制失败');
        }

        return response(['data' => true]);
    }

    public function fetch()
    {
        return response(['data' => ServerVless::orderBy('sort', 'ASC')->get()]);
    }
}