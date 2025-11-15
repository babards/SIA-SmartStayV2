# 🚀 SmartStay Deployment Guide - Railway

This guide will help you deploy your SmartStay Laravel application to Railway for **FREE**!

## 📋 Prerequisites

1. A Railway account (sign up at https://railway.app)
2. Git installed on your computer
3. Your project pushed to GitHub/GitLab (or Railway CLI)

---

## 🎯 Deployment Steps

### Step 1: Create Railway Account

1. Go to https://railway.app
2. Click "Login" and sign up with GitHub (recommended)
3. You'll get **$5 free credit monthly** (no credit card required initially)

### Step 2: Create New Project

#### Option A: Deploy from GitHub (Recommended)

1. Push your code to GitHub:
   ```bash
   git add .
   git commit -m "Prepare for Railway deployment"
   git push origin main
   ```

2. In Railway Dashboard:
   - Click "New Project"
   - Select "Deploy from GitHub repo"
   - Authorize Railway to access your GitHub
   - Select your `SmartStay` repository
   - Click "Deploy Now"

#### Option B: Deploy with Railway CLI

1. Install Railway CLI:
   ```bash
   # Windows (PowerShell)
   npm install -g @railway/cli
   
   # Or download from https://railway.app/cli
   ```

2. Login to Railway:
   ```bash
   railway login
   ```

3. Initialize and deploy:
   ```bash
   railway init
   railway up
   ```

### Step 3: Add PostgreSQL Database

1. In your Railway project dashboard, click "+ New"
2. Select "Database" → "PostgreSQL"
3. Railway will automatically create the database and set environment variables

### Step 4: Configure Environment Variables

1. Click on your web service
2. Go to "Variables" tab
3. Add the following variables:

#### Required Variables

```bash
APP_NAME=SmartStay
APP_ENV=production
APP_KEY=<generate-this-below>
APP_DEBUG=false
APP_URL=<your-railway-app-url>

# Database (Railway auto-fills these from PostgreSQL service)
# PGHOST, PGPORT, PGDATABASE, PGUSER, PGPASSWORD are already set

# Session & Cache
SESSION_DRIVER=database
CACHE_DRIVER=file
QUEUE_CONNECTION=database

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
```

#### Optional - Email Configuration (for sending emails)

If you want email functionality, add these:

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME=SmartStay
```

**For Gmail:**
- Use "App Password" (not your regular password)
- Generate at: https://myaccount.google.com/apppasswords

**Free SMTP alternatives:**
- Mailtrap.io (free tier: 500 emails/month)
- SendGrid (free tier: 100 emails/day)
- Mailgun (free tier: 5,000 emails/month)

### Step 5: Generate APP_KEY

1. In Railway, click on your service
2. Go to "Settings" tab
3. Scroll to "Deploy Triggers"
4. Click "Deploy" to start a deployment
5. Once deployed, open the "Deployments" tab
6. Click on the latest deployment
7. Open the terminal (icon looks like `>_`)
8. Run:
   ```bash
   php artisan key:generate --show
   ```
9. Copy the generated key
10. Go back to "Variables" tab and add it as `APP_KEY`
11. Redeploy the application

**OR** generate locally and copy:

```bash
# In your local project
php artisan key:generate --show
# Copy the output (e.g., base64:xxxxxxxxxxxxx)
```

### Step 6: Connect Database to Web Service

Railway should automatically connect your PostgreSQL database to your web service. Verify:

1. Go to your PostgreSQL database in Railway
2. Check "Connected Services" - your web service should be listed
3. If not, click "Connect" and select your web service

### Step 7: Access Your Application

1. In Railway, click on your web service
2. Go to "Settings" tab
3. Scroll to "Networking"
4. Click "Generate Domain"
5. Railway will give you a URL like: `smartstay-production.up.railway.app`
6. Visit your URL - your app should be live! 🎉

---

## 🔧 Post-Deployment Tasks

### Run Migrations (If needed)

Migrations run automatically on deployment, but if you need to run them manually:

1. Open your service in Railway
2. Go to "Deployments" tab
3. Click the latest deployment
4. Open terminal
5. Run:
   ```bash
   php artisan migrate --force
   ```

### Create Admin User

Create your admin account:

```bash
php artisan tinker

# Then in tinker:
User::create([
    'name' => 'Admin',
    'email' => 'admin@smartstay.com',
    'password' => bcrypt('your-secure-password'),
    'role' => 'admin',
    'email_verified_at' => now()
]);
```

### Clear Caches

If you update code:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

## 📊 Monitor Your Application

### View Logs

1. Go to your service in Railway
2. Click "Deployments" tab
3. Click on active deployment
4. View real-time logs

### Check Database

1. Click on PostgreSQL service
2. Go to "Data" tab
3. Or use a tool like TablePlus/DBeaver with connection details from "Connect" tab

---

## 💰 Cost Estimates

**Free Tier ($5/month credit):**
- Web Service: ~$3-4/month
- PostgreSQL: ~$1-2/month
- **Total: Should stay within free credit!**

**Tips to stay free:**
- Free credit resets monthly
- Monitor usage in Railway dashboard
- Remove unused deployments

---

## 🔄 Updating Your Application

### Push updates to GitHub:

```bash
git add .
git commit -m "Your update message"
git push origin main
```

Railway will automatically detect the push and redeploy!

### Or with CLI:

```bash
railway up
```

---

## 🛠️ Troubleshooting

### Build Failed

Check the build logs in Railway. Common issues:
- Missing dependencies in `composer.json`
- Node/npm errors (check `package.json`)
- PHP version mismatch

### Database Connection Error

1. Verify PostgreSQL service is running
2. Check that services are connected
3. Verify environment variables are set

### 500 Error

1. Set `APP_DEBUG=true` temporarily
2. Check logs in Railway
3. Ensure `APP_KEY` is set
4. Run migrations

### File Upload Issues

By default, files are stored in `storage/app/public`. On Railway, the filesystem is ephemeral (resets on deployment). For production, consider:
- AWS S3 (free tier: 5GB)
- Cloudinary (free tier: 25GB)
- Railway Volumes (persistent storage)

To use Railway Volumes:
1. Go to your service
2. Click "Settings"
3. Scroll to "Volumes"
4. Add volume: Mount path `/app/storage`

---

## 🎉 Success!

Your SmartStay application should now be live on Railway!

**Important URLs:**
- Application: Your Railway domain
- Database: Railway PostgreSQL connection string (in Variables)
- Logs: Railway Deployments tab

---

## 📚 Additional Resources

- Railway Docs: https://docs.railway.app
- Laravel Deployment: https://laravel.com/docs/deployment
- PostgreSQL Setup: https://railway.app/templates/postgres

---

## ⚡ Quick Commands Reference

```bash
# Login to Railway
railway login

# Link to project
railway link

# Open project dashboard
railway open

# View logs
railway logs

# Run commands
railway run php artisan migrate

# Deploy
railway up
```

---

## 🆘 Need Help?

- Railway Discord: https://discord.gg/railway
- Laravel Discord: https://discord.gg/laravel
- Stack Overflow: Tag with `laravel` and `railway`

Good luck with your deployment! 🚀

