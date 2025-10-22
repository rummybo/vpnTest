@extends('admin.layout')

@section('title', 'APK 渠道统计 - 仪表盘')

@section('styles')
<style>
  .main-header { display: none !important; }
  .main-footer { display: none !important; }
  .content-wrapper, .right-side, .main-footer { padding-top: 0 !important; }
</style>
@endsection

@section('content')
<div class="content-wrapper">
  <div class="container">
    <section class="content-header">
      <h1>APK 渠道统计 <small>实时仪表盘</small></h1>
    </section>

    <section class="content">
      <div class="row">
        <div class="col-sm-4">
          <div class="box box-success">
            <div class="box-header with-border"><h3 class="box-title">今日</h3></div>
            <div class="box-body">
              <p>下载：<strong>{{ $todayStats['downloads'] }}</strong></p>
              <p>注册：<strong>{{ $todayStats['registers'] }}</strong></p>
              <p>登录：<strong>{{ $todayStats['logins'] }}</strong></p>
              <p>独立设备：<strong>{{ $todayStats['unique_devices'] }}</strong></p>
            </div>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="box box-warning">
            <div class="box-header with-border"><h3 class="box-title">本周</h3></div>
            <div class="box-body">
              <p>下载：<strong>{{ $weekStats['downloads'] }}</strong></p>
              <p>注册：<strong>{{ $weekStats['registers'] }}</strong></p>
              <p>登录：<strong>{{ $weekStats['logins'] }}</strong></p>
              <p>独立设备：<strong>{{ $weekStats['unique_devices'] }}</strong></p>
            </div>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="box box-info">
            <div class="box-header with-border"><h3 class="box-title">本月</h3></div>
            <div class="box-body">
              <p>下载：<strong>{{ $monthStats['downloads'] }}</strong></p>
              <p>注册：<strong>{{ $monthStats['registers'] }}</strong></p>
              <p>登录：<strong>{{ $monthStats['logins'] }}</strong></p>
              <p>独立设备：<strong>{{ $monthStats['unique_devices'] }}</strong></p>
            </div>
          </div>
        </div>
      </div>

      <div class="text-right">
        <a href="{{ route('admin.apk_channel_stats.index') }}" class="btn btn-default">返回列表</a>
        <a href="{{ route('admin.apk_channel_stats.summary') }}" class="btn btn-info">查看汇总</a>
      </div>
    </section>
  </div>
</div>
@endsection
