<?php
// =====================
// 多渠道APP版本管理系统
// =====================

$base_dir = __DIR__;
$channels_config_file = $base_dir . "/channels.json";
$apk_dir = $base_dir . "/apk/";
$ios_file = $base_dir . "/ios_version.txt"; // iOS保持原有逻辑

// 确保目录存在
if (!file_exists($apk_dir)) {
    mkdir($apk_dir, 0777, true);
}

// =====================
// 渠道配置管理
// =====================
function loadChannels() {
    global $channels_config_file;
    if (file_exists($channels_config_file)) {
        return json_decode(file_get_contents($channels_config_file), true) ?: [];
    }
    return [];
}

function saveChannels($channels) {
    global $channels_config_file;
    file_put_contents($channels_config_file, json_encode($channels, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function getChannelDir($channel_code) {
    global $apk_dir;
    return $apk_dir . $channel_code . "/";
}

function ensureChannelDir($channel_code) {
    $dir = getChannelDir($channel_code);
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir;
}

// =====================
// 处理AJAX请求
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $action = $_POST['action'] ?? '';
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'add_channel':
            $channel_code = trim($_POST['channel_code']);
            $channel_name = trim($_POST['channel_name']);
            
            if (empty($channel_code) || empty($channel_name)) {
                echo json_encode(['success' => false, 'message' => '渠道代码和名称不能为空']);
                exit;
            }
            
            $channels = loadChannels();
            if (isset($channels[$channel_code])) {
                echo json_encode(['success' => false, 'message' => '渠道代码已存在']);
                exit;
            }
            
            $channels[$channel_code] = [
                'name' => $channel_name,
                'created_at' => date('Y-m-d H:i:s'),
                'version' => '0.0.0',
                'download_url' => '',
                'update_time' => ''
            ];
            
            saveChannels($channels);
            ensureChannelDir($channel_code);
            
            echo json_encode(['success' => true, 'message' => '渠道添加成功']);
            exit;
            
        case 'delete_channel':
            $channel_code = $_POST['channel_code'];
            $channels = loadChannels();
            
            if (!isset($channels[$channel_code])) {
                echo json_encode(['success' => false, 'message' => '渠道不存在']);
                exit;
            }
            
            // 删除渠道目录和文件
            $channel_dir = getChannelDir($channel_code);
            if (file_exists($channel_dir)) {
                $files = glob($channel_dir . '*');
                foreach ($files as $file) {
                    if (is_file($file)) unlink($file);
                }
                rmdir($channel_dir);
            }
            
            unset($channels[$channel_code]);
            saveChannels($channels);
            
            echo json_encode(['success' => true, 'message' => '渠道删除成功']);
            exit;
            
        case 'get_channels':
            echo json_encode(['success' => true, 'data' => loadChannels()]);
            exit;
    }
}

// =====================
// 处理文件上传
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $platform = $_POST['platform'];
    $version = trim($_POST['version']);
    $channel_code = $_POST['channel_code'] ?? '';
    
    if ($platform === 'android') {
        if (empty($channel_code)) {
            $error = "请选择渠道";
        } else {
            $channels = loadChannels();
            if (!isset($channels[$channel_code])) {
                $error = "渠道不存在";
            } else {
                $channel_dir = ensureChannelDir($channel_code);
                $download_url = "";
                
                // 处理APK文件上传
                if (!empty($_FILES['apk_file']['name'])) {
                    $ext = pathinfo($_FILES['apk_file']['name'], PATHINFO_EXTENSION);
                    if (strtolower($ext) === "apk") {
                        // 删除旧的APK文件
                        $old_apks = glob($channel_dir . "*.apk");
                        foreach ($old_apks as $old_apk) {
                            unlink($old_apk);
                        }
                        
                        $apk_name = "app_" . $version . "_" . $channel_code . "_" . time() . ".apk";
                        $apk_path = $channel_dir . $apk_name;
                        move_uploaded_file($_FILES['apk_file']['tmp_name'], $apk_path);
                        $download_url = "https://" . $_SERVER['HTTP_HOST'] . "/apk/" . $channel_code . "/" . $apk_name;
                    }
                }
                
                // 如果没有上传文件，使用输入的URL
                if (empty($download_url) && !empty($_POST['download_url'])) {
                    $download_url = trim($_POST['download_url']);
                }
                
                if (empty($download_url)) {
                    $download_url = "无下载地址，请重新上传";
                }
                
                // 更新渠道信息
                $channels[$channel_code]['version'] = $version;
                $channels[$channel_code]['download_url'] = $download_url;
                $channels[$channel_code]['update_time'] = date("Y-m-d H:i:s");
                
                saveChannels($channels);
                
                // 保存到渠道专用的版本文件
                $version_content = "版本号: {$version}\n下载地址: {$download_url}\n更新时间: " . date("Y-m-d H:i:s");
                file_put_contents($channel_dir . "version.txt", $version_content);
                
                $success = "渠道 {$channels[$channel_code]['name']} 版本更新成功";
            }
        }
    } elseif ($platform === 'ios') {
        // iOS保持原有逻辑
        $download_url = trim($_POST['download_url']);
        $content = "版本号: {$version}\n下载地址: {$download_url}\n更新时间: " . date("Y-m-d H:i:s");
        file_put_contents($ios_file, $content);
        $success = "iOS版本更新成功";
    }
    
    // 重定向避免重复提交
    $params = [];
    if (isset($success)) $params['success'] = urlencode($success);
    if (isset($error)) $params['error'] = urlencode($error);
    
    $query = $params ? '?' . http_build_query($params) : '';
    header("Location: " . $_SERVER['PHP_SELF'] . $query);
    exit;
}

