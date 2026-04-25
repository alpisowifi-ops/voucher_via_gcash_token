<?php
date_default_timezone_set("Asia/Manila");

// GET AMOUNT
$amount = $_GET['amount'] ?? 0;
if(!$amount){
    die("Invalid amount");
}

// LOAD CONFIG
$config = json_decode(file_get_contents("config.json"), true);

// TOKENS FILE
$tokens_file = "tokens.json";

// CREATE FILE IF NOT EXISTS
if(!file_exists($tokens_file)){
    file_put_contents($tokens_file, json_encode([], JSON_PRETTY_PRINT));
}

$tokens = json_decode(file_get_contents($tokens_file), true);
if(!is_array($tokens)) $tokens = [];

// 🔥 AUTO CLEAN OLD TOKENS (EXPIRE 3 MINUTES)
$now = time();
foreach($tokens as $t => $info){
    if(($now - $info['time']) > 180){
        unset($tokens[$t]);
    }
}

// 🔐 GENERATE SECURE TOKEN
$token = bin2hex(random_bytes(5));

// 🌐 GET USER IP
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// SAVE TOKEN
$tokens[$token] = [
    "amount" => intval($amount),
    "status" => "pending",
    "time" => time(),
    "ip" => $ip
];

// SAVE FILE
file_put_contents($tokens_file, json_encode($tokens, JSON_PRETTY_PRINT));
?>

<!DOCTYPE html>
<html>
<head>
<title>Pay ₱<?= $amount ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
body {
    font-family: Arial;
    text-align: center;
    background: linear-gradient(135deg,#0f2027,#203a43,#2c5364);
    color:white;
    margin:0;
}

.box {
    background:white;
    color:black;
    margin:20px;
    padding:20px;
    border-radius:15px;
}

button {
    padding:15px;
    margin:10px;
    width:90%;
    border:none;
    border-radius:10px;
    font-size:16px;
    font-weight:bold;
}

.btn-paid { background:#00c853; color:white; }
.btn-download { background:#607d8b; color:white; }

img {
    width:220px;
    border-radius:10px;
}

#timer {
    font-size:16px;
    margin-top:10px;
}
</style>
</head>

<body>

<h2>💳 Pay ₱<?= $amount ?></h2>

<div class="box">
    <h3>📷 Scan QR to Pay</h3>

    <img src="<?= $config['qr'] ?>"><br><br>

    <a href="<?= $config['qr'] ?>" download>
        <button class="btn-download">⬇ Download QR</button>
    </a>

    <p>⚠️ Pay exact amount: <b>₱<?= $amount ?></b></p>

    <button class="btn-paid" onclick="paidClick()">✅ I HAVE PAID</button>

    <div id="timer"></div>
</div>

<script>

// ⏳ TIMER (3 MINUTES)
let start = Math.floor(Date.now()/1000);
let duration = 180;

let timer = setInterval(() => {

    let now = Math.floor(Date.now()/1000);
    let left = duration - (now - start);

    if(left <= 0){
        clearInterval(timer);
        alert("⏰ Session expired, please try again.");
        window.location.href = "index.php";
        return;
    }

    let m = Math.floor(left/60);
    let s = left%60;

    document.getElementById("timer").innerText =
        "⏳ Time left: " +
        String(m).padStart(2,'0') + ":" +
        String(s).padStart(2,'0');

},1000);


// 👉 REDIRECT TO WAIT PAGE WITH TOKEN
function paidClick(){
    window.location.href = "wait.php?token=<?= $token ?>";
}

</script>

</body>
</html>