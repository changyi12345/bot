<?php
error_reporting(0);
date_default_timezone_set("Asia/Yangon");
session_start();

/* ===== CONFIG ===== */
define("ADMIN_KEY","123456");      // ✅ change!
define("BOT_TOKEN","7279743667:AAHbJKx12tWP5nY_zWF6TG-6fdl1NHCOls8");//BOT Token

define("TOPUP","@Kage_Ran");

define("AFILE",__DIR__."/data/admin.json");
define("UFILE",__DIR__."/data/users.json");
define("HFILE",__DIR__."/data/history.json");
define("TFILE",__DIR__."/data/topups.json");    // pending topups

define("ML_FILE",__DIR__."/prices.json");
define("PUBG_UC_FILE",__DIR__."/pubg_prices.json");
define("PUBG_CODE_FILE",__DIR__."/pubg_code_prices.json");

define("BOT_FILE",__DIR__."/bot.php");          // ✅ admin will replace this
define("BACKUP_DIR",__DIR__."/backup");         // ✅ backups here

define("MAX_IMG_MB",10);
define("MAX_PHP_MB",2);                         // bot.php max upload size
define("BC_DELAY_MS",80);

/* ===== BASIC ===== */
function h($s){ return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8"); }
function jr($f){
  if(!file_exists($f)) return [];
  $j=@json_decode(@file_get_contents($f),true);
  return is_array($j)?$j:[];
}
function jw($f,$d){
  @mkdir(dirname($f),0777,true);
  $tmp=$f.".tmp";
  @file_put_contents($tmp,json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),LOCK_EX);
  @rename($tmp,$f);
}
function admin_cfg(){
  $j=jr(AFILE);
  return is_array($j)?$j:[];
}
function admin_cfg_save($cfg){
  if(!is_array($cfg)) $cfg=[];
  jw(AFILE,$cfg);
}
function admin_sig($cfg){
  if(is_array($cfg) && !empty($cfg["pass_hash"])) return sha1("adm:".$cfg["pass_hash"]);
  return sha1("adm:".ADMIN_KEY);
}
function admin_avatar_src($cfg){
  if(!is_array($cfg)) return "";
  $a=trim((string)($cfg["avatar"]??""));
  if($a!=="" && preg_match('~^https?://~i',$a)) return $a;
  if($a!=="" && preg_match('~^data/[^\\/]+\.(png|jpe?g|gif|webp)$~i',$a) && file_exists(__DIR__."/".$a)) return $a."?v=".@filemtime(__DIR__."/".$a);
  return "";
}
function read_text($f,$fallback="[]"){
  if(!file_exists($f)) return $fallback;
  $t=trim((string)@file_get_contents($f));
  return ($t==="")?$fallback:$t;
}
function write_text_atomic($f,$txt){
  @mkdir(dirname($f),0777,true);
  $tmp=$f.".tmp";
  @file_put_contents($tmp,$txt,LOCK_EX);
  @rename($tmp,$f);
  return true;
}
function auth(){
  $cfg=admin_cfg();
  $sig=admin_sig($cfg);

  if(!empty($_SESSION["admin_ok"]) && $_SESSION["admin_ok"]===$sig){
    return "ok";
  }

  $k=$_GET["key"]??$_POST["key"]??$_POST["password"]??"";
  $k=(string)$k;

  $ok=false;
  if(!empty($cfg["pass_hash"])){
    $ok=function_exists("password_verify") ? password_verify($k,(string)$cfg["pass_hash"]) : false;
  }else{
    $ok=hash_equals((string)ADMIN_KEY,$k);
  }

  if($ok){
    $_SESSION["admin_ok"]=$sig;
    $view=$_GET["view"]??"users";
    header("Location: ?view=".$view);
    exit;
  }

  // show simple login form
  header("Content-Type:text/html; charset=utf-8");
  ?>
  <!doctype html>
  <html>
  <head>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Login</title>
    <style>
      :root{
        --p:#E91E63;
        --bg:#050816;
      }
      *{box-sizing:border-box}
      body{
        margin:0;
        font-family:system-ui,-apple-system,Segoe UI,Roboto;
        min-height:100vh;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:20px;
        color:#fff;
        background:
          radial-gradient(1200px 600px at 20% -10%, rgba(233,30,99,.30), transparent 60%),
          radial-gradient(900px 500px at 110% 10%, rgba(0,200,255,.22), transparent 55%),
          var(--bg);
      }
      .shell{
        width:100%;
        max-width:420px;
      }
      .logo{
        display:flex;
        align-items:center;
        gap:10px;
        margin-bottom:12px;
      }
      .dot{
        width:14px;height:14px;border-radius:999px;
        background:var(--p);
        box-shadow:0 0 18px rgba(233,30,99,.9);
      }
      .card{
        background:linear-gradient(135deg,rgba(8,12,32,.96),rgba(12,6,24,.98));
        border-radius:22px;
        padding:22px 20px 18px;
        box-shadow:0 26px 70px rgba(0,0,0,.65);
        border:1px solid rgba(255,255,255,.12);
        backdrop-filter:blur(18px);
      }
      h3{
        margin:0 0 6px 0;
        font-size:20px;
      }
      .sub{
        font-size:13px;
        opacity:.8;
        margin-bottom:14px;
      }
      label{
        display:block;
        font-size:12px;
        opacity:.85;
        margin-bottom:5px;
      }
      input,button{
        width:100%;
        padding:11px 12px;
        border-radius:14px;
        border:1px solid rgba(255,255,255,.16);
        background:rgba(6,10,28,.96);
        color:#fff;
        outline:none;
        font-size:14px;
      }
      input::placeholder{color:rgba(255,255,255,.35);}
      input:focus{
        border-color:rgba(233,30,99,.85);
        box-shadow:0 0 0 1px rgba(233,30,99,.65);
      }
      button{
        margin-top:12px;
        background:linear-gradient(135deg,#E91E63,#ff6aa7);
        border:0;
        font-weight:700;
        cursor:pointer;
        display:flex;
        align-items:center;
        justify-content:center;
        gap:6px;
      }
      button span.icon{
        font-size:15px;
      }
      .mini{
        font-size:11px;
        opacity:.72;
        margin-top:10px;
      }
      .foot{
        margin-top:10px;
        text-align:center;
        font-size:11px;
        opacity:.55;
      }
      code{
        background:rgba(0,0,0,.35);
        padding:1px 6px;
        border-radius:8px;
      }
    </style>
  </head>
  <body>
    <div class="shell">
      <div class="logo">
        <div class="dot"></div>
        <div>
          <div style="font-size:13px;opacity:.8">Auto Top-Up Bot</div>
          <div style="font-size:15px;font-weight:700">Admin Console</div>
        </div>
      </div>
      <form method="post" class="card">
        <h3>Welcome back 👋</h3>
        <div class="sub">Admin Panel သို့ ဝင်ရောက်ရန် Password ထည့်ပါ။</div>
        <div style="margin-bottom:12px">
          <label for="pw">Admin Key / Password</label>
          <input id="pw" type="password" name="password" placeholder="••••••••••" required autofocus>
        </div>
        <button type="submit">
          <span class="icon">🔐</span>
          <span>ENTER DASHBOARD</span>
        </button>
        <div class="mini">
          Config ဖိုင်ထဲက <code>ADMIN_KEY</code> ကို မျှဝေရန် မသုံးပါနှင့်။
          Own strong password တစ်ခု သတ်မှတ်သုံးထားပါ ✅
        </div>
      </form>
      <div class="foot">
        Secured Session Login • Unauthorized access is blocked
      </div>
    </div>
  </body>
  </html>
  <?php
  exit;
}

/* ===== Telegram ===== */
function tg_api($method,$data,$multipart=false){
  $url="https://api.telegram.org/bot".BOT_TOKEN."/".$method;
  $ch=curl_init($url);
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_POST=>1,CURLOPT_POSTFIELDS=>$data,CURLOPT_TIMEOUT=>35,CURLOPT_CONNECTTIMEOUT=>10]);
  if($multipart) curl_setopt($ch,CURLOPT_HTTPHEADER,["Expect:"]);
  $res=curl_exec($ch); $err=curl_error($ch); curl_close($ch);
  if($res===false||$res==="") return ["ok"=>0,"description"=>$err?: "empty_response"];
  $j=@json_decode($res,true);
  return is_array($j)?$j:["ok"=>0,"description"=>"bad_json","raw"=>$res];
}
function tg_text($cid,$text){
  $text=trim((string)$text); if($text==="") $text=" ";
  return tg_api("sendMessage",["chat_id"=>$cid,"text"=>$text,"parse_mode"=>"HTML","disable_web_page_preview"=>true],false);
}
function tg_photo_file($cid,$filePath,$caption=""){
  $caption=trim((string)$caption);
  if(mb_strlen($caption)>1024) $caption=mb_substr($caption,0,1020)."...";
  $mime=function_exists("mime_content_type")?@mime_content_type($filePath):"image/jpeg"; if(!$mime) $mime="image/jpeg";
  $cf=new CURLFile($filePath,$mime,basename($filePath));
  return tg_api("sendPhoto",["chat_id"=>$cid,"photo"=>$cf,"caption"=>$caption,"parse_mode"=>"HTML"],true);
}
function dl_tmp($url,&$e=null){
  $e=null; $url=trim((string)$url);
  if($url===""){ $e="empty_url"; return null; }
  $tmp=tempnam(sys_get_temp_dir(),"adm_"); if(!$tmp){ $e="tmp_fail"; return null; }
  $fp=@fopen($tmp,"w"); if(!$fp){ @unlink($tmp); $e="fp_fail"; return null; }
  $ch=curl_init($url);
  curl_setopt_array($ch,[CURLOPT_FILE=>$fp,CURLOPT_FOLLOWLOCATION=>1,CURLOPT_TIMEOUT=>25,CURLOPT_CONNECTTIMEOUT=>10,
    CURLOPT_USERAGENT=>"Mozilla/5.0",CURLOPT_SSL_VERIFYPEER=>0,CURLOPT_SSL_VERIFYHOST=>0]);
  $ok=curl_exec($ch); $cerr=curl_error($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch); fclose($fp);
  $sz=@filesize($tmp);
  if(!$ok||$code<200||$code>=300||$sz<200){ @unlink($tmp); $e="download_fail code=$code err=$cerr"; return null; }
  if($sz>MAX_IMG_MB*1024*1024){ @unlink($tmp); $e="file_too_large"; return null; }
  return $tmp;
}
function tg_send_any($cid,$text,$imgUrl="",$imgFileTmp=null){
  if($imgFileTmp && file_exists($imgFileTmp)) return tg_photo_file($cid,$imgFileTmp,$text);
  $imgUrl=trim((string)$imgUrl);
  if($imgUrl!==""){
    $e=null; $tmp=dl_tmp($imgUrl,$e); if(!$tmp) return ["ok"=>0,"description"=>"img_download_failed: ".$e];
    $r=tg_photo_file($cid,$tmp,$text); @unlink($tmp); return $r;
  }
  return tg_text($cid,$text);
}
function ks($n){ return number_format((int)round((float)$n))." MMK"; }

/* ===== BOT.PHP MANAGER ===== */
function ensure_backup_dir(){ @mkdir(BACKUP_DIR,0777,true); }
function backup_file($path){
  if(!file_exists($path)) return null;
  ensure_backup_dir();
  $bn=basename($path);
  $dst=BACKUP_DIR."/".$bn.".".date("Ymd_His").".bak";
  @copy($path,$dst);
  return $dst;
}
function looks_like_php($txt){
  $t=ltrim((string)$txt);
  return (stripos($t,"<?php")!==false); // simple check
}

/* ===== INIT ===== */
$key=auth();
$acfg=admin_cfg();
$avatar=admin_avatar_src($acfg);

$users=jr(UFILE); if(!is_array($users)) $users=[];
$hist =jr(HFILE); if(!is_array($hist))  $hist=[];
$tups =jr(TFILE); if(!is_array($tups))  $tups=[];

$view=$_GET["view"]??"dashboard";
$q=trim($_GET["q"]??"");
$topup_q=trim($_GET["topup_q"]??"");
$msg=""; $err=""; $info="";

/* ===== ACTIONS ===== */

/* Topup approve / reject */
if(isset($_POST["topup_approve"]) || isset($_POST["topup_reject"])){
  $tid=$_POST["tid"]??"";
  $isApprove=isset($_POST["topup_approve"]);
  if($tid===""){
    $err="Topup ID မရှိပါ";
  }else{
    $found=false;
    foreach($tups as $i=>$tp){
      if(($tp["id"]??"")!==$tid) continue;
      $found=true;
      if(($tp["status"]??"pending")!=="pending"){
        $err="This topup already processed";
        break;
      }
      $cid=$tp["cid"]??"";
      $amt=(int)($tp["amount"]??0);
      $ref=$tp["ref"]??"-";
      if($cid==="" || $amt<=0){
        $err="Topup data invalid";
        break;
      }
      if($isApprove){
        if(!isset($users[$cid])) $users[$cid]=["bal"=>0,"join"=>date("Y-m-d H:i:s")];
        $old=(int)($users[$cid]["bal"]??0);
        $users[$cid]["bal"]=$old+$amt;
        if(empty($users[$cid]["join"])) $users[$cid]["join"]=date("Y-m-d H:i:s");
        jw(UFILE,$users);
        $tups[$i]["status"]="approved";
        $tups[$i]["approved_at"]=date("Y-m-d H:i:s");
        jw(TFILE,$tups);
        $msg="Topup Approved ✅ (CID $cid, +".ks($amt).")";
        $txt="✅ <b>Topup Success</b>\n━━━━━━━━━━━━━━\n".
             "Amount : <b>".ks($amt)."</b>\n".
             "Ref ID : <code>".h($ref)."</code>\n".
             "New Wallet : <b>".ks($users[$cid]["bal"])."</b>\n\n".
             "🙏 Thanks for your purchase!";
        tg_text($cid,$txt);
      }else{
        $tups[$i]["status"]="rejected";
        $tups[$i]["approved_at"]=date("Y-m-d H:i:s");
        jw(TFILE,$tups);
        $msg="Topup Rejected 🚫 (CID ".h($cid).")";
        if($cid){
          $txt="❌ <b>Topup မအောင်မြင်ပါ</b>\n━━━━━━━━━━━━━━\n".
               "Amount : <b>".ks($amt)."</b>\n".
               "Ref ID : <code>".h($ref)."</code>\n\n".
               "အကြောင်းရင်းစမ်းထိုးရန် Admin ".h(TOPUP)." ကို ဆက်သွယ်နိုင်ပါတယ်။";
          tg_text($cid,$txt);
        }
      }
      break;
    }
    if(!$found && !$err){
      $err="Topup record မတွေ့ပါ";
    }
  }
}

/* Logout */
if(isset($_GET["logout"])){
  $_SESSION=[];
  if(session_id()!=="") session_destroy();
  header("Location: ?");
  exit;
}

if(isset($_POST["admin_save_profile"])){
  $acfg=admin_cfg();

  $aurl=trim((string)($_POST["avatar_url"]??""));
  if($aurl!=="" && !preg_match('~^https?://~i',$aurl)) $err="Avatar URL မမှန်ပါ";

  if(!$err && !empty($_FILES["avatar_file"]["tmp_name"]) && is_uploaded_file($_FILES["avatar_file"]["tmp_name"])){
    $sz=(int)($_FILES["avatar_file"]["size"]??0);
    if($sz>MAX_IMG_MB*1024*1024) $err="Image too large (max ".MAX_IMG_MB."MB)";
    else{
      $tmp=$_FILES["avatar_file"]["tmp_name"];
      $type=function_exists("exif_imagetype")?@exif_imagetype($tmp):0;
      $ext=($type===IMAGETYPE_PNG?"png":($type===IMAGETYPE_GIF?"gif":($type===IMAGETYPE_WEBP?"webp":"jpg")));
      $dst=__DIR__."/data/admin_avatar.".$ext;
      @mkdir(__DIR__."/data",0777,true);
      if(!@move_uploaded_file($tmp,$dst)) $err="Avatar upload failed";
      else{
        $acfg["avatar"]="data/admin_avatar.".$ext;
        admin_cfg_save($acfg);
        $msg="Profile Saved ✅";
      }
    }
  }

  if(!$err && empty($_FILES["avatar_file"]["tmp_name"])){
    if($aurl!=="") $acfg["avatar"]=$aurl; else unset($acfg["avatar"]);
    admin_cfg_save($acfg);
    $msg="Profile Saved ✅";
  }

  $avatar=admin_avatar_src(admin_cfg());
}

if(isset($_POST["admin_save_noti"])){
  $acfg=admin_cfg();
  $cid=trim((string)($_POST["admin_chat_id"]??""));
  if($cid!=="" && !preg_match('/^\d{5,20}$/',$cid)) $err="Admin Chat ID မမှန်ပါ";
  else{
    if($cid!=="") $acfg["admin_chat_id"]=(int)$cid; else unset($acfg["admin_chat_id"]);
    $acfg["topup_admin_noti"]=isset($_POST["topup_admin_noti"])?1:0;
    admin_cfg_save($acfg);
    $msg="Notification Saved ✅";
  }
}

if(isset($_POST["admin_change_pw"])){
  $acfg=admin_cfg();
  $old=(string)($_POST["old_pw"]??"");
  $new=(string)($_POST["new_pw"]??"");
  $cf =(string)($_POST["new_pw2"]??"");

  $oldOk=false;
  if(!empty($acfg["pass_hash"])) $oldOk=function_exists("password_verify") ? password_verify($old,(string)$acfg["pass_hash"]) : false;
  else $oldOk=hash_equals((string)ADMIN_KEY,$old);

  if(!$oldOk) $err="Old password မမှန်ပါ";
  elseif($new==="" || mb_strlen($new)<6) $err="New password အနည်းဆုံး 6 လုံးထားပါ";
  elseif($new!==$cf) $err="Confirm password မတူပါ";
  else{
    if(!function_exists("password_hash")) $err="password_hash မရှိပါ (PHP version စစ်ပါ)";
    else{
      $acfg["pass_hash"]=password_hash($new,PASSWORD_DEFAULT);
      admin_cfg_save($acfg);
      $_SESSION["admin_ok"]=admin_sig($acfg);
      $msg="Password Changed ✅";
    }
  }
}

/* Ban / Unban user */
if(isset($_POST["toggle_ban"])){
  $cid=trim($_POST["cid"]??"");
  if($cid===""||!preg_match("/^\d+$/",$cid)) $err="CID မမှန်ပါ";
  else{
    if(!isset($users[$cid])) $users[$cid]=["bal"=>0,"join"=>date("Y-m-d H:i:s")];
    $banned=!empty($users[$cid]["ban"]);
    if($banned){
      unset($users[$cid]["ban"]);
      $msg="User Unbanned ✅ ($cid)";
    }else{
      $users[$cid]["ban"]=1;
      $msg="User Banned ✅ ($cid)";
    }
    jw(UFILE,$users);
  }
}

/* Balance + auto notify */
if(isset($_POST["add_bal"])){
  $cid=trim($_POST["cid"]??"");
  $amt=(int)($_POST["amt"]??0);
  if($cid===""||!preg_match("/^\d+$/",$cid)) $err="CID မမှန်ပါ";
  else{
    if(!isset($users[$cid])) $users[$cid]=["bal"=>0,"join"=>date("Y-m-d H:i:s")];
    $old=(int)($users[$cid]["bal"]??0);
    $new=max(0,$old+$amt);
    $users[$cid]["bal"]=$new;
    if(empty($users[$cid]["join"])) $users[$cid]["join"]=date("Y-m-d H:i:s");
    jw(UFILE,$users);

    $deltaTxt=($amt>=0?"+":"").ks(abs($amt));
    $text="💰 <b>Wallet Update</b>\n━━━━━━━━━━━━━━\n🆔 <code>$cid</code>\n➕➖ Change: <b>$deltaTxt</b>\nOld: <b>".ks($old)."</b>\nNew: <b>".ks($new)."</b>\n\n🙏 Thanks!";
    tg_text($cid,$text);

    $msg="Balance Updated ✅ ($cid)";
  }
}

/* Save prices */
if(isset($_POST["save_price"])){
  $file=$_POST["file"]??"";
  $json=trim($_POST["json"]??"");
  $arr=@json_decode($json,true);
  if(!$file) $err="File missing";
  elseif(!is_array($arr)) $err="JSON မမှန်ပါ";
  else{ jw($file,$arr); $msg="Saved ✅"; }
}

/* Notify one */
if(isset($_POST["notify_one"])){
  $cid=trim($_POST["n_cid"]??"");
  $text=trim($_POST["n_text"]??"");
  $imgUrl=trim($_POST["n_img_url"]??"");
  $imgTmp=null;

  if(!empty($_FILES["n_img_file"]["tmp_name"]) && is_uploaded_file($_FILES["n_img_file"]["tmp_name"])){
    $sz=(int)($_FILES["n_img_file"]["size"]??0);
    if($sz>MAX_IMG_MB*1024*1024) $err="Image too large (max ".MAX_IMG_MB."MB)";
    else $imgTmp=$_FILES["n_img_file"]["tmp_name"];
  }
  if(!$err){
    if($cid===""||!preg_match("/^\d+$/",$cid)) $err="CID မမှန်ပါ";
    elseif($text==="" && $imgUrl==="" && !$imgTmp) $err="Message သို့ Photo ထည့်ပါ";
    else{
      $r=tg_send_any($cid,$text,$imgUrl,$imgTmp);
      if(!empty($r["ok"])) $msg="Notify sent ✅ ($cid)";
      else $err="Notify failed ($cid): ".h($r["description"]??"send_failed");
    }
  }
}

/* Broadcast all */
$fails=[];
if(isset($_POST["broadcast_all"])){
  $text=trim($_POST["b_text"]??"");
  $imgUrl=trim($_POST["b_img_url"]??"");
  $delay=(int)($_POST["b_delay"]??BC_DELAY_MS);
  if($delay<0) $delay=0; if($delay>2000) $delay=2000;

  $imgTmp=null; $shared=null;
  if(!empty($_FILES["b_img_file"]["tmp_name"]) && is_uploaded_file($_FILES["b_img_file"]["tmp_name"])){
    $sz=(int)($_FILES["b_img_file"]["size"]??0);
    if($sz>MAX_IMG_MB*1024*1024) $err="Image too large (max ".MAX_IMG_MB."MB)";
    else $imgTmp=$_FILES["b_img_file"]["tmp_name"];
  }

  if(!$err){
    if(!$users) $err="users မရှိသေးပါ";
    elseif($text==="" && $imgUrl==="" && !$imgTmp) $err="Message သို့ Photo ထည့်ပါ";
    else{
      if(!$imgTmp && $imgUrl!==""){ $e=null; $shared=dl_tmp($imgUrl,$e); if(!$shared) $err="Image download failed: ".$e; }
      if(!$err){
        $ok=0;$fail=0;
        foreach(array_keys($users) as $cid){
          if(!preg_match("/^\d+$/",$cid)) continue;
          $useFile=$imgTmp?:$shared;
          $r=tg_send_any($cid,$text,"",$useFile);
          if(!empty($r["ok"])) $ok++;
          else{ $fail++; $fails[]=["cid"=>$cid,"desc"=>$r["description"]??"send_failed"]; }
          usleep($delay*1000);
        }
        if($shared) @unlink($shared);
        $info="Broadcast DONE ✅ | OK:$ok FAIL:$fail TOTAL:".count($users);
      }
    }
  }
}

/* ===== NEW: BOT.PHP UPDATE (Upload / Edit) ===== */
if(isset($_POST["bot_save_text"])){
  $txt=(string)($_POST["bot_text"]??"");
  if(trim($txt)==="") $err="bot.php content empty";
  elseif(!looks_like_php($txt)) $err="PHP code မဟုတ်သလိုပဲ (<?php မတွေ့ဘူး)";
  else{
    backup_file(BOT_FILE);
    write_text_atomic(BOT_FILE,$txt);
    $msg="bot.php saved ✅";
  }
}
if(isset($_POST["bot_upload_file"])){
  if(empty($_FILES["bot_file"]["tmp_name"]) || !is_uploaded_file($_FILES["bot_file"]["tmp_name"])) $err="Upload file မတွေ့ဘူး";
  else{
    $sz=(int)($_FILES["bot_file"]["size"]??0);
    if($sz > MAX_PHP_MB*1024*1024) $err="bot.php too large (max ".MAX_PHP_MB."MB)";
    else{
      $raw=@file_get_contents($_FILES["bot_file"]["tmp_name"]);
      if(!$raw || !looks_like_php($raw)) $err="Upload ဖိုင်က PHP မဟုတ်သလိုပဲ";
      else{
        backup_file(BOT_FILE);
        write_text_atomic(BOT_FILE,$raw);
        $msg="bot.php upload saved ✅";
      }
    }
  }
}

/* ===== LOAD UI TEXT ===== */
$ml = read_text(ML_FILE);
$uc = read_text(PUBG_UC_FILE);
$pc = read_text(PUBG_CODE_FILE);
$bot_now = file_exists(BOT_FILE) ? (string)file_get_contents(BOT_FILE) : "<?php\n// bot.php not found\n";

$warn=[];
if(!file_exists(ML_FILE))        $warn[]="prices.json (ML) not found";
if(!file_exists(PUBG_UC_FILE))   $warn[]="pubg_prices.json (UC) not found";
if(!file_exists(PUBG_CODE_FILE)) $warn[]="pubg_code_prices.json (CODE) not found";
if(!file_exists(BOT_FILE))       $warn[]="bot.php not found";

$tabs=[
  "dashboard"=>"📊 Dashboard",
  "users"=>"👤 Users",
  "balance"=>"💰 Balance",
  "topup"=>"💸 Topup Req",
  "topup_history"=>"📋 Topup History",
  "notify"=>"🔔 Notify",
  "broadcast"=>"📣 Broadcast",
  "prices"=>"🎮 Prices",
  "bot"=>"🧩 bot.php",
  "settings"=>"⚙️ Settings",
  "history"=>"📜 History"
];
?>
<!doctype html>
<html>
<head>
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin</title>
<style>
:root{--p:#E91E63;--bg:#0b1020;--card:rgba(255,255,255,.06);--bd:rgba(255,255,255,.12)}
*{box-sizing:border-box}
body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto;background:
radial-gradient(1200px 600px at 20% -10%, rgba(233,30,99,.25), transparent 60%),
radial-gradient(900px 500px at 110% 10%, rgba(0,200,255,.18), transparent 55%),
var(--bg); color:#fff}
a{color:#fff;text-decoration:none}
.top{position:sticky;top:0;backdrop-filter:blur(10px);background:rgba(10,14,30,.75);border-bottom:1px solid var(--bd);padding:12px;z-index:5}
.brand{display:flex;align-items:center;gap:10px;justify-content:space-between}
.dot{width:12px;height:12px;border-radius:999px;background:var(--p);box-shadow:0 0 18px rgba(233,30,99,.9)}
.mini{opacity:.8;font-size:12px}
.right-mini{font-size:12px;opacity:.8}
.wrap{max-width:980px;margin:auto;padding:14px}
.tabbar{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.tab{padding:10px 12px;border-radius:14px;background:rgba(255,255,255,.06);border:1px solid var(--bd)}
.tab.act{background:var(--p);border-color:var(--p);box-shadow:0 10px 30px rgba(233,30,99,.25)}
.card{background:var(--card);border:1px solid var(--bd);border-radius:18px;padding:14px;margin:12px 0}
h3{margin:0 0 10px 0}
input,textarea,button{
  width:100%;padding:12px;border-radius:14px;border:1px solid rgba(255,255,255,.14);
  background:rgba(10,14,30,.6);color:#fff;outline:none
}
textarea{min-height:170px;font-family:ui-monospace,Menlo,monospace;background:rgba(0,0,0,.55);color:#7CFF9A}
.row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
@media(max-width:720px){.row{grid-template-columns:1fr}}
.btn{background:linear-gradient(135deg,var(--p),#ff6aa7);border:0;font-weight:800;cursor:pointer}
.ok{background:rgba(32,200,120,.12);border:1px solid rgba(32,200,120,.35)}
.bad{background:rgba(255,80,80,.12);border:1px solid rgba(255,80,80,.35)}
.info{background:rgba(0,170,255,.12);border:1px solid rgba(0,170,255,.35)}
.pill{display:inline-block;padding:3px 9px;border-radius:999px;font-size:11px}
.pill-ban{background:rgba(255,80,80,.18);border:1px solid rgba(255,80,80,.5);color:#ffb3b3}
.pill-ok{background:rgba(32,200,120,.18);border:1px solid rgba(32,200,120,.5);color:#a6ffd1}
table{width:100%;border-collapse:collapse}
th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,.10);text-align:left}
code{background:rgba(0,0,0,.35);padding:2px 8px;border-radius:10px}
</style>
</head>
<body>

<div class="top">
  <div class="brand">
    <div style="display:flex;align-items:center;gap:10px">
      <div class="dot"></div>
      <?php if($avatar): ?>
        <img src="<?=h($avatar)?>" style="width:36px;height:36px;border-radius:999px;object-fit:cover;border:1px solid rgba(255,255,255,.18)" alt="avatar">
      <?php endif; ?>
      <div>
        <b>Admin Panel</b> <span class="mini">Secure Login ✅</span><br>
        <span class="mini">Theme: <b>#E91E63</b> • Users: <b><?=count($users)?></b></span>
      </div>
    </div>
    <div class="right-mini">
      <a href="?logout=1" style="color:#ffb3c9;text-decoration:none">Logout</a>
    </div>
  </div>
  <div class="tabbar">
    <?php foreach($tabs as $k=>$n): ?>
      <a class="tab <?=($view===$k?"act":"")?>" href="?view=<?=$k?>"><?=$n?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="wrap">
  <?php if($msg):?><div class="card ok">✅ <?=h($msg)?></div><?php endif;?>
  <?php if($info):?><div class="card info">ℹ️ <?=h($info)?></div><?php endif;?>
  <?php if($err):?><div class="card bad">❌ <?=h($err)?></div><?php endif;?>
  <?php if($warn):?><div class="card bad">⚠️ <?=h(implode(" | ",$warn))?></div><?php endif;?>

  <?php if($view==="dashboard"): ?>
    <?php
      $total_users=count($users);
      $banned=0; $active=0; $total_bal=0;
      foreach($users as $u){
        if(!empty($u["ban"])) $banned++; else $active++;
        $total_bal+=(float)($u["bal"]??0);
      }
      $pending_topups=0; $approved_topups=0; $rejected_topups=0; $total_topup_amt=0;
      foreach($tups as $tp){
        $s=$tp["status"]??"pending";
        if($s==="pending") $pending_topups++;
        elseif($s==="approved"){ $approved_topups++; $total_topup_amt+=(int)($tp["amount"]??0); }
        elseif($s==="rejected") $rejected_topups++;
      }
      $total_orders=0;
      foreach($hist as $cid=>$list){
        if(is_array($list)) $total_orders+=count($list);
      }
      $recent_topups=array_slice(array_reverse($tups),0,10);
      $recent_orders=[];
      foreach($hist as $cid=>$list){
        if(!is_array($list)) continue;
        foreach($list as $r){
          $recent_orders[]=["cid"=>$cid,"time"=>$r["time"]??"-","item"=>$r["order"]??($r["item"]??"-"),"amt"=>$r["amount"]??0];
        }
      }
      usort($recent_orders,function($a,$b){ return strcmp($b["time"],$a["time"]); });
      $recent_orders=array_slice($recent_orders,0,10);
    ?>
    <div class="card">
      <h3>📊 Dashboard Overview</h3>
      <div class="row" style="margin-top:12px">
        <div class="card" style="margin:6px 0">
          <div style="font-size:24px;font-weight:700;color:var(--p)"><?=h($total_users)?></div>
          <div class="mini">Total Users</div>
        </div>
        <div class="card" style="margin:6px 0">
          <div style="font-size:24px;font-weight:700;color:#a6ffd1"><?=h($active)?></div>
          <div class="mini">Active Users</div>
        </div>
        <div class="card" style="margin:6px 0">
          <div style="font-size:24px;font-weight:700;color:#ffb3b3"><?=h($banned)?></div>
          <div class="mini">Banned Users</div>
        </div>
        <div class="card" style="margin:6px 0">
          <div style="font-size:24px;font-weight:700;color:#ffd700"><?=h(ks($total_bal))?></div>
          <div class="mini">Total Wallet Balance</div>
        </div>
      </div>
      <div class="row" style="margin-top:12px">
        <div class="card" style="margin:6px 0">
          <div style="font-size:24px;font-weight:700;color:#ff6aa7"><?=h($pending_topups)?></div>
          <div class="mini">Pending Topups</div>
        </div>
        <div class="card" style="margin:6px 0">
          <div style="font-size:24px;font-weight:700;color:#a6ffd1"><?=h($approved_topups)?></div>
          <div class="mini">Approved Topups</div>
        </div>
        <div class="card" style="margin:6px 0">
          <div style="font-size:24px;font-weight:700;color:#ffb3b3"><?=h($rejected_topups)?></div>
          <div class="mini">Rejected Topups</div>
        </div>
        <div class="card" style="margin:6px 0">
          <div style="font-size:24px;font-weight:700;color:#7CFF9A"><?=h(ks($total_topup_amt))?></div>
          <div class="mini">Total Topup Amount</div>
        </div>
      </div>
      <div class="row" style="margin-top:12px">
        <div class="card" style="margin:6px 0">
          <div style="font-size:24px;font-weight:700;color:#00aaff"><?=h($total_orders)?></div>
          <div class="mini">Total Orders</div>
        </div>
      </div>
    </div>
    <div class="card">
      <h3>🕐 Recent Topup Requests</h3>
      <table>
        <tr><th>Time</th><th>CID</th><th>Username</th><th>Amount</th><th>Ref ID</th><th>Status</th></tr>
        <?php
          if($recent_topups){
            foreach($recent_topups as $tp){
              $cid=$tp["cid"]??"-";
              $uname=$tp["uname"]??($users[$cid]["uname"]??"-");
              $s=$tp["status"]??"pending";
              $pill=$s==="approved"?'pill-ok':($s==="rejected"?'pill-ban':'pill-ok');
              echo "<tr>";
              echo "<td>".h($tp["time"]??"-")."</td>";
              echo "<td><code>".h($cid)."</code></td>";
              echo "<td>".($uname!=="-"?"@".h($uname):"-")."</td>";
              echo "<td><b>".h(ks($tp["amount"]??0))."</b></td>";
              echo "<td><code>".h($tp["ref"]??"-")."</code></td>";
              echo "<td><span class='pill $pill'>".h(ucfirst($s))."</span></td>";
              echo "</tr>";
            }
          }else{
            echo "<tr><td colspan='6'>No topup requests</td></tr>";
          }
        ?>
      </table>
    </div>
    <div class="card">
      <h3>🛒 Recent Orders</h3>
      <table>
        <tr><th>Time</th><th>CID</th><th>Item</th><th>Amount</th></tr>
        <?php
          if($recent_orders){
            foreach($recent_orders as $r){
              echo "<tr>";
              echo "<td>".h($r["time"])."</td>";
              echo "<td><code>".h($r["cid"])."</code></td>";
              echo "<td>".h($r["item"])."</td>";
              echo "<td><b>".h(ks($r["amt"]))."</b></td>";
              echo "</tr>";
            }
          }else{
            echo "<tr><td colspan='4'>No orders</td></tr>";
          }
        ?>
      </table>
    </div>
  <?php endif; ?>

  <?php if($view==="users"): ?>
    <div class="card">
      <h3>👤 Users</h3>
      <form method="get">
        <input type="hidden" name="view" value="users">
        <div class="row">
          <input name="q" placeholder="Search CID..." value="<?=h($q)?>">
          <button class="btn">Search</button>
        </div>
      </form>
    </div>
    <div class="card">
      <table>
        <tr><th>CID</th><th>Username</th><th>Balance</th><th>Join</th><th>Status</th><th>Action</th></tr>
        <?php
          $show=$users;
          if($q!==""){ $show=[]; foreach($users as $cid=>$ud){ if(strpos((string)$cid,$q)!==false) $show[$cid]=$ud; } }
          uasort($show,function($a,$b){ return (int)($b["bal"]??0) <=> (int)($a["bal"]??0); });
          $i=0;
          foreach($show as $cid=>$ud){
            if(++$i>250) break;
            $ban=!empty($ud["ban"]);
            $uname=$ud["uname"]??"-";
            echo "<tr>";
            echo "<td><code>".h($cid)."</code></td>";
            echo "<td>".($uname!=="-"?"@".h($uname):"-")."</td>";
            echo "<td><b>".h(ks($ud["bal"]??0))."</b></td>";
            echo "<td>".h($ud["join"]??"-")."</td>";
            echo "<td>".($ban?'<span class=\"pill pill-ban\">BANNED</span>':'<span class=\"pill pill-ok\">Active</span>')."</td>";
            echo "<td>";
            echo "<form method='post' style='margin:0;display:inline'>";
            echo "<input type='hidden' name='cid' value='".h($cid)."'>";
            echo "<button class='btn' name='toggle_ban' style='padding:6px 10px;font-size:11px;width:auto'>".($ban?'UNBAN':'BAN')."</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
          }
          if(!$show) echo "<tr><td colspan='6'>No users</td></tr>";
        ?>
      </table>
    </div>
  <?php endif; ?>

  <?php if($view==="balance"): ?>
    <div class="card">
      <h3>💰 Balance + Auto Notify</h3>
      <form method="post">
        <div class="row">
          <input name="cid" placeholder="CID (numbers only)" required>
          <input name="amt" placeholder="+5000 / -2000" required>
        </div>
        <button class="btn" name="add_bal" style="margin-top:10px">UPDATE & NOTIFY</button>
      </form>
    </div>
  <?php endif; ?>

  <?php if($view==="notify"): ?>
    <div class="card">
      <h3>🔔 Notify (One User)</h3>
      <form method="post" enctype="multipart/form-data">
        <input name="n_cid" placeholder="CID" required>
        <textarea name="n_text" placeholder="Message (Caption)"></textarea>
        <div class="row" style="margin-top:10px">
          <input name="n_img_url" placeholder="Image URL (optional)">
          <input type="file" name="n_img_file" accept="image/*">
        </div>
        <button class="btn" name="notify_one" style="margin-top:10px">SEND</button>
      </form>
    </div>
  <?php endif; ?>

  <?php if($view==="broadcast"): ?>
    <div class="card">
      <h3>📣 Broadcast (ALL Users)</h3>
      <form method="post" enctype="multipart/form-data">
        <textarea name="b_text" placeholder="Message (Caption)"></textarea>
        <div class="row" style="margin-top:10px">
          <input name="b_img_url" placeholder="Image URL (optional)">
          <input type="file" name="b_img_file" accept="image/*">
        </div>
        <div class="row" style="margin-top:10px">
          <input name="b_delay" value="<?=h(BC_DELAY_MS)?>" placeholder="Delay ms">
          <input value="Users: <?=count($users)?>" readonly>
        </div>
        <button class="btn" name="broadcast_all" style="margin-top:10px">SEND BROADCAST</button>
      </form>
    </div>
    <?php if($fails): ?>
      <div class="card bad"><h3>❌ Failed</h3>
        <textarea readonly><?=h(json_encode($fails,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT))?></textarea>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <?php if($view==="topup"): ?>
    <div class="card">
      <h3>💸 Pending Topup Requests</h3>
      <div class="mini">User မှ amount + Ref ID ပေးပို့ထားသော Topup များကို ဒီနေရာမှာ စစ်ဆေးပြီး လက်ခံနိုင်ပါတယ် ✅</div>
    </div>
    <div class="card">
      <table>
        <tr><th>Time</th><th>CID</th><th>Username</th><th>Amount</th><th>Ref ID</th><th>Status</th><th>Action</th></tr>
        <?php
          $has=false;
          foreach($tups as $tp){
            $status=$tp["status"]??"pending";
            if($status!=="pending") continue;
            $has=true;
            $cid=$tp["cid"]??"-";
            $uname=$tp["uname"]??($users[$cid]["uname"]??"-");
            $amt=(int)($tp["amount"]??0);
            $ref=$tp["ref"]??"-";
            $time=$tp["time"]??"-";
            echo "<tr>";
            echo "<td>".h($time)."</td>";
            echo "<td><code>".h($cid)."</code></td>";
            echo "<td>".($uname!=="-"?"@".h($uname):"-")."</td>";
            echo "<td><b>".h(ks($amt))."</b></td>";
            echo "<td><code>".h($ref)."</code></td>";
            echo "<td><span class='pill pill-ok'>Pending</span></td>";
            echo "<td>";
            echo "<form method='post' style='display:inline-block;margin-right:4px'>";
            echo "<input type='hidden' name='tid' value='".h($tp["id"]??"")."'>";
            echo "<button class='btn' name='topup_approve' style='padding:6px 10px;font-size:11px;width:auto'>APPROVE</button>";
            echo "</form>";
            echo "<form method='post' style='display:inline-block;margin:0'>";
            echo "<input type='hidden' name='tid' value='".h($tp["id"]??"")."'>";
            echo "<button class='btn' name='topup_reject' style='padding:6px 10px;font-size:11px;width:auto;background:rgba(255,80,80,.85)'>REJECT</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
          }
          if(!$has) echo "<tr><td colspan='7'>No pending topup requests</td></tr>";
        ?>
      </table>
    </div>
  <?php endif; ?>

  <?php if($view==="topup_history"): ?>
    <div class="card">
      <h3>📋 Topup Requests History</h3>
      <div class="mini">Approved / Rejected / Pending အားလုံးကို ဒီနေရာမှာ ကြည့်နိုင်ပါတယ် ✅</div>
    </div>
    <div class="card">
      <form method="get">
        <input type="hidden" name="view" value="topup_history">
        <div class="row">
          <input name="topup_q" placeholder="Search by CID..." value="<?=h($topup_q)?>">
          <button class="btn">Search</button>
        </div>
      </form>
    </div>
    <div class="card">
      <table>
        <tr><th>Time</th><th>CID</th><th>Username</th><th>Amount</th><th>Ref ID</th><th>Status</th></tr>
        <?php
          $sorted=array_reverse($tups);
          $show=$sorted;
          if($topup_q!==""){
            $show=[];
            foreach($sorted as $tp){
              $cid_str=(string)($tp["cid"]??"");
              if(strpos($cid_str,$topup_q)!==false) $show[]=$tp;
            }
          }
          $i=0;
          foreach($show as $tp){
            if(++$i>200) break;
            $cid=$tp["cid"]??"-";
            $uname=$tp["uname"]??($users[$cid]["uname"]??"-");
            $amt=(int)($tp["amount"]??0);
            $ref=$tp["ref"]??"-";
            $time=$tp["time"]??"-";
            $s=$tp["status"]??"pending";
            $pill=$s==="approved"?'pill-ok':($s==="rejected"?'pill-ban':'pill-ok');
            echo "<tr>";
            echo "<td>".h($time)."</td>";
            echo "<td><code>".h($cid)."</code></td>";
            echo "<td>".($uname!=="-"?"@".h($uname):"-")."</td>";
            echo "<td><b>".h(ks($amt))."</b></td>";
            echo "<td><code>".h($ref)."</code></td>";
            echo "<td><span class='pill $pill'>".h(ucfirst($s))."</span></td>";
            echo "</tr>";
          }
          if(!$show) echo "<tr><td colspan='6'>No topup history".($topup_q!==""?" (filtered by CID: ".h($topup_q).")":"")."</td></tr>";
        ?>
      </table>
    </div>
  <?php endif; ?>

  <?php if($view==="prices"): ?>
    <div class="card"><h3>🎮 Prices (JSON)</h3><div class="mini">Backup လုပ်ပြီးမှ Save ✅</div></div>

    <div class="card"><form method="post">
      <h3>💎 ML</h3>
      <textarea name="json"><?=h($ml)?></textarea>
      <input type="hidden" name="file" value="<?=h(ML_FILE)?>">
      <button class="btn" name="save_price" style="margin-top:10px">SAVE ML</button>
    </form></div>

    <div class="card"><form method="post">
      <h3>🟢 PUBG UC</h3>
      <textarea name="json"><?=h($uc)?></textarea>
      <input type="hidden" name="file" value="<?=h(PUBG_UC_FILE)?>">
      <button class="btn" name="save_price" style="margin-top:10px">SAVE UC</button>
    </form></div>

    <div class="card"><form method="post">
      <h3>🎫 PUBG CODE</h3>
      <textarea name="json"><?=h($pc)?></textarea>
      <input type="hidden" name="file" value="<?=h(PUBG_CODE_FILE)?>">
      <button class="btn" name="save_price" style="margin-top:10px">SAVE CODE</button>
    </form></div>
  <?php endif; ?>

  <?php if($view==="bot"): ?>
    <div class="card">
      <h3>🧩 bot.php Manager</h3>
      <div class="mini">Save/Upload လုပ်တိုင်း <code>/backup</code> ထဲ backup သိမ်းပေးမယ် ✅</div>
    </div>

    <div class="card">
      <h3>✍️ Edit & Save</h3>
      <form method="post">
        <textarea name="bot_text" style="min-height:420px"><?=h($bot_now)?></textarea>
        <button class="btn" name="bot_save_text" style="margin-top:10px">SAVE bot.php</button>
      </form>
    </div>

    <div class="card">
      <h3>📤 Upload bot.php</h3>
      <form method="post" enctype="multipart/form-data">
        <input type="file" name="bot_file" accept=".php,text/plain" required>
        <button class="btn" name="bot_upload_file" style="margin-top:10px">UPLOAD & REPLACE</button>
      </form>
      <div class="mini">Max <?=h(MAX_PHP_MB)?>MB • PHP မဟုတ်ရင် reject လုပ်မယ်</div>
    </div>
  <?php endif; ?>

  <?php if($view==="settings"): ?>
    <?php $cfg_now=admin_cfg(); ?>
    <div class="card"><h3>⚙️ Settings</h3><div class="mini">Admin password change • Avatar • Topup notification</div></div>

    <div class="card">
      <h3>🖼️ Avatar</h3>
      <form method="post" enctype="multipart/form-data">
        <div class="row">
          <input name="avatar_url" placeholder="Avatar Image URL (https://...)" value="<?=h((string)($cfg_now["avatar"]??""))?>">
          <input type="file" name="avatar_file" accept="image/*">
        </div>
        <button class="btn" name="admin_save_profile" style="margin-top:10px">SAVE AVATAR</button>
      </form>
      <div class="mini">URL သို့မဟုတ် File တစ်ခုထဲကတစ်ခု ထည့်နိုင်ပါတယ် • Max <?=h(MAX_IMG_MB)?>MB</div>
    </div>

    <div class="card">
      <h3>🔔 Topup Request Notification</h3>
      <form method="post">
        <div class="row">
          <input name="admin_chat_id" placeholder="Admin Telegram Chat ID (numbers)" value="<?=h((string)($cfg_now["admin_chat_id"]??""))?>">
          <input value="TOPUP: <?=h(TOPUP)?>" readonly>
        </div>
        <div style="margin-top:10px">
          <label style="display:flex;gap:8px;align-items:center;font-size:13px;opacity:.9">
            <input type="checkbox" name="topup_admin_noti" value="1" style="width:auto" <?=!isset($cfg_now["topup_admin_noti"])||!empty($cfg_now["topup_admin_noti"]) ? "checked" : ""?>>
            Enable Admin Noti
          </label>
        </div>
        <button class="btn" name="admin_save_noti" style="margin-top:10px">SAVE NOTI</button>
      </form>
      <div class="mini">Chat ID မသိရင် Bot ကို Admin account နဲ့ /start လုပ်ပြီး CID ကို Users tab မှာ ကြည့်နိုင်ပါတယ်</div>
    </div>

    <div class="card">
      <h3>🔐 Change Admin Password</h3>
      <form method="post">
        <input type="password" name="old_pw" placeholder="Old Password" required>
        <div class="row" style="margin-top:10px">
          <input type="password" name="new_pw" placeholder="New Password (min 6 chars)" required>
          <input type="password" name="new_pw2" placeholder="Confirm New Password" required>
        </div>
        <button class="btn" name="admin_change_pw" style="margin-top:10px">CHANGE PASSWORD</button>
      </form>
    </div>
  <?php endif; ?>

  <?php if($view==="history"): ?>
    <div class="card"><h3>📜 History</h3></div>
    <div class="card">
      <table>
        <tr><th>Time</th><th>CID</th><th>Item</th><th>Amount</th></tr>
        <?php
          $rows=[];
          foreach($hist as $cid=>$list){
            if(!is_array($list)) continue;
            foreach($list as $r){
              $rows[]=["time"=>$r["time"]??"-","cid"=>$cid,"item"=>$r["order"]??($r["item"]??"-"),"amt"=>$r["amount"]??0];
            }
          }
          $i=0;
          foreach($rows as $r){
            if(++$i>400) break;
            echo "<tr><td>".h($r["time"])."</td><td><code>".h($r["cid"])."</code></td><td>".h($r["item"])."</td><td><b>".h($r["amt"])."</b></td></tr>";
          }
          if(!$rows) echo "<tr><td colspan='4'>No history</td></tr>";
        ?>
      </table>
    </div>
  <?php endif; ?>

</div>
</body>
</html>