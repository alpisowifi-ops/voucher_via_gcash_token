<?php
date_default_timezone_set("Asia/Manila");

header("Content-Type: application/json");

// ================= FILES =================
$voucher_file = "vouchers.json";
$tokens_file  = "tokens.json";
$logs_file    = "logs.json";

// 🔐 SECRET KEY
$SECRET = "u36qbe29fl";

// ================= AUTH =================
if(!isset($_GET['key']) || $_GET['key'] !== $SECRET){
    die(json_encode([
        "status"=>"error",
        "msg"=>"Unauthorized"
    ]));
}

// ================= LOAD JSON =================
function load_json($file){
    if(!file_exists($file)){
        file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

$data   = load_json($voucher_file);
$tokens = load_json($tokens_file);
$logs   = load_json($logs_file);

// ================= GET AMOUNT =================
$amount = intval($_GET['amount'] ?? 0);

if(!$amount){
    die(json_encode([
        "status"=>"error",
        "msg"=>"Invalid amount"
    ]));
}

// ================= FIND MATCHING TOKEN =================
$targetToken = null;

foreach(array_reverse($tokens, true) as $t => $info){

    // match amount + pending + not expired
    if(
        intval($info['amount']) === $amount &&
        $info['status'] === "pending" &&
        (time() - $info['time']) <= 180
    ){
        $targetToken = $t;
        break;
    }
}

if(!$targetToken){
    die(json_encode([
        "status"=>"error",
        "msg"=>"No pending token"
    ]));
}

// ================= CHECK VOUCHERS =================
if(!isset($data[$amount]) || count($data[$amount]) == 0){
    die(json_encode([
        "status"=>"error",
        "msg"=>"No voucher available"
    ]));
}

// ================= GET VOUCHER =================
$voucher = array_shift($data[$amount]);

// SAVE vouchers
file_put_contents($voucher_file, json_encode($data, JSON_PRETTY_PRINT));

// ================= EARNINGS =================
$config_file = "config.json";
$config = load_json($config_file);

if(!isset($config['earnings'])){
    $config['earnings'] = 0;
}

$config['earnings'] += intval($amount);

file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));

// ================= SAVE CURRENT =================
file_put_contents("current.txt", $voucher);

// ================= MARK TOKEN USED =================
$tokens[$targetToken]['status'] = "used";
$tokens[$targetToken]['used_time'] = time();

// OPTIONAL: remove token completely
unset($tokens[$targetToken]);

file_put_contents($tokens_file, json_encode($tokens, JSON_PRETTY_PRINT));

// ================= SAVE LOG =================
$logs[] = [
    "voucher"=>$voucher,
    "amount"=>$amount,
    "time"=>date("Y-m-d H:i:s"),
    "ip"=>$tokens[$targetToken]['ip'] ?? $_SERVER['REMOTE_ADDR'],
    "token"=>$targetToken
];

file_put_contents($logs_file, json_encode($logs, JSON_PRETTY_PRINT));

// ================= RESPONSE =================
echo json_encode([
    "status"=>"success",
    "voucher"=>$voucher,
    "amount"=>$amount
]);