#!/bin/bash

# SmartStay - Railway Deployment Helper Script
# This script helps you deploy your Laravel app to Railway

echo "🚀 SmartStay Railway Deployment Helper"
echo "======================================="
echo ""

# Check if Railway CLI is installed
if ! command -v railway &> /dev/null
then
    echo "❌ Railway CLI is not installed."
    echo ""
    echo "Please install it first:"
    echo "  npm install -g @railway/cli"
    echo ""
    echo "Or download from: https://railway.app/cli"
    exit 1
fi

echo "✅ Railway CLI is installed"
echo ""

# Check if logged in
echo "🔐 Checking Railway authentication..."
if ! railway whoami &> /dev/null
then
    echo "❌ Not logged in to Railway."
    echo ""
    echo "Logging you in..."
    railway login
else
    echo "✅ Already logged in to Railway"
fi

echo ""
echo "📦 Deployment Options:"
echo ""
echo "1. Initialize new Railway project"
echo "2. Link to existing Railway project"
echo "3. Deploy to Railway"
echo "4. View logs"
echo "5. Open Railway dashboard"
echo ""
read -p "Choose an option (1-5): " option

case $option in
    1)
        echo ""
        echo "🆕 Initializing new Railway project..."
        railway init
        echo ""
        echo "✅ Project initialized!"
        echo ""
        echo "Next steps:"
        echo "1. Add PostgreSQL database in Railway dashboard"
        echo "2. Configure environment variables"
        echo "3. Run: ./deploy-railway.sh and choose option 3"
        ;;
    2)
        echo ""
        echo "🔗 Linking to existing Railway project..."
        railway link
        echo ""
        echo "✅ Project linked!"
        ;;
    3)
        echo ""
        echo "🚀 Deploying to Railway..."
        railway up
        echo ""
        echo "✅ Deployment complete!"
        echo ""
        echo "Run: railway open"
        echo "to view your app in the browser"
        ;;
    4)
        echo ""
        echo "📋 Viewing logs..."
        railway logs
        ;;
    5)
        echo ""
        echo "🌐 Opening Railway dashboard..."
        railway open
        ;;
    *)
        echo "❌ Invalid option"
        exit 1
        ;;
esac

echo ""
echo "✨ Done!"

