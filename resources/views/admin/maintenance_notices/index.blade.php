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
                            <a href="{{ url('/admin/maintenance_notices/create') }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> 新增通知
                            </a>
                            <button type="button" class="btn btn-default btn-sm" onclick="location.reload()">
                                <i class="fa fa-refresh"></i> 刷新
                            </button>
                        </div>
                    </div>

                    <div class="box-body">
                        <div class="clearfix" style="margin-bottom:10px;">
                            <div class="pull-right text-muted" style="padding-top:6px;">
                                总计: {{ $notices->total() }} 条
                            </div>
                        </div>

                        @if($notices->count() === 0)
                            <div class="text-center" style="padding:60px 0;">
                                <i class="fa fa-info-circle text-muted" style="font-size:36px;"></i>
                                <p class="text-muted" style="margin-top:10px;">暂无维护通知数据</p>
                                <a href="{{ url('/admin/maintenance_notices/create') }}" class="btn btn-primary btn-sm">添加第一个通知</a>
                            </div>
                        @else
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
                                        <td style="max-width:480px; word-break:break-word; white-space:normal;">
                                            {{ \Illuminate\Support\Str::limit($notice->content, 120) }}
                                        </td>
                                        <td>{{ $notice->weigh }}</td>
                                        <td>
                                            <span class="label label-{{ $notice->status === 'normal' ? 'success' : 'danger' }}">
                                                {{ $notice->status === 'normal' ? '显示' : '隐藏' }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ url('/admin/maintenance_notices/'.$notice->id.'/edit') }}" class="btn btn-xs btn-info">
                                                <i class="fa fa-edit"></i> 编辑
                                            </a>
                                            <form action="{{ url('/admin/maintenance_notices/'.$notice->id) }}" method="POST" style="display:inline">
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

                            <div class="box-footer clearfix">
                                {{ $notices->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection