#!/data/data/com.termux/files/usr/bin/bash

clear
echo "🔥 INSTALLING PISO WIFI TOKEN SYSTEM..."

pkg update -y
pkg upgrade -y

pkg install php git tmux termux-api termux-services iproute2 -y

echo "📂 Setting storage..."
termux-setup-storage

sleep 2

# 🔥 REMOVE OLD SYSTEM
echo "🧹 Cleaning old install..."
rm -rf ~/htdocs
rm -rf ~/voucher_via_gcash
rm -rf ~/voucher_via_gcash_token

# 📥 CLONE NEW TOKEN SYSTEM
echo "📥 Downloading system..."
git clone https://github.com/alpisowifi-ops/voucher_via_gcash_token ~/htdocs

cd ~/htdocs

# 🔐 GENERATE RANDOM API NAME + KEY
API_NAME=$(tr -dc a-z0-9 </dev/urandom | head -c 6)
SECRET_KEY=$(tr -dc a-z0-9 </dev/urandom | head -c 10)

# 🔁 RENAME API FILE
mv d6s0or.php $API_NAME.php

# 🔑 REPLACE SECRET KEY
sed -i "s/u36qbe29fl/$SECRET_KEY/g" $API_NAME.php

# 🌐 GET IP (SAFE FIX)
IP=$(ip route get 1 | awk '{print $7;exit}')

# ⚙️ AUTO START SERVER
echo "🚀 Setting auto-start..."

cat > ~/start_wifi.sh <<EOF
cd ~/htdocs
php -S 0.0.0.0:8080
EOF

chmod +x ~/start_wifi.sh

# RUN SERVER IN TMUX
tmux new-session -d -s wifi "~/start_wifi.sh"

echo ""
echo "✅ INSTALL COMPLETE!"
echo ""
echo "🌐 Open this in browser:"
echo "👉 http://$IP:8080"
echo ""
echo "🔐 Admin Panel:"
echo "👉 http://$IP:8080/admin.php"
echo "👉 Password: admin123"
echo ""
echo "⚙️ MacroDroid URL:"
echo "👉 http://$IP:8080/$API_NAME.php?amount=10&key=$SECRET_KEY"
echo ""
echo "⚡ Server running in background (tmux)"
echo "⚡ Auto start enabled"
