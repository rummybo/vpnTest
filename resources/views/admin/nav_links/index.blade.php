@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>福利导航管理</h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> 首页</a></li>
            <li class="active">福利导航</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">导航链接列表</h3>
                        <div class="box-tools">
                            <a href="{{ route('admin.nav_links.create') }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> 新增导航
                            </a>
                        </div>
                    </div>

                    <div class="box-body">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="20%">标题</th>
                                    <th width="30%">链接</th>
                                    <th width="10%">图标</th>
                                    <th width="10%">排序</th>
                                    <th width="10%">状态</th>
                                    <th width="15%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($navLinks as $navLink)
                                <tr>
                                    <td>{{ $navLink->id }}</td>
                                    <td>{{ $navLink->title }}</td>
                                    <td>{{ $navLink->link }}</td>
                                    <td>{{ $navLink->icon }}</td>
                                    <td>{{ $navLink->sort }}</td>
                                    <td>
                                        <span class="label label-{{ $navLink->status ? 'success' : 'danger' }}">
                                            {{ $navLink->status ? '显示' : '隐藏' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.nav_links.edit', $navLink->id) }}" class="btn btn-xs btn-info">
                                            <i class="fa fa-edit"></i> 编辑
                                        </a>
                                        <form action="{{ route('admin.nav_links.destroy', $navLink->id) }}" method="POST" style="display:inline">
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
                        {{ $navLinks->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection