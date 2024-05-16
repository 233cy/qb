<?php
// 基础配置
$status = [
    'downloading' => '正在下载-传输数据',
    'stalledDL' => '正在下载-等待',
    'uploading' => '正在上传-传输数据',
    'stalledUP' => '正在上传-未建立连接',
    'error' => '暂停-发生错误',
    'pausedDL' => '暂停-下载未完成',
    'pausedUP' => '暂停-下载完成',
    'missingFiles' => '暂停-文件丢失',
    'checkingDL' => '检查中-下载未完成',
    'checkingUP' => '检查中-下载完成',
    'checkingResumeData' => '检查中-启动时恢复数据',
    'forcedDL' => '强制下载-忽略队列',
    'queuedDL' => '等待下载-排队',
    'forcedUP' => '强制上传-忽略队列',
    'queuedUP' => '等待上传-排队',
    'allocating' => '分配磁盘空间',
    'metaDL' => '获取元数据',
    'moving' => '移动文件',
    'unknown' => '未知状态',
];
// 修改成你qb的ip和端口就行，url不需要动
$qbUrl = 'http://127.0.0.1:8085/api/v2';
// qb的账号
$qbUser = 'admin';
// qb的密码
$qbPwd = 'xiaogui1';
// 你要监测的目录，（会自动和qb任务列表对比）
$qbDownDir = '/volume2/video/download/MP刷流';
// 监测空文件夹的大小。一般小于600kb的都是一些空文件夹。这里单位是kb可以自己调整。
$minSize = 600;

// 删除目录ajax操作
$delDir = '';
if(isset($_POST['dir']) && !empty($_POST['dir'])){
    function deleteDirectory($dirPath) {
        if (!is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (is_dir($dirPath) && !is_link($dirPath)) {
            $items = scandir($dirPath);
            foreach ($items as $item) {
                if ($item != "." && $item != "..") {
                    $path = $dirPath . DIRECTORY_SEPARATOR . $item;
                    if (is_dir($path) && !is_link($path)) {
                        deleteDirectory($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            return rmdir($dirPath);
        }
    }
    $delDir = base64_decode($_POST['dir']);
    try{
        if($_POST['isDir'] == 1){
            if(deleteDirectory($delDir)){
                echo '成功删除文件夹';
            }
        }else{
            if(unlink($delDir)){
                echo '成功删除文件';
            }
        }
        exit;
    } catch(\Throwable $th){
        throw $th;
        exit;
    }
}
// ajax END

function getDirCount($directory){
	if(!is_dir($directory)){
		return 0;
	}
    $files = scandir($directory);
    $fileCount = 0;
     
    // 移除当前目录(.)和上级目录(..)的计数
    foreach ($files as $file) {
        if (!in_array($file, ['.', '..', '@eaDir'])) {
            $fileCount++;
        }
    }
    return $fileCount;
}
function getInt($by) {
    $unit = [0 => 'bytes', 1 => 'MB', 2 => 'GB',3 => 'T',4 => 'MAX',5 => 'MAX'];
    if(empty($by)){
        return [0, $unit[0]];
    }
    if($by > 0 && $by < 1024){
        return [number_format($by, 2), $unit[0]];
    }
    for($i=0; $i<=5;$i++){
        $by = $by / 1024;
        if($by > 0 && $by < 1024){
            return [number_format($by, 2), $unit[$i]];
        }
    }
}
function getDirName($path){
    global $qbDownDir;
    $res = str_replace($qbDownDir.'/', '', $path);
    $dir = strstr($res, '/', true);
    if( empty($dir) ){
        return $res;
    }
    return $dir;
}
function loginQb(){
    global $qbUrl;
    global $qbUser;
    global $qbPwd;
    $curl = curl_init($qbUrl . '/auth/login');
    // 设置 cURL 选项
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
        'username' => $qbUser,
        'password' => $qbPwd
    ]));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_COOKIEJAR, __DIR__.'/cookie.txt'); // 保存cookie到文件

    // 执行 cURL 请求
    $response = curl_exec($curl);
    // 关闭 cURL
    curl_close($curl);
    
    if($response == 'ok'){
        echo '登录成功刷新查看';
    }else{
        echo '登录失败';
        exit;
    }
}
function getData(){
    global $qbUrl;
    $curl = curl_init($qbUrl . '/sync/maindata');
    // 设置 cURL 选项
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_COOKIEFILE, __DIR__.'/cookie.txt');

    // 执行 cURL 请求
    $res = curl_exec($curl);
    // 关闭 cURL
    curl_close($curl);
    if($res === 'Forbidden'){
        loginQb();
    }
    return json_decode($res, true);
}
$data = getData()['torrents'];

$qbDirFileIn = [];
$dataSize = [];
$taskList = [];
foreach($data as $key => $val){
    if( $val['save_path'] === $qbDownDir ){
        $basename = getDirName($val['content_path']);
        $qbDirFileIn[] = $basename;
        $taskList[] = [
            'name' => $val['name'],
            'dir' => $basename,
            'content_path' => $val['content_path'],
            'state' => $val['state'],
            'state_' => $status[$val['state']],
        ];
        if(isset($dataSize[$basename])){
            $dataSize[$basename] = [
                'size' => $val['size'] + $dataSize[$basename]['size'],
                'state' => $dataSize[$basename]['state'] . ',' . $val['state'],
                'total_size' => (isset($val['total_size']) ? $val['total_size'] : 0) + $dataSize[$basename]['total_size'],
                'num' => $dataSize[$basename]['num']+1,
                'state_' => $dataSize[$basename]['state_'] . '、' . $status[$val['state']],
                'name' => $dataSize[$basename]['name'] . '、' . $val['name'] . "：{$status[$val['state']]}",
            ];
        }else{
            $dataSize[$basename] = [
                'size' => $val['size'],
                'state' => $val['state'],
                'total_size' => isset($val['total_size']) ? $val['total_size'] : 0,
                'num' => 1,
                'state_' => $status[$val['state']],
                'name' => $val['name'] . "：{$status[$val['state']]}",
            ];
        }
    }
}

