<?php
// =====================
// 配置路径
// =====================
$base_dir = __DIR__; // public/AppManager/
$android_file = $base_dir . "/android_version.txt";
$ios_file = $base_dir . "/ios_version.txt";
$apk_dir = $base_dir . "/apk/";

// 确保 apk 目录存在
if (!file_exists($apk_dir)) {
    mkdir($apk_dir, 0777, true);
}

// =====================
// 处理提交 (只处理表单直传，不走 AJAX)
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $platform = $_POST['platform'];
    $version = trim($_POST['version']);
    $download_url = "";

    if ($platform === 'android') {
        if (!empty($_FILES['apk_file']['name'])) {
            $ext = pathinfo($_FILES['apk_file']['name'], PATHINFO_EXTENSION);
            if (strtolower($ext) === "apk") {
                $apk_name = "app_" . $version . "_" . time() . ".apk";
                $apk_path = $apk_dir . $apk_name;
                move_uploaded_file($_FILES['apk_file']['tmp_name'], $apk_path);
                $download_url = "http://" . $_SERVER['HTTP_HOST'] . "/apk/" . $apk_name;
            }
        }
        if (empty($download_url) && !empty($_POST['download_url'])) {
            $download_url = trim($_POST['download_url']);
        }
        if (empty($download_url)) {
            $download_url = "无下载地址，请重新上传";
        }
        $content = "版本号: {$version}\n下载地址: {$download_url}\n更新时间: " . date("Y-m-d H:i:s");
        file_put_contents($android_file, $content);

    } elseif ($platform === 'ios') {
        $download_url = trim($_POST['download_url']);
        $content = "版本号: {$version}\n下载地址: {$download_url}\n更新时间: " . date("Y-m-d H:i:s");
        file_put_contents($ios_file, $content);
    }

    // 如果是表单直传，刷新页面
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// =====================
// 读取现有数据
// =====================
$android_content = file_exists($android_file) ? nl2br(file_get_contents($android_file)) : "暂无记录";
$ios_content = file_exists($ios_file) ? nl2br(file_get_contents($ios_file)) : "暂无记录";
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>APP 版本管理</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f7f7f7; }
        h1 { color: #333; }
        form { background: white; padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        input, select { margin: 5px 0; padding: 8px; width: 100%; }
        button { padding: 10px 20px; margin-top: 10px; }
        .card { background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        pre { background: #eee; padding: 10px; border-radius: 5px; }
        #progress-container { display: none; margin-top: 10px; }
        #progress-bar { height: 20px; background: green; width: 0%; color: white; text-align: center; }
    </style>
</head>
<body>
<h1>APP 版本管理 (含 APK 上传进度)</h1>

<form id="uploadForm" method="post" enctype="multipart/form-data">
    <label>平台：</label>
    <select name="platform" id="platform" required onchange="toggleUpload()">
        <option value="android">Android</option>
        <option value="ios">iOS</option>
    </select>

    <label>版本号：</label>
    <input type="text" name="version" placeholder="例如 1.0.1" required>

    <div id="android-upload">
        <label>上传 APK 文件：</label>
        <input type="file" name="apk_file" id="apk_file" accept=".apk">
        <p style="color: gray; font-size: 14px;">（可选：如果不上传 APK，可以填写下载链接）</p>
        <label>下载地址：</label>
        <input type="text" name="download_url" placeholder="http://yourdomain.com/AppManager/apk/app.apk">
    </div>

    <div id="ios-upload" style="display:none;">
        <label>iOS 下载地址：</label>
        <input type="text" name="download_url" placeholder="TestFlight 或企业签名链接">
    </div>

    <button type="submit">更新</button>

    <div id="progress-container">
        <div id="progress-bar">0%</div>
    </div>
</form>

<div class="card">
    <h2>Android 最新版本</h2>
    <pre><?= $android_content ?></pre>
</div>

<div class="card">
    <h2>iOS 最新版本</h2>
    <pre><?= $ios_content ?></pre>
</div>

<script>
    function toggleUpload() {
        let platform = document.getElementById("platform").value;
        document.getElementById("android-upload").style.display = (platform === "android") ? "block" : "none";
        document.getElementById("ios-upload").style.display = (platform === "ios") ? "block" : "none";
    }
    toggleUpload();

    // 上传进度
    const form = document.getElementById('uploadForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(form);

        let xhr = new XMLHttpRequest();
        xhr.open("POST", form.action || window.location.href, true);

        // 监听进度
        xhr.upload.onprogress = function(event) {
            if (event.lengthComputable) {
                let percent = Math.round((event.loaded / event.total) * 100);
                let bar = document.getElementById('progress-bar');
                document.getElementById('progress-container').style.display = 'block';
                bar.style.width = percent + '%';
                bar.textContent = percent + '%';
            }
        };

        xhr.onload = function() {
            if (xhr.status === 200) {
                alert("上传完成！");
                window.location.reload();
            } else {
                alert("上传失败，请重试。");
            }
        };

        xhr.send(formData);
    });
</script>
</body>
</html>
