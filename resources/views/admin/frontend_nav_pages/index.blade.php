@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>前端导航页管理</h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> 首页</a></li>
            <li class="active">前端导航页</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">导航页列表</h3>
                        <div class="box-tools">
                            <a href="{{ route('admin.frontend_nav_pages.create') }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> 新增导航页
                            </a>
                        </div>
                    </div>

                    <!-- 搜索表单 -->
                    <div class="box-body">
                        <form method="GET" action="{{ route('admin.frontend_nav_pages.index') }}" class="form-inline" style="margin-bottom: 20px;">
                            <div class="form-group">
                                <input type="text" name="search" class="form-control" placeholder="搜索名称或URL" value="{{ request('search') }}">
                            </div>
                            <div class="form-group">
                                <select name="status" class="form-control">
                                    <option value="">全部状态</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>启用</option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>禁用</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-default">搜索</button>
                            <a href="{{ route('admin.frontend_nav_pages.index') }}" class="btn btn-default">重置</a>
                        </form>

                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                {{ session('success') }}
                            </div>
                        @endif

                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="20%">导航页名称</th>
                                    <th width="15%">图标</th>
                                    <th width="30%">跳转URL</th>
                                    <th width="8%">排序</th>
                                    <th width="8%">状态</th>
                                    <th width="14%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($navPages as $navPage)
                                <tr>
                                    <td>{{ $navPage->id }}</td>
                                    <td>{{ $navPage->name }}</td>
                                    <td>
                                        @if($navPage->icon)
                                            @if(str_starts_with($navPage->icon, 'http'))
                                                <img src="{{ $navPage->icon }}" alt="图标" style="width: 24px; height: 24px;">
                                            @else
                                                <i class="{{ $navPage->icon }}" style="font-size: 18px;"></i>
                                            @endif
                                            <br><small class="text-muted">{{ $navPage->icon }}</small>
                                        @else
                                            <span class="text-muted">无</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ $navPage->url }}" target="_blank" class="text-primary">
                                            {{ Str::limit($navPage->url, 40) }}
                                        </a>
                                    </td>
                                    <td>{{ $navPage->sort }}</td>
                                    <td>
                                        <span class="label label-{{ $navPage->status === 'active' ? 'success' : 'danger' }}">
                                            {{ $navPage->status_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.frontend_nav_pages.edit', $navPage->id) }}" class="btn btn-xs btn-info">
                                            <i class="fa fa-edit"></i> 编辑
                                        </a>
                                        <form action="{{ route('admin.frontend_nav_pages.destroy', $navPage->id) }}" method="POST" style="display:inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('确定删除这个导航页吗？')">
                                                <i class="fa fa-trash"></i> 删除
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">暂无数据</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="box-footer clearfix">
                        {{ $navPages->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection