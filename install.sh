#!/data/data/com.termux/files/usr/bin/bash

clear
echo "🔥 INSTALLING PISO WIFI TOKEN SYSTEM..."

set -e  # stop pag may error

pkg update -y && pkg upgrade -y
pkg install php git tmux iproute2 -y

echo "🧹 Cleaning old install..."
rm -rf ~/htdocs

echo "📥 Cloning system..."
git clone https://github.com/alpisowifi-ops/voucher_via_gcash_token ~/htdocs || {
    echo "❌ CLONE FAILED! Check internet."
    exit 1
}

cd ~/htdocs

# 🔍 CHECK FILE EXISTS
if [ ! -f "d6s0or.php" ]; then
    echo "❌ ERROR: d6s0or.php not found!"
    ls
    exit 1
fi

echo "✅ Files OK"

# 🔐 GENERATE KEYS
API_NAME=$(tr -dc a-z0-9 </dev/urandom | head -c 6)
SECRET_KEY=$(tr -dc a-z0-9 </dev/urandom | head -c 10)

# 🔁 RENAME API
mv d6s0or.php $API_NAME.php

# 🔑 REPLACE KEY
sed -i "s/u36qbe29fl/$SECRET_KEY/g" $API_NAME.php

# 🌐 GET IP (safe fallback)
IP=$(ip route get 1 2>/dev/null | awk '{print $7;exit}')
[ -z "$IP" ] && IP="127.0.0.1"

# 🚀 START SERVER
cat > ~/start_wifi.sh <<EOF
cd ~/htdocs
php -S 0.0.0.0:8080
EOF

chmod +x ~/start_wifi.sh

tmux kill-session -t wifi 2>/dev/null || true
tmux new-session -d -s wifi "~/start_wifi.sh"

echo ""
echo "✅ INSTALL COMPLETE!"
echo ""
echo "🌐 Open:"
echo "👉 http://$IP:8080"
echo ""
echo "🔐 Admin:"
echo "👉 http://$IP:8080/admin.php"
echo "👉 Password: admin123"
echo ""
echo "⚙️ API:"
echo "👉 http://$IP:8080/$API_NAME.php?amount=10&key=$SECRET_KEY"
echo ""
echo "⚡ Running in background (tmux)"
