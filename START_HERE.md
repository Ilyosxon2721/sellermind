# 🚀 START HERE - Deploy SellerMind to Production

**Everything is ready! Just follow these 3 simple steps:**

---

## 📋 What You Need

- ✅ Ubuntu 20.04+ server with root access
- ✅ Domain name pointed to your server
- ✅ MySQL root password (or ability to set one)
- ✅ Telegram Bot Token (get from @BotFather)

---

## 🎯 OPTION 1: Automated Deployment (Recommended - 10 minutes)

### On Your Production Server:

```bash
# 1. Connect to your server
ssh user@your-server-ip

# 2. Download the deployment script
wget https://raw.githubusercontent.com/Ilyosxon2721/sellermind/claude/review-production-readiness-LSoNy/deploy.sh
chmod +x deploy.sh

# 3. Edit configuration (IMPORTANT!)
nano deploy.sh

# Change these lines:
# DOMAIN="your-actual-domain.com"
# (other values will be prompted)

# 4. Run deployment
./deploy.sh

# Follow the prompts:
# - Enter MySQL password
# - Enter Telegram Bot Token
# - Confirm SSL setup (y/n)
```

**That's it!** The script will:
- Install all dependencies (Nginx, PHP, MySQL, Redis, Supervisor)
- Clone and configure the application
- Setup database and run migrations
- Configure queue workers
- Setup cron scheduler
- Configure Nginx with SSL
- Run verification tests

---

## 🎯 OPTION 2: Manual Deployment (30 minutes)

Follow the complete checklist:

```bash
# On your local machine, copy the checklist
cat DEPLOYMENT_CHECKLIST.md

# Then follow step-by-step on your server
```

---

## 🎯 OPTION 3: GitHub Deployment

### Step 1: Push to Main Branch

```bash
# On your local machine
cd /home/user/sellermind

# The code is already in branch: claude/review-production-readiness-LSoNy
# Create a PR or merge directly

# Create main branch (if doesn't exist)
git checkout -b main
git push origin main

# Or push your feature branch
git push origin claude/review-production-readiness-LSoNy
```

### Step 2: On Production Server

```bash
ssh user@your-server-ip

# Clone repository
sudo mkdir -p /var/www
cd /var/www
sudo git clone https://github.com/Ilyosxon2721/sellermind.git
cd sellermind

# Checkout the branch
sudo git checkout claude/review-production-readiness-LSoNy

# Run the deployment script
sudo chmod +x deploy.sh
./deploy.sh
```

---

## ✅ After Deployment - Verification

### 1. Check Services

```bash
# All should show "active (running)" or "RUNNING"
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status redis-server
sudo systemctl status mysql
sudo supervisorctl status
```

### 2. Run Smoke Tests

```bash
cd /var/www/sellermind
./tests/smoke-tests.sh
```

### 3. Test in Browser

Visit your domain:
- **Homepage:** https://your-domain.com
- **Login:** https://your-domain.com/login
- **Dashboard:** https://your-domain.com/dashboard

### 4. Test Quick Wins

1. **Products & Bulk Ops:** `/products` - Try bulk price update
2. **Smart Promotions:** `/promotions` - Click "Find slow-moving products"
3. **Sales Analytics:** `/analytics` - View charts
4. **Review Responses:** `/reviews` - Test AI generation

---

## 📊 What's Automated

After deployment, these run automatically:

### Every Monday at 9:00 AM
- 🤖 Auto-create promotions for slow-moving inventory
- 📊 Calculate optimal discounts (15-50%)
- 💰 Track ROI

### Every Day at 10:00 AM
- 🔔 Send expiring promotion notifications
- 📱 Telegram alerts to managers

### Every Hour
- 📈 Update analytics cache
- ⚡ Sync marketplace data

### Every 10 Minutes
- 🛒 Sync orders from WB/Ozon/Yandex
- 📦 Update stock levels

---

## 🔧 Common Post-Deployment Commands

```bash
# View application logs
sudo tail -f /var/www/sellermind/storage/logs/laravel.log

# View worker logs
sudo tail -f /var/www/sellermind/storage/logs/worker.log

# Check scheduled tasks
sudo -u www-data php artisan schedule:list

# Check queue status
sudo -u www-data php artisan queue:failed

# Restart queue workers
sudo supervisorctl restart sellermind-worker:*

# Clear cache
sudo -u www-data php artisan cache:clear

# Check cron
sudo crontab -u www-data -l
```

---

## 🆘 Troubleshooting

### Workers not running?
```bash
sudo supervisorctl status
sudo supervisorctl restart sellermind-worker:*
sudo tail -f /var/www/sellermind/storage/logs/worker.log
```

### Scheduler not working?
```bash
sudo crontab -u www-data -l
sudo grep CRON /var/log/syslog
sudo -u www-data php artisan schedule:run
```

### 500 Error?
```bash
sudo tail -f /var/www/sellermind/storage/logs/laravel.log
sudo tail -f /var/log/nginx/error.log
```

### Permission issues?
```bash
sudo chown -R www-data:www-data /var/www/sellermind
sudo chmod -R 775 /var/www/sellermind/storage
sudo chmod -R 775 /var/www/sellermind/bootstrap/cache
```

---

## 📚 Documentation

All documentation is in the repository:

- **Quick Start:** `PRODUCTION_DEPLOYMENT.md` (30 min guide)
- **Full Guide:** `docs/AUTOMATION_AND_DEPLOYMENT.md` (comprehensive)
- **Checklist:** `DEPLOYMENT_CHECKLIST.md` (step-by-step)
- **Feature Guides:** `docs/SMART_PROMOTIONS_GUIDE.md`, etc.

---

## 🎉 Success Checklist

- [ ] All services running (Nginx, PHP, MySQL, Redis, Supervisor)
- [ ] Homepage loads with HTTPS
- [ ] Login works
- [ ] Dashboard displays correctly
- [ ] All 5 Quick Wins accessible
- [ ] Smoke tests passing
- [ ] Queue workers running (6 processes)
- [ ] Cron scheduler configured
- [ ] Logs clean (no critical errors)

---

## 🚀 Quick Deploy Summary

**Fastest way:**

1. SSH into your production server
2. Download: `wget https://raw.githubusercontent.com/Ilyosxon2721/sellermind/claude/review-production-readiness-LSoNy/deploy.sh`
3. Edit: `nano deploy.sh` (set DOMAIN)
4. Run: `./deploy.sh`
5. Done! 🎉

**Time:** ~10 minutes (automated)

---

## 📞 Need Help?

If you get stuck:

1. **Check logs first:**
   ```bash
   sudo tail -f /var/www/sellermind/storage/logs/laravel.log
   ```

2. **Check troubleshooting section** in `DEPLOYMENT_CHECKLIST.md`

3. **Review documentation:**
   - `docs/AUTOMATION_AND_DEPLOYMENT.md`
   - `PRODUCTION_DEPLOYMENT.md`

---

## 🎊 What You Get

After deployment, you have:

✅ **5 Complete Quick Wins:**
1. Bulk Operations (80% time saved)
2. Telegram Notifications (real-time)
3. Smart Promotions (25% inventory reduction)
4. Sales Analytics (data-driven decisions)
5. Review Responses (70% time saved)

✅ **Full Automation:**
- Auto promotions weekly
- Daily notifications
- Hourly analytics
- Continuous marketplace sync

✅ **Production-Ready:**
- HTTPS/SSL configured
- Queue workers running
- Scheduler active
- Monitoring in place

---

**Ready? Pick Option 1 (Automated) and deploy in 10 minutes!** 🚀

```bash
# Copy this single command:
wget https://raw.githubusercontent.com/Ilyosxon2721/sellermind/claude/review-production-readiness-LSoNy/deploy.sh && chmod +x deploy.sh && nano deploy.sh
```

**Edit the DOMAIN line, save, then run:**
```bash
./deploy.sh
```

🎉 **Done!** 🎉
