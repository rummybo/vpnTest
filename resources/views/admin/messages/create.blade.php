@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>新增消息</h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> 首页</a></li>
            <li><a href="{{ url('/admin/messages') }}">消息中心</a></li>
            <li class="active">新增</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">消息内容</h3>
                    </div>

                    <form id="create-form" action="{{ url('/admin/messages') }}" method="POST">
                        <div class="box-body">
                            <div class="form-group">
                                <label for="title">标题 <span class="text-red">*</span></label>
                                <input type="text" class="form-control" id="title" name="title"
                                       value="" placeholder="请输入消息标题" required>
                            </div>

                            <div class="form-group">
                                <label for="content">内容 <span class="text-red">*</span></label>
                                <textarea class="form-control" id="content" name="content" rows="6" placeholder="请输入消息内容" required></textarea>
                            </div>

                            <div class="form-group">
                                <label for="weigh">权重</label>
                                <input type="number" class="form-control" id="weigh" name="weigh"
                                       value="0" placeholder="数值越大排序越靠前">
                            </div>

                            <div class="form-group">
                                <label for="status">状态</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="normal" selected>显示</option>
                                    <option value="hidden">隐藏</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>发送范围</label>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="to_all" name="to_all" value="1"> 发送给全部用户
                                    </label>
                                </div>
                                <small class="text-muted">如勾选“全部用户”，将对 <code>v2_user</code> 中的所有用户生成消息记录。</small>
                            </div>

                            <div class="form-group" id="user-ids-group">
                                <label for="user_ids">指定用户ID（多个用逗号分隔）</label>
                                <input type="text" class="form-control" id="user_ids" name="user_ids"
                                       value="" placeholder="例如：1,2,3">
                            </div>
                        </div>

                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary">保存</button>
                            <a href="{{ url('/admin/messages') }}" class="btn btn-default">取消</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script>
$(function() {
    function toggleUserIds() {
        var checked = $('#to_all').is(':checked');
        $('#user_ids').prop('disabled', checked);
        $('#user-ids-group').find('label').toggleClass('text-muted', checked);
    }
    toggleUserIds();
    $('#to_all').on('change', toggleUserIds);

    $('#create-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var data = $form.serialize();
        // 使用 /admin 前缀下的保存接口，避免 {secure_path} 为空导致 404
        var api = '/admin/messages/save';
        $.post(api, data)
            .done(function() {
                window.location.href = '{{ url('/admin/messages') }}';
            })
            .fail(function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '保存失败';
                alert(msg);
            });
    });
});
</script>
@endsection