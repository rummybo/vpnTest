@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>新增导航链接</h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> 首页</a></li>
            <li><a href="{{ route('admin.nav_links.index') }}">福利导航</a></li>
            <li class="active">新增</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">导航链接信息</h3>
                    </div>

                    <form action="{{ route('admin.nav_links.store') }}" method="POST">
                        @csrf
                        <div class="box-body">
                            <div class="form-group">
                                <label for="title">标题 *</label>
                                <input type="text" class="form-control" id="title" name="title" required 
                                       placeholder="请输入导航标题" value="{{ old('title') }}">
                                @error('title')
                                    <span class="help-block text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="link">链接 *</label>
                                <input type="url" class="form-control" id="link" name="link" required 
                                       placeholder="请输入完整的URL链接" value="{{ old('link') }}">
                                @error('link')
                                    <span class="help-block text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="icon">图标</label>
                                <input type="text" class="form-control" id="icon" name="icon" 
                                       placeholder="请输入图标类名（如：fa fa-home）" value="{{ old('icon') }}">
                                @error('icon')
                                    <span class="help-block text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="sort">排序</label>
                                <input type="number" class="form-control" id="sort" name="sort" 
                                       value="{{ old('sort', 0) }}" min="0">
                                @error('sort')
                                    <span class="help-block text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="status">状态</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>显示</option>
                                    <option value="0" {{ old('status', 1) == 0 ? 'selected' : '' }}>隐藏</option>
                                </select>
                                @error('status')
                                    <span class="help-block text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary">保存</button>
                            <a href="{{ route('admin.nav_links.index') }}" class="btn btn-default">取消</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection