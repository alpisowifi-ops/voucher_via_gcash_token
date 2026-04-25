<?php
session_start();

$pass_file = "admin_pass.txt";
$config_file = "config.json";
$voucher_file = "vouchers.json";
$logs_file = "logs.json";

// ================= SAFE JSON =================
function load_json($file){
    if(!file_exists($file)){
        file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function save_json($file, $data){
    $fp = fopen($file, 'w');
    if(flock($fp, LOCK_EX)){
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

// ================= INIT =================
if(!file_exists($pass_file)){
    file_put_contents($pass_file, password_hash("admin123", PASSWORD_DEFAULT));
}

$config = load_json($config_file);
$data   = load_json($voucher_file);
$logs   = load_json($logs_file);

if(!isset($config['qr'])) $config['qr'] = "qr.jpg";
if(!isset($config['rates'])) $config['rates'] = [];
if(!isset($config['earnings'])) $config['earnings'] = 0;

// ================= LOGIN =================
$saved_pass = file_get_contents($pass_file);

if(!isset($_SESSION['login'])){
    if(isset($_POST['pass'])){
        if(password_verify($_POST['pass'], $saved_pass)){
            $_SESSION['login'] = true;
            header("Location: admin.php"); exit;
        } else $error="Wrong password!";
    }
?>
<div style="text-align:center;margin-top:80px;">
<h2>🔐 Admin Login</h2>
<form method="post">
<input type="password" name="pass" placeholder="Password"><br><br>
<button>Login</button>
</form>
<p style="color:red;"><?php if(isset($error)) echo $error;?></p>
</div>
<?php exit; }

// LOGOUT
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: admin.php"); exit;
}

// ================= CHANGE PASSWORD =================
if(isset($_POST['new_pass']) && isset($_POST['current_pass'])){
    if(password_verify($_POST['current_pass'], $saved_pass)){
        file_put_contents($pass_file, password_hash($_POST['new_pass'], PASSWORD_DEFAULT));
        $msg = "✅ Password updated!";
    } else {
        $msg = "❌ Wrong current password!";
    }
}

// ================= UPLOAD QR =================
if(isset($_FILES['qr'])){
    move_uploaded_file($_FILES['qr']['tmp_name'], "qr.jpg");
    $config['qr'] = "qr.jpg";
    save_json($config_file, $config);
}

// ================= ADD RATE =================
if(isset($_POST['new_amount']) && isset($_POST['new_label'])){
    $config['rates'][] = [
        "amount"=>intval($_POST['new_amount']),
        "label"=>$_POST['new_label']
    ];
    save_json($config_file, $config);
}

// DELETE RATE
if(isset($_GET['delrate'])){
    $del = intval($_GET['delrate']);
    $config['rates'] = array_values(array_filter($config['rates'], fn($r)=>$r['amount']!=$del));
    save_json($config_file, $config);
}

// ================= ADD VOUCHERS (FIXED) =================
if(isset($_POST['amount']) && isset($_POST['codes'])){
    $a = intval($_POST['amount']);
    if(!isset($data[$a])) $data[$a]=[];

    $lines = explode("\n", $_POST['codes']);

    foreach($lines as $c){
        $c = trim($c);
        if($c && !in_array($c, $data[$a])){
            $data[$a][] = $c;
        }
    }

    save_json($voucher_file, $data);

    // 🔥 FIX: reload page para iwas duplicate submit
    header("Location: admin.php");
    exit;
}

// DELETE ALL
if(isset($_GET['delete_all'])){
    $data[$_GET['delete_all']] = [];
    save_json($voucher_file, $data);
}

// DELETE ONE
if(isset($_GET['delete_one'])){
    $a=$_GET['amount'];
    $code=$_GET['delete_one'];

    if(isset($data[$a])){
        $data[$a]=array_values(array_filter($data[$a], fn($v)=>$v!==$code));
        save_json($voucher_file, $data);
    }
}

// CLEAR LOGS
if(isset($_POST['clear_logs'])){
    save_json($logs_file, []);
}
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Panel</title>

<style>
body{font-family:Arial;background:#0f2027;color:white;margin:0;padding:15px;}
.card{background:white;color:black;padding:15px;margin-bottom:15px;border-radius:12px;}
button{padding:10px;border:none;border-radius:8px;background:#2196F3;color:white;}
input,textarea,select{width:100%;padding:10px;margin-top:5px;border-radius:8px;border:1px solid #ccc;}
.voucher{display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding:5px;}
.scroll{max-height:200px;overflow:auto;}
</style>
</head>

<body>

<h2>🔥 ADMIN PANEL</h2>
<a href="?logout=1" style="color:red;">Logout</a>

<!-- CHANGE PASSWORD -->
<div class="card">
<h3>🔑 Change Password</h3>
<form method="post">
<input type="password" name="current_pass" placeholder="Current Password" required>
<input type="password" name="new_pass" placeholder="New Password" required>
<button>Update Password</button>
</form>
<?php if(isset($msg)): ?><p><?= $msg ?></p><?php endif; ?>
</div>

<!-- EARNINGS -->
<div class="card">
<h3>💰 Earnings</h3>
<h2>₱<?= $config['earnings'] ?></h2>
</div>

<!-- QR -->
<div class="card">
<h3>📷 QR</h3>
<img src="<?= $config['qr'] ?>" width="120"><br><br>
<form method="post" enctype="multipart/form-data">
<input type="file" name="qr">
<button>Upload</button>
</form>
</div>

<!-- RATES -->
<div class="card">
<h3>💸 Rates</h3>
<?php foreach($config['rates'] as $r): ?>
<p>₱<?= $r['amount'] ?> - <?= $r['label'] ?>
<a href="?delrate=<?= $r['amount'] ?>">❌</a></p>
<?php endforeach; ?>

<form method="post">
<input name="new_amount" placeholder="Amount">
<input name="new_label" placeholder="Label">
<button>Add Rate</button>
</form>
</div>

<!-- ADD VOUCHERS -->
<div class="card">
<h3>📋 Upload Vouchers</h3>
<form method="post">
<select name="amount">
<?php foreach($config['rates'] as $r): ?>
<option value="<?= $r['amount'] ?>">₱<?= $r['amount'] ?></option>
<?php endforeach; ?>
</select>

<textarea name="codes" placeholder="Enter codes, one per line"></textarea>
<button>Upload</button>
</form>
</div>

<!-- REMAINING -->
<div class="card">
<h3>📊 Remaining</h3>
<?php foreach($data as $a=>$list): ?>
<p>₱<?= $a ?> = <?= count($list) ?>
<a href="?delete_all=<?= $a ?>">❌</a></p>
<?php endforeach; ?>
</div>

<!-- LIST (FIXED DISPLAY) -->
<div class="card">
<h3>📋 Voucher List</h3>

<?php foreach($data as $a=>$list): ?>
<h4>₱<?= $a ?></h4>

<?php if(empty($list)): ?>
<p>No vouchers</p>
<?php else: ?>
<div class="scroll">
<?php foreach($list as $v): ?>
<div class="voucher">
<span><?= htmlspecialchars($v) ?></span>
<a href="?delete_one=<?= urlencode($v) ?>&amount=<?= $a ?>">❌</a>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php endforeach; ?>
</div>

<!-- LOGS -->
<div class="card">
<h3>📊 User Logs</h3>

<?php if(empty($logs)): ?>
<p>No logs yet</p>
<?php else: ?>
<div class="scroll">
<?php foreach(array_reverse($logs) as $log): ?>
<div style="border-bottom:1px solid #eee;padding:8px;">
<b><?= $log['voucher'] ?? 'N/A' ?></b><br>
₱<?= $log['amount'] ?? '0' ?> | <?= $log['time'] ?? '-' ?><br>
IP: <?= $log['ip'] ?? 'N/A' ?><br>
MAC: <?= $log['mac'] ?? 'N/A' ?><br>
Token: <?= $log['token'] ?? 'N/A' ?>
</div>
<?php endforeach; ?>
</div>

<form method="post">
<button name="clear_logs" style="background:red;">Clear Logs</button>
</form>

<?php endif; ?>
</div>

</body>
</html>
