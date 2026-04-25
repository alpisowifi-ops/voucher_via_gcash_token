#!/data/data/com.termux/files/usr/bin/bash

clear
echo "🔥 INSTALLING PISO WIFI SYSTEM..."

pkg update -y
pkg upgrade -y

pkg install php git tmux termux-api termux-services -y

echo "📂 Setting storage..."
termux-setup-storage
sleep 2

# ================= SETUP FOLDER =================
echo "📁 Creating project folder..."
rm -rf ~/htdocs
mkdir -p ~/htdocs

echo "📥 Cloning project..."
git clone https://github.com/alpisowifi-ops/voucher_via_gcash.git ~/htdocs

cd ~/htdocs

# ================= GENERATE RANDOM API =================
echo "🔐 Generating secure API..."

API_NAME=$(tr -dc a-z0-9 </dev/urandom | head -c 6)
SECRET_KEY=$(tr -dc a-z0-9 </dev/urandom | head -c 10)

# RENAME API FILE
mv d6s0or.php ${API_NAME}.php

# REPLACE SECRET KEY SA FILE
sed -i "s/u36qbe29fl/${SECRET_KEY}/g" ${API_NAME}.php

# SAVE CONFIG
echo "API_FILE=${API_NAME}.php" > api_config.txt
echo "SECRET_KEY=${SECRET_KEY}" >> api_config.txt

# ================= AUTO START =================
echo "⚙️ Setting auto-start..."

mkdir -p ~/.termux/boot

cat > ~/.termux/boot/start.sh << EOF
#!/data/data/com.termux/files/usr/bin/sh

termux-wake-lock
cd ~/htdocs
tmux new-session -d -s wifi "php -S 0.0.0.0:8080"
EOF

chmod +x ~/.termux/boot/start.sh

# ================= START SERVER =================
echo "🚀 Starting server..."

tmux kill-session -t wifi 2>/dev/null
tmux new-session -d -s wifi "php -S 0.0.0.0:8080"

# ================= GET IP =================
IP=$(ip route get 1 | awk '{print $7; exit}')

echo ""
echo "✅ INSTALL COMPLETE!"
echo ""
echo "🌐 Open this in browser:"
echo "👉 http://${IP}:8080"
echo ""
echo "🔐 Admin Panel:"
echo "👉 http://${IP}:8080/admin.php"
echo "👉 Password: admin123"
echo ""
echo "⚙️ MacroDroid URL:"
echo "👉 http://${IP}:8080/${API_NAME}.php?amount=10&key=${SECRET_KEY}"
echo ""
echo "⚡ Server runs in background (tmux)"
echo "⚡ Auto start on reboot enabled"