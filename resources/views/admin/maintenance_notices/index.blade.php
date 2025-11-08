@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>维护通知管理</h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> 首页</a></li>
            <li class="active">维护通知</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">维护通知列表</h3>
                        <div class="box-tools">
                            <a href="{{ route('admin.maintenance_notices.create') }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> 新增通知
                            </a>
                        </div>
                    </div>

                    <div class="box-body">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="25%">标题</th>
                                    <th>内容</th>
                                    <th width="10%">权重</th>
                                    <th width="10%">状态</th>
                                    <th width="15%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($notices as $notice)
                                <tr>
                                    <td>{{ $notice->id }}</td>
                                    <td>{{ $notice->title }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($notice->content, 120) }}</td>
                                    <td>{{ $notice->weigh }}</td>
                                    <td>
                                        <span class="label label-{{ $notice->status === 'normal' ? 'success' : 'danger' }}">
                                            {{ $notice->status === 'normal' ? '显示' : '隐藏' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.maintenance_notices.edit', $notice->id) }}" class="btn btn-xs btn-info">
                                            <i class="fa fa-edit"></i> 编辑
                                        </a>
                                        <form action="{{ route('admin.maintenance_notices.destroy', $notice->id) }}" method="POST" style="display:inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('确定删除吗？')">
                                                <i class="fa fa-trash"></i> 删除
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="box-footer clearfix">
                        {{ $notices->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
