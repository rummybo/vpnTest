@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>新增维护通知</h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> 首页</a></li>
            <li><a href="{{ url('/admin/maintenance_notices') }}">维护通知</a></li>
            <li class="active">新增</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">通知内容</h3>
                    </div>

                    <form action="{{ url('/admin/maintenance_notices') }}" method="POST">
                        @csrf
                        <div class="box-body">
                            <div class="form-group">
                                <label for="title">标题 <span class="text-red">*</span></label>
                                <input type="text" class="form-control" id="title" name="title"
                                       value="{{ old('title') }}" placeholder="请输入通知标题" required>
                                @error('title')
                                    <span class="text-red">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="content">内容 <span class="text-red">*</span></label>
                                <textarea class="form-control" id="content" name="content" rows="6" placeholder="请输入通知内容" required>{{ old('content') }}</textarea>
                                @error('content')
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
                                    <option value="normal" {{ old('status', 'normal') === 'normal' ? 'selected' : '' }}>显示</option>
                                    <option value="hidden" {{ old('status') === 'hidden' ? 'selected' : '' }}>隐藏</option>
                                </select>
                                @error('status')
                                    <span class="text-red">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary">保存</button>
                            <a href="{{ url('/admin/maintenance_notices') }}" class="btn btn-default">取消</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection