<?php
// ── Video player and server-side video lookup ────────────────────
// AES key NEVER touches the browser.
require_once 'config.php';
if(session_status()===PHP_SESSION_NONE) session_start();
auto_login();
send_security_headers();

$mediaUrl = $_GET['url'] ?? '';
if(!is_string($mediaUrl)){
    http_response_code(400);
    die('Invalid video URL');
}
$mediaUrl = trim($mediaUrl);
if($mediaUrl !== ''){
    $mediaParts = parse_url($mediaUrl);
    if(!$mediaParts || strtolower($mediaParts['scheme'] ?? '') !== 'https' || empty($mediaParts['host'])){
        http_response_code(400);
        die('Invalid video URL');
    }

    $sourceJson = json_encode($mediaUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    if($sourceJson === false){
        http_response_code(400);
        die('Invalid video URL');
    }

    $isHls = strtolower(pathinfo($mediaParts['path'] ?? '', PATHINFO_EXTENSION)) === 'm3u8';
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="referrer" content="no-referrer">
<title>Video Player</title>
<style>
*{box-sizing:border-box}
html,body{width:100%;height:100%;margin:0;background:#08080d;color:#f1f5f9;font-family:system-ui,sans-serif}
body{display:grid;place-items:center}
main{width:min(100%,1000px);padding:16px}
video{display:block;width:100%;max-height:calc(100dvh - 32px);background:#000;border-radius:12px}
#error{color:#fca5a5;text-align:center;padding:12px}
</style>
</head>
<body>
<main>
<video id="video" controls playsinline preload="metadata"></video>
<p id="error" hidden></p>
</main>
<?php if($isHls): ?>
<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.17/dist/hls.min.js"></script>
<?php endif; ?>
<script>
const source=<?=$sourceJson?>;
const video=document.getElementById('video');
const error=document.getElementById('error');
function showError(message){error.textContent=message;error.hidden=false;}
video.addEventListener('error',()=>showError('Unable to load this video. Check your connection and try again.'));
<?php if($isHls): ?>
if(video.canPlayType('application/vnd.apple.mpegurl')){
  video.src=source;
}else if(typeof Hls!=='undefined'&&Hls.isSupported()){
  const hls=new Hls();
  hls.loadSource(source);
  hls.attachMedia(video);
  hls.on(Hls.Events.ERROR,(_,data)=>{
    if(data.fatal){
      hls.destroy();
      showError('Unable to load this video. Check your connection and try again.');
    }
  });
}else{
  showError('HLS playback is not supported by this browser.');
}
<?php else: ?>
video.src=source;
<?php endif; ?>
</script>
</body>
</html>
    <?php
    exit;
}

$vid = intval($_GET['vid'] ?? 0);
$bid = intval($_GET['bid'] ?? 0);
$q   = preg_replace('/[^a-z0-9]/', '', $_GET['q'] ?? 'auto');

if(!$vid || !$bid){ http_response_code(400); die('Bad request'); }

$token  = $_SESSION['token'];
$userid = $_SESSION['userid'];

$url = API_BASE.'/get/fetchVideoDetailsById?course_id='.$bid.'&video_id='.$vid.'&ytflag=0&folder_wise_course=0&userid='.urlencode($userid);

$ch=curl_init($url);
curl_setopt_array($ch,[
    CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20,CURLOPT_CONNECTTIMEOUT=>8,
    CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_ENCODING=>'',
    CURLOPT_HTTPHEADER=>[
        'Client-Service: Appx','Auth-Key: appxapi',
        'Authorization: '.$token,'User-ID: '.$userid,
        'User-Agent: Mozilla/5.0 (Linux; Android 12) AppleWebKit/537.36',
    ],
]);
$resp=curl_exec($ch);curl_close($ch);

$data=json_decode($resp,true);
$d=$data['data']??null;
if(!$d){
    if(preg_match('/\{"status":[\s\S]*?\}/U',$resp??'',$m)){
        $temp=json_decode($m[0],true);$d=$temp['data']??null;
    }
}
if(!$d){ http_response_code(404); die('Video not found'); }

$player='/player?url=';

// YouTube
if(!empty($d['video_id'])&&empty($d['download_link'])){
    $yt=strlen($d['video_id'])>20?decrypt_appx($d['video_id']):$d['video_id'];
    header('Location: https://www.youtube.com/watch?v='.urlencode($yt));exit;
}
// Direct download link
if(!empty($d['download_link'])){
    $u=decrypt_appx($d['download_link']);
    if($q!=='auto') $u=preg_replace('/(1080p|720p|480p|360p|240p)/',$q,$u);
    header('Location: '.$player.urlencode($u));exit;
}
// Encrypted links array
$lnks=$d['encrypted_links']??[];
foreach($lnks as $lnk){
    if(!empty($lnk['path'])){
        $u=decrypt_appx($lnk['path']);
        if($q!=='auto') $u=preg_replace('/(1080p|720p|480p|360p|240p)/',$q,$u);
        if($u){header('Location: '.$player.urlencode($u));exit;}
    }
}
http_response_code(404); die('No playable source');