// =====================
// 读取数据
// =====================
$channels = loadChannels();
$ios_content = file_exists($ios_file) ? nl2br(file_get_contents($ios_file)) : "暂无记录";

// 处理消息显示
$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>多渠道APP版本管理</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f7f7f7; }
        h1, h2 { color: #333; }
        .container { max-width: 1200px; margin: 0 auto; }
        .form-section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .channels-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; margin-top: 20px; }
        .channel-card { background: white; padding: 15px; border-radius: 8px; border: 1px solid #ddd; }
        .channel-header { display: flex; justify-content: between; align-items: center; margin-bottom: 10px; }
        .channel-info { font-size: 14px; color: #666; }
        .btn { padding: 8px 16px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .progress-container { display: none; margin-top: 10px; }
        .progress-bar { height: 20px; background: #28a745; width: 0%; color: white; text-align: center; border-radius: 4px; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 400px; border-radius: 8px; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: black; }
    </style>
</head>
<body>
<div class="container">
    <h1>多渠道APP版本管理系统</h1>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>
    
    <!-- 渠道管理 -->
    <div class="form-section">
        <h2>渠道管理</h2>
        <button class="btn btn-primary" onclick="showAddChannelModal()">添加新渠道</button>
        
        <div class="channels-grid">
            <?php foreach ($channels as $code => $info): ?>
                <div class="channel-card">
                    <div class="channel-header">
                        <h3><?= htmlspecialchars($info['name']) ?></h3>
                        <button class="btn btn-danger" onclick="deleteChannel('<?= $code ?>')">删除</button>
                    </div>
                    <div class="channel-info">
                        <p><strong>渠道代码:</strong> <?= htmlspecialchars($code) ?></p>
                        <p><strong>当前版本:</strong> <?= htmlspecialchars($info['version']) ?></p>
                        <p><strong>更新时间:</strong> <?= htmlspecialchars($info['update_time'] ?: '未更新') ?></p>
                        <?php if ($info['download_url']): ?>
                            <p><strong>下载地址:</strong> <a href="<?= htmlspecialchars($info['download_url']) ?>" target="_blank">查看</a></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($channels)): ?>
                <p>暂无渠道，请先添加渠道</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 版本上传 -->
    <div class="form-section">
        <h2>版本上传</h2>
        <form id="uploadForm" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>平台：</label>
                <select name="platform" id="platform" required onchange="toggleUpload()">
                    <option value="android">Android</option>
                    <option value="ios">iOS</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>版本号：</label>
                <input type="text" name="version" placeholder="例如 1.0.1" required>
            </div>
            
            <div id="android-upload">
                <div class="form-group">
                    <label>选择渠道：</label>
                    <select name="channel_code" id="channel_code">
                        <option value="">请选择渠道</option>
                        <?php foreach ($channels as $code => $info): ?>
                            <option value="<?= $code ?>"><?= htmlspecialchars($info['name']) ?> (<?= $code ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>上传 APK 文件：</label>
                    <input type="file" name="apk_file" accept=".apk">
                    <small style="color: gray;">（可选：如果不上传 APK，可以填写下载链接）</small>
                </div>
                
                <div class="form-group">
                    <label>下载地址：</label>
                    <input type="text" name="download_url" placeholder="http://yourdomain.com/apk/channel/app.apk">
                </div>
            </div>
            
            <div id="ios-upload" style="display:none;">
                <div class="form-group">
                    <label>iOS 下载地址：</label>
                    <input type="text" name="download_url" placeholder="TestFlight 或企业签名链接">
                </div>
            </div>
            
            <button type="submit" class="btn btn-success">上传更新</button>
            
            <div class="progress-container">
                <div class="progress-bar">0%</div>
            </div>
        </form>
    </div>
    
    <!-- iOS版本信息 -->
    <div class="form-section">
        <h2>iOS 最新版本</h2>
        <pre><?= $ios_content ?></pre>
    </div>
</div>

<!-- 添加渠道模态框 -->
<div id="addChannelModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeAddChannelModal()">&times;</span>
        <h2>添加新渠道</h2>
        <form id="addChannelForm">
            <div class="form-group">
                <label>渠道代码：</label>
                <input type="text" id="new_channel_code" placeholder="例如: google_play" required>
                <small>只能包含字母、数字和下划线</small>
            </div>
            <div class="form-group">
                <label>渠道名称：</label>
                <input type="text" id="new_channel_name" placeholder="例如: Google Play" required>
            </div>
            <button type="submit" class="btn btn-primary">添加渠道</button>
        </form>
    </div>
</div>

<script>
function toggleUpload() {
    let platform = document.getElementById("platform").value;
    document.getElementById("android-upload").style.display = (platform === "android") ? "block" : "none";
    document.getElementById("ios-upload").style.display = (platform === "ios") ? "block" : "none";
}

function showAddChannelModal() {
    document.getElementById("addChannelModal").style.display = "block";
}

function closeAddChannelModal() {
    document.getElementById("addChannelModal").style.display = "none";
}

// 添加渠道
document.getElementById('addChannelForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    let formData = new FormData();
    formData.append('action', 'add_channel');
    formData.append('channel_code', document.getElementById('new_channel_code').value);
    formData.append('channel_name', document.getElementById('new_channel_name').value);
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert(data.message);
        }
    });
});

// 删除渠道
function deleteChannel(channelCode) {
    if (confirm('确定要删除渠道 ' + channelCode + ' 吗？这将删除该渠道的所有文件！')) {
        let formData = new FormData();
        formData.append('action', 'delete_channel');
        formData.append('channel_code', channelCode);
        
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message);
            }
        });
    }
}

// 文件上传进度
const form = document.getElementById('uploadForm');
form.addEventListener('submit', function(e) {
    e.preventDefault();
    let formData = new FormData(form);
    
    let xhr = new XMLHttpRequest();
    xhr.open("POST", form.action || window.location.href, true);
    
    xhr.upload.onprogress = function(event) {
        if (event.lengthComputable) {
            let percent = Math.round((event.loaded / event.total) * 100);
            let bar = document.querySelector('.progress-bar');
            document.querySelector('.progress-container').style.display = 'block';
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

toggleUpload();
</script>
</body>
</html>