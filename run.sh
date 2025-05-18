#!/bin/bash

# Xaxino Ultimate Casino Platform - Startup Script

echo "🎰 Starting Xaxino Ultimate Casino Platform..."

# Set executable permissions
chmod +x run.sh

# Install PHP extensions and required packages
if command -v apt-get &> /dev/null; then
    echo "📦 Installing system dependencies..."
    apt-get update && apt-get install -y php php-mbstring php-xml php-bcmath php-curl php-zip php-mysql unzip curl
fi

# Create storage directory structure if it doesn't exist
echo "📁 Setting up storage directories..."
mkdir -p storage/app/public/kyc_documents
mkdir -p storage/app/contracts
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p bootstrap/cache

# Set proper permissions
chmod -R 775 storage bootstrap/cache

# Check if .env file exists, create from example if not
if [ ! -f .env ]; then
    echo "⚙️ Creating environment file..."
    cp .env.example .env
    
    # Generate application key
    echo "🔑 Generating application key..."
    php artisan key:generate
fi

# Check and create database if needed
if [ ! -d /var/lib/mysql/xaxino ]; then
    echo "💾 Setting up database..."
    
    # Start MySQL service
    service mysql start || true
    
    # Create database and user
    mysql -e "CREATE DATABASE IF NOT EXISTS xaxino;"
    mysql -e "CREATE USER IF NOT EXISTS 'xaxino'@'localhost' IDENTIFIED BY 'xaxino_password';"
    mysql -e "GRANT ALL PRIVILEGES ON xaxino.* TO 'xaxino'@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"
    
    # Update .env with database credentials
    sed -i 's/DB_DATABASE=.*/DB_DATABASE=xaxino/' .env
    sed -i 's/DB_USERNAME=.*/DB_USERNAME=xaxino/' .env
    sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=xaxino_password/' .env
fi

# Store Solidity ABI for Smart Contract if needed
if [ ! -f storage/app/contracts/CasinoPayout.json ]; then
    echo "📜 Setting up smart contract ABI..."
    mkdir -p storage/app/contracts
    
    cat > storage/app/contracts/CasinoPayout.json << 'EOF'
[
  {
    "inputs": [],
    "stateMutability": "nonpayable",
    "type": "constructor"
  },
  {
    "anonymous": false,
    "inputs": [
      {
        "indexed": true,
        "internalType": "address",
        "name": "recipient",
        "type": "address"
      },
      {
        "indexed": false,
        "internalType": "uint256",
        "name": "amount",
        "type": "uint256"
      },
      {
        "indexed": false,
        "internalType": "uint256",
        "name": "timestamp",
        "type": "uint256"
      }
    ],
    "name": "PayoutSent",
    "type": "event"
  },
  {
    "stateMutability": "payable",
    "type": "fallback"
  },
  {
    "inputs": [],
    "name": "getBalance",
    "outputs": [
      {
        "internalType": "uint256",
        "name": "",
        "type": "uint256"
      }
    ],
    "stateMutability": "view",
    "type": "function"
  },
  {
    "inputs": [],
    "name": "owner",
    "outputs": [
      {
        "internalType": "address",
        "name": "",
        "type": "address"
      }
    ],
    "stateMutability": "view",
    "type": "function"
  },
  {
    "inputs": [
      {
        "internalType": "address payable",
        "name": "winner",
        "type": "address"
      },
      {
        "internalType": "uint256",
        "name": "amount",
        "type": "uint256"
      }
    ],
    "name": "payout",
    "outputs": [],
    "stateMutability": "nonpayable",
    "type": "function"
  },
  {
    "inputs": [
      {
        "internalType": "address",
        "name": "newOwner",
        "type": "address"
      }
    ],
    "name": "transferOwnership",
    "outputs": [],
    "stateMutability": "nonpayable",
    "type": "function"
  },
  {
    "stateMutability": "payable",
    "type": "receive"
  }
]
EOF
fi

# Register the symbolic link to public storage if needed
if [ ! -L public/storage ]; then
    echo "🔗 Creating storage symlink..."
    php artisan storage:link
fi

# Update composer dependencies
if command -v composer &> /dev/null; then
    echo "🎵 Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Run database migrations and seed data
echo "🌱 Running database migrations..."
php artisan migrate --force

# Seed admin user if it doesn't exist
if ! mysql -u xaxino -pxaxino_password xaxino -e "SELECT * FROM users WHERE role = 'admin' LIMIT 1" | grep -q admin; then
    echo "👤 Creating admin user..."
    php artisan db:seed --class=AdminUserSeeder
    
    # Create AdminUserSeeder if it doesn't exist
    if [ ! -f database/seeders/AdminUserSeeder.php ]; then
        mkdir -p database/seeders
        cat > database/seeders/AdminUserSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@xaxino.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'kyc_status' => 'verified',
            'status' => 'active',
        ]);
        
        $this->command->info('Admin user created: admin@xaxino.com / admin123');
    }
}
EOF
        
        # Update DatabaseSeeder to include the AdminUserSeeder
        if [ -f database/seeders/DatabaseSeeder.php ]; then
            sed -i '/public function run/a \ \ \ \ \ \ \ \ $this->call(AdminUserSeeder::class);' database/seeders/DatabaseSeeder.php
        fi
        
        # Run the seeder
        php artisan db:seed --class=AdminUserSeeder
    fi
fi

# Start MySQL service in background (if not already running)
service mysql start &> /dev/null || true

echo "🚀 Starting Laravel server..."
echo "⭐ Access the casino at: http://localhost:3000"
echo "👤 Admin login: admin@xaxino.com / admin123"

# Start the Laravel server on port 3000
php artisan serve --host=0.0.0.0 --port=3000
