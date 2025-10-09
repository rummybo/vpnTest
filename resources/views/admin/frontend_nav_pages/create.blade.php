@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>新增前端导航页</h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> 首页</a></li>
            <li><a href="{{ route('admin.frontend_nav_pages.index') }}">前端导航页</a></li>
            <li class="active">新增</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-8">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">导航页信息</h3>
                    </div>

                    <form action="{{ route('admin.frontend_nav_pages.store') }}" method="POST">
                        @csrf
                        <div class="box-body">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul style="margin: 0;">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="form-group">
                                <label for="name">导航页名称 <span class="text-red">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="{{ old('name') }}" placeholder="请输入导航页名称" required>
                                <p class="help-block">显示在前端的导航页名称</p>
                            </div>

                            <div class="form-group">
                                <label for="icon">图标</label>
                                <input type="text" class="form-control" id="icon" name="icon" 
                                       value="{{ old('icon') }}" placeholder="fa fa-home 或 https://example.com/icon.png">
                                <p class="help-block">
                                    支持Font Awesome图标类名（如：fa fa-home）或图标URL地址<br>
                                    <small class="text-muted">
                                        常用图标：fa fa-home（首页）、fa fa-user（用户）、fa fa-cog（设置）、fa fa-star（收藏）
                                    </small>
                                </p>
                                <div id="icon-preview" style="margin-top: 10px;">
                                    <!-- 图标预览区域 -->
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="url">跳转URL <span class="text-red">*</span></label>
                                <input type="url" class="form-control" id="url" name="url" 
                                       value="{{ old('url') }}" placeholder="https://example.com" required>
                                <p class="help-block">点击导航页时跳转的URL地址</p>
                            </div>

                            <div class="form-group">
                                <label for="sort">排序</label>
                                <input type="number" class="form-control" id="sort" name="sort" 
                                       value="{{ old('sort', 0) }}" min="0" placeholder="0">
                                <p class="help-block">数字越小排序越靠前，默认为0</p>
                            </div>

                            <div class="form-group">
                                <label for="status">状态</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>启用</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>禁用</option>
                                </select>
                                <p class="help-block">禁用后前端将不会显示此导航页</p>
                            </div>
                        </div>

                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary">保存</button>
                            <a href="{{ route('admin.frontend_nav_pages.index') }}" class="btn btn-default">返回</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-4">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">使用说明</h3>
                    </div>
                    <div class="box-body">
                        <h5>图标设置：</h5>
                        <ul>
                            <li>Font Awesome图标：输入类名如 <code>fa fa-home</code></li>
                            <li>自定义图标：输入完整URL地址</li>
                            <li>留空则不显示图标</li>
                        </ul>
                        
                        <h5>排序规则：</h5>
                        <ul>
                            <li>数字越小排序越靠前</li>
                            <li>相同排序按ID升序排列</li>
                            <li>建议使用10、20、30等间隔数字</li>
                        </ul>

                        <h5>URL格式：</h5>
                        <ul>
                            <li>必须包含协议（http://或https://）</li>
                            <li>支持内部页面和外部链接</li>
                            <li>建议使用https://提高安全性</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // 图标预览功能
    $('#icon').on('input', function() {
        var iconValue = $(this).val().trim();
        var previewContainer = $('#icon-preview');
        
        if (!iconValue) {
            previewContainer.html('');
            return;
        }
        
        var previewHtml = '';
        if (iconValue.startsWith('http://') || iconValue.startsWith('https://')) {
            // URL图标
            previewHtml = '<strong>预览：</strong><img src="' + iconValue + '" alt="图标预览" style="width: 24px; height: 24px; margin-left: 10px;" onerror="this.style.display=\'none\'">';
        } else {
            // Font Awesome图标
            previewHtml = '<strong>预览：</strong><i class="' + iconValue + '" style="font-size: 18px; margin-left: 10px;"></i>';
        }
        
        previewContainer.html(previewHtml);
    });

    // 页面加载时触发预览
    $('#icon').trigger('input');
});
</script>
@endpush
@endsection