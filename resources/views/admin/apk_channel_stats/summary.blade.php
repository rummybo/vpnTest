@extends('admin.layout')

@section('title', 'APK 渠道统计 - 汇总与图表')

@section('styles')
<style>
  .main-header { display: none !important; }
  .main-footer { display: none !important; }
  .content-wrapper, .right-side, .main-footer { padding-top: 0 !important; }
  .kpi-box { text-align:center; padding:15px; }
  .kpi-box h3 { margin:10px 0; }
  canvas { max-width: 100%; }
</style>
@endsection

@section('content')
<div class="content-wrapper">
  <div class="container">
    <section class="content-header">
      <h1>APK 渠道统计 <small>汇总与图表</small></h1>
    </section>

    <section class="content">
      <div class="box box-info">
        <div class="box-header with-border">
          <form class="form-inline" method="GET">
            <div class="row">
              <div class="col-sm-3">
                <label>开始日期</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" style="width:100%">
              </div>
              <div class="col-sm-3">
                <label>结束日期</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" style="width:100%">
              </div>
              <div class="col-sm-6" style="margin-top:25px;">
                <button class="btn btn-primary" type="submit"><i class="fa fa-refresh"></i> 刷新</button>
                <a class="btn btn-default" href="{{ route('admin.apk_channel_stats.index') }}">返回列表</a>
              </div>
            </div>
          </form>
        </div>
        <div class="box-body">
          <div class="row">
            <div class="col-sm-3 kpi-box">
              <h4>今日总量</h4>
              <h3>{{ $realTimeStats['today_total'] ?? 0 }}</h3>
            </div>
            <div class="col-sm-3 kpi-box">
              <h4>今日下载</h4>
              <h3>{{ $realTimeStats['today_downloads'] ?? 0 }}</h3>
            </div>
            <div class="col-sm-3 kpi-box">
              <h4>今日注册</h4>
              <h3>{{ $realTimeStats['today_registers'] ?? 0 }}</h3>
            </div>
            <div class="col-sm-3 kpi-box">
              <h4>今日登录</h4>
              <h3>{{ $realTimeStats['today_logins'] ?? 0 }}</h3>
            </div>
          </div>

          <hr>

          <h4>趋势图（下载/注册/登录）</h4>
          <canvas id="trendChart" height="100"></canvas>

          <h4 style="margin-top:30px;">渠道对比</h4>
          <canvas id="channelChart" height="100"></canvas>

          <h4 style="margin-top:30px;">转化率</h4>
          <canvas id="conversionChart" height="100"></canvas>

          <h4 style="margin-top:30px;">平台分布</h4>
          <canvas id="platformChart" height="100"></canvas>

          <hr>

          <h4>渠道汇总</h4>
          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>渠道</th>
                  <th>下载</th>
                  <th>注册</th>
                  <th>登录</th>
                  <th>独立设备</th>
                  <th>独立用户</th>
                  <th>总量</th>
                </tr>
              </thead>
              <tbody>
                @forelse($channelSummary as $row)
                <tr>
                  <td>{{ $row->channel_code }}</td>
                  <td>{{ $row->download_count }}</td>
                  <td>{{ $row->register_count }}</td>
                  <td>{{ $row->login_count }}</td>
                  <td>{{ $row->unique_devices }}</td>
                  <td>{{ $row->unique_users }}</td>
                  <td>{{ $row->total_count }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted">暂无汇总数据</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </section>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4"></script>
<script>
  function fetchChart(type) {
    return $.getJSON("{{ route('admin.apk_channel_stats.chart') }}", {
      type: type,
      start_date: "{{ $startDate }}",
      end_date: "{{ $endDate }}"
    });
  }

  $(function(){
    // 趋势
    fetchChart('trend').done(function(res){
      var ctx = document.getElementById('trendChart').getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: res.labels,
          datasets: [
            { label: '下载', backgroundColor: 'rgba(54, 162, 235, 0.2)', borderColor: 'rgba(54,162,235,1)', data: res.downloads, fill: false },
            { label: '注册', backgroundColor: 'rgba(255, 206, 86, 0.2)', borderColor: 'rgba(255,206,86,1)', data: res.registers, fill: false },
            { label: '登录', backgroundColor: 'rgba(75, 192, 192, 0.2)', borderColor: 'rgba(75,192,192,1)', data: res.logins, fill: false },
          ]
        },
        options: { responsive: true }
      });
    });

    // 渠道对比
    fetchChart('channel').done(function(res){
      var ctx = document.getElementById('channelChart').getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: res.labels,
          datasets: [
            { label: '下载', backgroundColor: 'rgba(54,162,235,0.5)', data: res.downloads },
            { label: '注册', backgroundColor: 'rgba(255,206,86,0.5)', data: res.registers },
            { label: '登录', backgroundColor: 'rgba(75,192,192,0.5)', data: res.logins },
          ]
        },
        options: { responsive: true, scales: { xAxes: [{ stacked:false }], yAxes: [{ ticks: { beginAtZero:true } }] } }
      });
    });

    // 转化率
    fetchChart('conversion').done(function(res){
      var ctx = document.getElementById('conversionChart').getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: res.labels,
          datasets: [
            { label: '下载→注册(%)', backgroundColor: 'rgba(153,102,255,0.5)', data: res.download_to_register_rate },
            { label: '注册→登录(%)', backgroundColor: 'rgba(255,99,132,0.5)', data: res.register_to_login_rate },
          ]
        },
        options: { responsive: true, scales: { yAxes: [{ ticks: { beginAtZero:true, callback: function(v){ return v + '%' } } }] } }
      });
    });

    // 平台分布
    fetchChart('platform').done(function(res){
      var ctx = document.getElementById('platformChart').getContext('2d');
      new Chart(ctx, {
        type: 'pie',
        data: { labels: res.labels, datasets: [{ data: res.counts, backgroundColor: ['#36A2EB','#FFCE56','#4BC0C0','#FF6384','#9966FF','#FF9F40'] }] },
        options: { responsive: true }
      });
    });
  });
</script>
@endsection
