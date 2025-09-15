@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>新增常用导航</h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> 首页</a></li>
            <li><a href="{{ route('admin.common_links.index') }}">常用导航</a></li>
            <li class="active">新增</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">导航信息</h3>
                    </div>

                    <form action="{{ route('admin.common_links.store') }}" method="POST">
                        @csrf
                        <div class="box-body">
                            <div class="form-group">
                                <label for="title">标题 <span class="text-red">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="{{ old('title') }}" placeholder="请输入导航标题" required>
                                @error('title')
                                    <span class="text-red">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="url">链接地址 <span class="text-red">*</span></label>
                                <input type="url" class="form-control" id="url" name="url" 
                                       value="{{ old('url') }}" placeholder="请输入链接地址" required>
                                @error('url')
                                    <span class="text-red">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="logo">图标URL</label>
                                <input type="text" class="form-control" id="logo" name="logo" 
                                       value="{{ old('logo') }}" placeholder="请输入图标URL">
                                @error('logo')
                                    <span class="text-red">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="weigh">权重</label>
                                <input type="number" class="form-control" id="weigh" name="weigh" 
                                       value="{{ old('weigh', 0) }}" placeholder="数值越大排序越靠前">
                                @error('weigh')
                                    <span class="text-red">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="status">状态</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="normal" {{ old('status') == 'normal' ? 'selected' : '' }}>显示</option>
                                    <option value="hidden" {{ old('status') == 'hidden' ? 'selected' : '' }}>隐藏</option>
                                </select>
                                @error('status')
                                    <span class="text-red">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary">保存</button>
                            <a href="{{ route('admin.common_links.index') }}" class="btn btn-default">取消</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection