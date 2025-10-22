@extends('admin.layout')

@section('title', 'APK 渠道统计 - 列表')

@section('styles')
<style>
  .main-header { display: none !important; }
  .main-footer { display: none !important; }
  .content-wrapper, .right-side, .main-footer { padding-top: 0 !important; }
  .filter-row { margin-bottom: 15px; }
  .table-fixed { table-layout: fixed; }
  .table-fixed td { word-wrap: break-word; }
  .json-preview { max-width: 360px; white-space: pre-wrap; }
</style>
@endsection

@section('content')
<div class="content-wrapper">
  <div class="container">
    <section class="content-header">
      <h1>APK 渠道统计 <small>列表与筛选</small></h1>
    </section>

    <section class="content">
      <div class="box box-primary">
        <div class="box-header with-border">
          <form class="form-inline" method="GET">
            <div class="row filter-row">
              <div class="col-sm-2">
                <label>渠道</label>
                <select name="channel_code" class="form-control" style="width:100%">
                  <option value="">全部</option>
                  @foreach($channels as $ch)
                    <option value="{{ $ch }}" @if(request('channel_code')===$ch) selected @endif>{{ $ch }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-2">
                <label>类型</label>
                <select name="type" class="form-control" style="width:100%">
                  <option value="">全部</option>
                  <option value="1" @if(request('type')==='1') selected @endif>下载</option>
                  <option value="2" @if(request('type')==='2') selected @endif>注册</option>
                  <option value="3" @if(request('type')==='3') selected @endif>登录</option>
                </select>
              </div>
              <div class="col-sm-2">
                <label>版本</label>
                <select name="app_version" class="form-control" style="width:100%">
                  <option value="">全部</option>
                  @foreach($appVersions as $v)
                    <option value="{{ $v }}" @if(request('app_version')===$v) selected @endif>{{ $v }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-2">
                <label>平台</label>
                <select name="platform" class="form-control" style="width:100%">
                  <option value="">全部</option>
                  @foreach($platforms as $p)
                    <option value="{{ $p }}" @if(request('platform')===$p) selected @endif>{{ $p }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-2">
                <label>开始日期</label>
                <input type="date" class="form-control" name="start_date" value="{{ request('start_date') }}" style="width:100%">
              </div>
              <div class="col-sm-2">
                <label>结束日期</label>
                <input type="date" class="form-control" name="end_date" value="{{ request('end_date') }}" style="width:100%">
              </div>
            </div>
            <div class="row filter-row">
              <div class="col-sm-2">
                <label>设备ID</label>
                <input type="text" class="form-control" name="device_id" value="{{ request('device_id') }}" style="width:100%" placeholder="模糊">
              </div>
              <div class="col-sm-2">
                <label>用户ID</label>
                <input type="text" class="form-control" name="user_id" value="{{ request('user_id') }}" style="width:100%">
              </div>
              <div class="col-sm-2">
                <label>IP地址</label>
                <input type="text" class="form-control" name="ip_address" value="{{ request('ip_address') }}" style="width:100%" placeholder="模糊">
              </div>
              <div class="col-sm-2">
                <label>每页</label>
                <select name="per_page" class="form-control" style="width:100%">
                  @foreach([20,50,100,200] as $pp)
                    <option value="{{ $pp }}" @if((int)request('per_page',20)===$pp) selected @endif>{{ $pp }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-4" style="margin-top:25px;">
                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> 筛选</button>
                <a href="{{ route('admin.apk_channel_stats.index') }}" class="btn btn-default">重置</a>
                <a href="{{ route('admin.apk_channel_stats.export', request()->query()) }}" class="btn btn-success"><i class="fa fa-download"></i> 导出CSV</a>
                <a href="{{ route('admin.apk_channel_stats.summary') }}" class="btn btn-info"><i class="fa fa-bar-chart"></i> 汇总与图表</a>
              </div>
            </div>
          </form>
        </div>
        <div class="box-body">
          <div class="table-responsive">
            <table class="table table-bordered table-striped table-fixed">
              <thead>
                <tr>
                  <th style="width:70px">ID</th>
                  <th>渠道</th>
                  <th>类型</th>
                  <th>设备ID</th>
                  <th style="width:80px">用户ID</th>
                  <th>IP</th>
                  <th>版本/平台</th>
                  <th>扩展数据</th>
                  <th style="width:140px">时间</th>
                </tr>
              </thead>
              <tbody>
                @forelse($stats as $row)
                  <tr>
                    <td>{{ $row->id }}</td>
                    <td><span class="label label-primary">{{ $row->channel_code }}</span></td>
                    <td>
                      @if($row->type==1)
                        <span class="label label-default">下载</span>
                      @elseif($row->type==2)
                        <span class="label label-warning">注册</span>
                      @else
                        <span class="label label-success">登录</span>
                      @endif
                    </td>
                    <td><code>{{ $row->device_id }}</code></td>
                    <td>{{ $row->user_id }}</td>
                    <td>{{ $row->ip_address }}</td>
                    <td>
                      <span class="badge bg-light-blue">{{ $row->app_version }}</span>
                      <span class="badge bg-green">{{ $row->platform }}</span>
                    </td>
                    <td class="json-preview">{{ json_encode($row->extra_data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</td>
                    <td>{{ date('Y-m-d H:i:s', $row->created_at) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="9" class="text-center text-muted">暂无数据</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="text-center">
            {{ $stats->links() }}
          </div>
        </div>
      </div>
    </section>
  </div>
</div>
@endsection
