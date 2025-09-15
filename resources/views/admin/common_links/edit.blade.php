@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>编辑常用导航</h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> 首页</a></li>
            <li><a href="{{ route('admin.common_links.index') }}">常用导航</a></li>
            <li class="active">编辑</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">导航信息</h3>
                    </div>

                    <form action="{{ route('admin.common_links.update', $commonLink->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="box-body">
                            <div class="form-group">
                                <label for="title">标题 <span class="text-red">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="{{ old('title', $commonLink->title) }}" placeholder="请输入导航标题" required>
                                @error('title')
                                    <span class="text-red">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="url">链接地址 <span class="text-red">*</span></label>
                                <input type="url" class="form-control" id="url" name="url" 
                                       value="{{ old('url', $commonLink->url) }}" placeholder="请输入链接地址" required>
                                @error('url')
                                    <span class="text-red">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="logo">图标URL</label>
                                <input type="text" class="form-control" id="logo" name="logo" 
                                       value="{{ old('logo', $commonLink->logo) }}" placeholder="请输入图标URL">
                                @error('logo')
                                    <span class="text-red">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="weigh">权重</label>
                                <input type="number" class="form-control" id="weigh" name="weigh" 
                                       value="{{ old('weigh', $commonLink->weigh) }}" placeholder="数值越大排序越靠前">
                                @error('weigh')
                                    <span class="text-red">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="status">状态</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="normal" {{ old('status', $commonLink->status) == 'normal' ? 'selected' : '' }}>显示</option>
                                    <option value="hidden" {{ old('status', $commonLink->status) == 'hidden' ? 'selected' : '' }}>隐藏</option>
                                </select>
                                @error('status')
                                    <span class="text-red">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary">更新</button>
                            <a href="{{ route('admin.common_links.index') }}" class="btn btn-default">取消</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection