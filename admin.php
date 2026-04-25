<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$pass_file = "admin_pass.txt";
$config_file = "config.json";
$voucher_file = "vouchers.json";
$logs_file = "logs.json";

// ================= SAFE LOAD =================
function load_json($file){
    if(!file_exists($file)){
        file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
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

// ================= LOGOUT =================
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: admin.php"); exit;
}

// ================= CHANGE PASSWORD =================
if(isset($_POST['new_pass']) && isset($_POST['current_pass'])){
    if(password_verify($_POST['current_pass'], $saved_pass)){
        file_put_contents($pass_file, password_hash($_POST['new_pass'], PASSWORD_DEFAULT));
        header("Location: admin.php"); exit;
    }
}

// ================= UPLOAD QR =================
if(isset($_FILES['qr'])){
    move_uploaded_file($_FILES['qr']['tmp_name'], "qr.jpg");
    $config['qr'] = "qr.jpg";
    file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
    header("Location: admin.php"); exit;
}

// ================= ADD RATE =================
if(isset($_POST['new_amount']) && isset($_POST['new_label'])){
    $amount = intval($_POST['new_amount']);
    $label  = trim($_POST['new_label']);

    if($amount && $label){
        foreach($config['rates'] as $r){
            if($r['amount'] == $amount){
                header("Location: admin.php"); exit;
            }
        }

        $config['rates'][] = [
            "amount"=>$amount,
            "label"=>$label
        ];

        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
    }

    header("Location: admin.php"); exit;
}

// ================= DELETE RATE =================
if(isset($_GET['delrate'])){
    $del = intval($_GET['delrate']);
    $config['rates'] = array_values(array_filter($config['rates'], fn($r)=>$r['amount']!=$del));
    file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
    header("Location: admin.php"); exit;
}

// ================= UPLOAD VOUCHERS =================
if(isset($_POST['amount']) && isset($_POST['codes'])){
    $a = intval($_POST['amount']);

    if(!isset($data[$a])) $data[$a] = [];

    foreach(explode("\n", $_POST['codes']) as $c){
        $c = trim($c);
        if(!$c) continue;

        if(!in_array($c, $data[$a])){
            $data[$a][] = $c;
        }
    }

    file_put_contents($voucher_file, json_encode($data, JSON_PRETTY_PRINT));

    header("Location: admin.php?success=1"); exit;
}

// ================= DELETE ALL =================
if(isset($_GET['delete_all'])){
    $data[$_GET['delete_all']] = [];
    file_put_contents($voucher_file, json_encode($data, JSON_PRETTY_PRINT));
    header("Location: admin.php"); exit;
}

// ================= DELETE ONE =================
if(isset($_GET['delete_one'])){
    $a=$_GET['amount'];
    $code=$_GET['delete_one'];

    if(isset($data[$a])){
        $data[$a]=array_values(array_filter($data[$a], fn($v)=>$v!==$code));
        file_put_contents($voucher_file, json_encode($data, JSON_PRETTY_PRINT));
    }

    header("Location: admin.php"); exit;
}

// ================= CLEAR LOGS =================
if(isset($_POST['clear_logs'])){
    file_put_contents($logs_file, json_encode([], JSON_PRETTY_PRINT));
    header("Location: admin.php"); exit;
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

<?php if(isset($_GET['success'])): ?>
<p style="color:green;">✅ Upload successful</p>
<?php endif; ?>

<!-- EARNINGS -->
<div class="card">
<h3>💰 Earnings</h3>
<h2>₱<?= $config['earnings'] ?></h2>
</div>

<!-- RATES -->
<div class="card">
<h3>💸 Rates</h3>
<?php foreach($config['rates'] as $r): ?>
<p>₱<?= $r['amount'] ?> - <?= $r['label'] ?> <a href="?delrate=<?= $r['amount'] ?>">❌</a></p>
<?php endforeach; ?>

<form method="post">
<input name="new_amount" placeholder="Amount">
<input name="new_label" placeholder="Label">
<button>Add Rate</button>
</form>
</div>

<!-- UPLOAD VOUCHERS -->
<div class="card">
<h3>📋 Upload Vouchers</h3>
<form method="post">
<select name="amount">
<?php foreach($config['rates'] as $r): ?>
<option value="<?= $r['amount'] ?>">₱<?= $r['amount'] ?></option>
<?php endforeach; ?>
</select>

<textarea name="codes"></textarea>
<button>Upload</button>
</form>
</div>

<!-- REMAINING -->
<div class="card">
<h3>📊 Remaining</h3>
<?php foreach($data as $a=>$list): ?>
<p>₱<?= $a ?> = <?= count($list) ?> <a href="?delete_all=<?= $a ?>">❌</a></p>
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
<b><?= htmlspecialchars($log['voucher'] ?? 'N/A') ?></b><br>
₱<?= intval($log['amount'] ?? 0) ?> | <?= htmlspecialchars($log['time'] ?? '-') ?><br>
IP: <?= htmlspecialchars($log['ip'] ?? 'N/A') ?><br>
MAC: <?= htmlspecialchars($log['mac'] ?? 'N/A') ?><br>
Token: <?= htmlspecialchars($log['token'] ?? 'N/A') ?>
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
