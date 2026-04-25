<!DOCTYPE html>
<html>
<head>
<title>Waiting Voucher</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
body {
    font-family: Arial;
    text-align: center;
    background: #0f2027;
    color: white;
    margin:0;
}

.box {
    background: white;
    color: black;
    margin: 20px;
    padding: 25px;
    border-radius: 15px;
}

.voucher {
    font-size: 32px;
    font-weight: bold;
    color: green;
}

#timer {
    font-size: 18px;
    margin-top: 10px;
}

.loading {
    margin-top: 50px;
    font-size: 18px;
}
</style>
</head>

<body>

<h2>⏳ Processing Payment...</h2>

<div class="loading" id="loading">
    Please wait while we verify your payment...
</div>

<div class="box" id="box" style="display:none;">
    <h3>✅ Voucher Detected</h3>

    <div class="voucher" id="code"></div>
    <div id="timer"></div>

    <p>⚡ Connecting... Please wait</p>
</div>

<script>

// ✅ GET TOKEN
let token = new URLSearchParams(location.search).get("token");

// FLAGS
let activated = false;
let timerStarted = false;

// 🔁 CHECK CURRENT.TXT EVERY 2s
setInterval(() => {

    fetch("current.txt?" + Date.now())
    .then(res => res.text())
    .then(code => {

        code = code.trim();

        if(code && code !== "0"){

            document.getElementById("loading").style.display = "none";
            document.getElementById("box").style.display = "block";
            document.getElementById("code").innerText = code;

            // ⏳ START TIMER ONCE
            if(!timerStarted){
                startTimer();
                timerStarted = true;
            }

            // 🔥 AUTO CONNECT ONCE
            if(!activated){

                activated = true;

                fetch("http://10.0.0.1/vouchers/activate", {
                    method:"POST",
                    headers:{
                        "Content-Type":"application/x-www-form-urlencoded"
                    },
                    body:"code=" + encodeURIComponent(code)
                })
                .then(res => res.text())
                .then(data => {

                    console.log("Activated:", data);

                    // 🧹 CLEAR AFTER USE
                    fetch("clear.php");

                    // redirect after connect
                    setTimeout(()=>{
                        window.location.href = "http://10.0.0.1";
                    },1500);

                });

            }

        }

    });

}, 2000);


// ⏳ TIMER (3 MINUTES)
function startTimer(){

    let start = Math.floor(Date.now()/1000);
    let duration = 180;

    setInterval(() => {

        let now = Math.floor(Date.now()/1000);
        let left = duration - (now - start);

        if(left <= 0){

            alert("⏰ Voucher expired");

            fetch("clear.php");

            window.location.href = "index.php";
            return;
        }

        let m = Math.floor(left/60);
        let s = left%60;

        document.getElementById("timer").innerText =
            "⏳ Expire in: " +
            String(m).padStart(2,'0') + ":" +
            String(s).padStart(2,'0');

    },1000);
}

</script>

</body>
</html>