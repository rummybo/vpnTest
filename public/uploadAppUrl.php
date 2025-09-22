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
// 处理提交
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $platform = $_POST['platform'];
    $version = trim($_POST['version']);
    $download_url = "";

    if ($platform === 'android') {
        // -------- APK 上传 --------
        if (!empty($_FILES['apk_file']['name'])) {
            $ext = pathinfo($_FILES['apk_file']['name'], PATHINFO_EXTENSION);
            if (strtolower($ext) === "apk") {
                $apk_name = "app_" . $version . "_" . time() . ".apk";
                $apk_path = $apk_dir . $apk_name;
                move_uploaded_file($_FILES['apk_file']['tmp_name'], $apk_path);

                // 生成下载链接（注意路径）
                $download_url = "http://" . $_SERVER['HTTP_HOST'] . "/AppManager/apk/" . $apk_name;
            }
        }

        // -------- 如果没上传文件，检查输入框 --------
        if (empty($download_url) && !empty($_POST['download_url'])) {
            $download_url = trim($_POST['download_url']);
        }

        // -------- 兜底 --------
        if (empty($download_url)) {
            $download_url = "无下载地址，请重新上传";
        }

        // 写入文件
        $content = "版本号: {$version}\n下载地址: {$download_url}\n更新时间: " . date("Y-m-d H:i:s");
        file_put_contents($android_file, $content);

    } elseif ($platform === 'ios') {
        // -------- iOS 链接 --------
        $download_url = trim($_POST['download_url']);
        $content = "版本号: {$version}\n下载地址: {$download_url}\n更新时间: " . date("Y-m-d H:i:s");
        file_put_contents($ios_file, $content);
    }
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
    </style>
</head>
<body>
<h1>APP 版本管理 (TXT 记录 + APK 上传)</h1>
<form method="post" enctype="multipart/form-data">
    <label>平台：</label>
    <select name="platform" id="platform" required onchange="toggleUpload()">
        <option value="android">Android</option>
        <option value="ios">iOS</option>
    </select>

    <label>版本号：</label>
    <input type="text" name="version" placeholder="例如 1.0.1" required>

    <div id="android-upload">
        <label>上传 APK 文件：</label>
        <input type="file" name="apk_file" accept=".apk">
        <p style="color: gray; font-size: 14px;">（可选：如果不上传 APK，可以填写下载链接）</p>
        <label>下载地址：</label>
        <input type="text" name="download_url" placeholder="http://yourdomain.com/AppManager/apk/app.apk">
    </div>

    <div id="ios-upload" style="display:none;">
        <label>iOS 下载地址：</label>
        <input type="text" name="download_url" placeholder="TestFlight 或企业签名链接">
    </div>

    <button type="submit">更新</button>
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
</script>
</body>
</html>