$files_and_dirs = scandir($qbDownDir); // 列出当前目录下的所有文件和目录

$dirList = [];
$emptyDir = [];
$qbEmpty = [];
$errQb = [];
$isTask = [];

foreach ($files_and_dirs as $entry) {
    if (!in_array($entry, ['.', '..', '@eaDir'])) { // 排除当前目录和上级目录的引用
        // 检测空目录
        $dir = $qbDownDir . "/{$entry}";
        $entry_ = escapeshellarg($entry);
        $dir_ = $qbDownDir . "/{$entry_}";
        $dirList[] = $entry;
        $isDir = is_dir($qbDownDir . "/{$entry}") ? 1: 2;
        
        $size = exec("du -sk {$dir_} | awk '{print $1}'");
        if($size < $minSize){
            $err = '该文件夹没有对应任务。';
            if(in_array($entry, $qbDirFileIn)){
                $err = '该文件夹有对应任务。请自行甄别。';
            }
            $status = (is_dir($qbDownDir . "/{$entry}") ? "该目录有".getDirCount($qbDownDir . "/{$entry}")."个文件" : '这是个文件') . '文件大小：' . $size . 'kb';
            $emptyDir[] = ['val' => $dir, 'dir' => $dir, 'isDir' => $isDir, 'val1' =>  $dir_, 'err' => $err . $status];
        }
        
        $zijieSzie = exec("du -sb {$dir_} | awk '{print $1}'");
        empty($zijieSzie) ? $zijieSzie = 0: '';

        // 文件夹没有对应qb任务。。
        if(!in_array($entry, $qbDirFileIn)){
            $qbEmpty[] = ['val' => $dir, 'dir' => $dir, 'isDir' => $isDir, 'val1' => $dir_, 'err' => '输出的是源文件'];
        }else if($zijieSzie != $dataSize[$entry]['total_size'] && !strstr($dataSize[$entry]['state'], 'downloading')){
            // 当源文件和任务大小不一致的时候。
            $sourceSize = getInt($zijieSzie * $dataSize[$entry]['num']);
            $sourceSize_ = getInt($zijieSzie);
            $taskSize = getInt($dataSize[$entry]['total_size']);
            if($sourceSize[1] == $taskSize[1] && $taskSize[1] == 'MB'){
                $tmp1 = strstr($sourceSize[1], '.', true);
                $tmp2 = strstr($taskSize[1], '.', true);
                if($tmp1 == $tmp2){
                    continue;
                }
            }
            // 当多个任务指向一个文件夹，但内容不一样的时候。大小一致可以跳过。基本是因为一个剧，每集都下载到同一个文件夹下
            if($dataSize[$entry]['num'] > 1 && $sourceSize_[0] == $taskSize[0] && $sourceSize_[1] == $taskSize[1]){
                continue;
            }
            if( $sourceSize[0] != $taskSize[0] || $sourceSize[1] != $taskSize[1] ){
                $errQb[] = [
                    'val' => $dir_,
                    'val1' => $dataSize[$entry]['name'],
                    'isDir' => $isDir, 
                    'err' => "任务数量：{$dataSize[$entry]['num']}个，文件大小：{$sourceSize_[0]}{$sourceSize_[1]}、任务总大小：{$taskSize[0]}{$taskSize[1]}，"
                ];
            }
        }
    }
}

// 检测任务存在但是文件已经不存在了。
foreach($taskList as  $item){
    if(!in_array($item['dir'], $dirList)){
        $isTask[] = [
            'val' => $item['name'],
            'val1' => $item['content_path'],
            'err' => '状态：' . $item['state_'],
            'color' => 'red'
        ];
    }
}

function dds($title, $arr, $cmd = ''){
    if(!empty($arr)){
        $len = count($arr);
        echo "<p style='font-size: 22px;font-weight: 900;color: blue;'>{$title} - {$len}个</p>";
        echo "<table>";
        $dir = [];
        foreach($arr as $item){
            $color = isset($item['color']) ? $item['color'] : '#000';
            !isset($item['val1']) ? $item['val1'] = '' : '';
            echo "<tr style='color: {$color};'>";
            echo "<td>{$item['val']}</td><td>{$item['val1']}</td><td>{$item['err']}</td>";
            if(isset($item['dir']) && is_dir($item['dir'])){
                echo "<td> <button class='but' type='button' onclick=\"del(this, '".base64_encode($item['dir'])."', ".$item['isDir'].")\">点我删除</button> </td>";
            }
            echo "</tr>";
            $dir[] = $item['val'];
        }
        echo "</table>";
        echo '<br /><br /><br />';
    }
}
dds('检测小于'.$minSize.'KB的文件夹', $emptyDir);
dds('文件夹没有对应qb任务', $qbEmpty);
dds('文件夹和任务大小不一致。（列1文件夹路径，列2任务名，列3描述）', $errQb);
dds('qb任务对应文件消失。（列1任务名，列2任务存放位置，列三描述）', $isTask);
?>

<script type="text/javascript" src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-1-M/jquery/1.7.2/jquery.min.js"></script>
<script type="text/javascript">
function del(use, code, isDir){
    $('.but').attr("disabled", true);
    $.post(window.location.href, {dir:code, isDir:isDir}, function(res){
        $('.but').attr("disabled", false);
        use.disabled = true
        use.style.color='red';
        use.innerText = res;
    });
}
</script>
