<?php
$config = json_decode(file_get_contents("config.json"), true);
?>

<!DOCTYPE html>
<html>
<head>
<title>Buy WiFi Voucher</title>
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

.btn-price { background:#2196F3; color:white; }
</style>
</head>

<body>

<h2>📶 Buy WiFi Voucher</h2>

<!-- PRICE ONLY -->
<div class="box">
    <h3>💸 Select Amount</h3>

    <?php foreach($config['rates'] as $r): ?>
        <button class="btn-price" onclick="buy(<?= $r['amount'] ?>)">
            ₱<?= $r['amount'] ?> - <?= $r['label'] ?>
        </button>
    <?php endforeach; ?>

</div>

<!-- INSTRUCTIONS -->
<div class="box">
    <h3>📌 How to Use</h3>
    <ul style="text-align:left; font-size:14px; line-height:1.8; padding-left:20px;">
        <li>Select amount</li>
        <li>Scan QR and pay</li>
        <li>Tap <b>I HAVE PAID</b></li>
        <li>Wait for voucher</li>
        <li>Auto connect</li>
    </ul>
</div>

<script>

// 🔥 REDIRECT TO PAY PAGE (TOKEN GENERATED THERE)
function buy(amount){
    window.location.href = "pay.php?amount=" + amount;
}

</script>

</body>
</html>