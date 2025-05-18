from flask import Flask, render_template, redirect, url_for, flash, request, session
from flask_sqlalchemy import SQLAlchemy
from werkzeug.security import generate_password_hash, check_password_hash
import os
import json
import uuid
import hashlib
import random
from datetime import datetime

# Create the app
app = Flask(__name__)
app.secret_key = os.environ.get("SESSION_SECRET", "xaxino_secret_key")

# Configure the database
app.config["SQLALCHEMY_DATABASE_URI"] = os.environ.get("DATABASE_URL", "sqlite:///casino.db")
app.config["SQLALCHEMY_TRACK_MODIFICATIONS"] = False

# Initialize the database
db = SQLAlchemy(app)

# Define models
class User(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(64), unique=True, nullable=False)
    email = db.Column(db.String(120), unique=True, nullable=False)
    password_hash = db.Column(db.String(256))
    role = db.Column(db.String(20), default='user')
    google2fa_secret = db.Column(db.String(32), nullable=True)
    kyc_status = db.Column(db.String(20), default='pending')
    wallets = db.relationship('Wallet', backref='user', lazy=True)
    bets = db.relationship('DiceBet', backref='user', lazy=True)

    def set_password(self, password):
        self.password_hash = generate_password_hash(password)

    def check_password(self, password):
        return check_password_hash(self.password_hash, password)

    def is_admin(self):
        return self.role == 'admin'

class Wallet(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    currency = db.Column(db.String(10), nullable=False)
    address = db.Column(db.String(64), nullable=False)
    private_key = db.Column(db.String(64), nullable=False)
    balance = db.Column(db.Float, default=0.0)

class DiceBet(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    bet_amount = db.Column(db.Float, nullable=False)
    client_seed = db.Column(db.String(64), nullable=False)
    server_seed = db.Column(db.String(64), nullable=False)
    server_seed_hash = db.Column(db.String(64), nullable=False)
    rolled_number = db.Column(db.Float, nullable=False)
    payout = db.Column(db.Float, default=0.0)
    currency = db.Column(db.String(10), nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

class KycDocument(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    document_type = db.Column(db.String(20), nullable=False)
    document_path = db.Column(db.String(256), nullable=False)
    status = db.Column(db.String(20), default='pending')
    notes = db.Column(db.Text, nullable=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

# Helper functions
def generate_wallet_address(currency):
    """Generate a random wallet address for demo purposes"""
    address = hashlib.sha256(str(uuid.uuid4()).encode()).hexdigest()
    if currency == 'BTC':
        return f'bc1{address[:38]}'
    elif currency == 'ETH':
        return f'0x{address[:40]}'
    return address

def generate_private_key():
    """Generate a random private key for demo purposes"""
    return hashlib.sha256(str(uuid.uuid4()).encode()).hexdigest()

def get_user_wallets():
    """Get current user's wallets or create them if they don't exist"""
    if 'user_id' not in session:
        return []
    
    user = User.query.get(session['user_id'])
    if not user:
        return []
    
    # Create wallets if they don't exist
    if len(user.wallets) == 0:
        for currency in ['BTC', 'ETH']:
            new_wallet = Wallet()
            new_wallet.user_id = user.id
            new_wallet.currency = currency
            new_wallet.address = generate_wallet_address(currency)
            new_wallet.private_key = generate_private_key()
            new_wallet.balance = 1.0  # Demo balance
            db.session.add(new_wallet)
        db.session.commit()
    
    return user.wallets

def get_recent_bets():
    """Get the user's recent bets"""
    if 'user_id' not in session:
        return []
    
    return DiceBet.query.filter_by(user_id=session['user_id']).order_by(DiceBet.created_at.desc()).limit(10).all()

# Routes
@app.route('/')
def home():
    return render_template('home.html', is_authenticated='user_id' in session)

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        email = request.form.get('email')
        password = request.form.get('password')
        
        user = User.query.filter_by(email=email).first()
        
        if user and user.check_password(password):
            session['user_id'] = user.id
            session['username'] = user.username
            
            # Check for 2FA
            if user.google2fa_secret:
                # Redirect to 2FA verification (simplified)
                flash('2FA is enabled but we\'re skipping it in this demo', 'info')
            
            return redirect(url_for('home'))
        else:
            flash('Invalid email or password', 'danger')
    
    return render_template('login.html')

@app.route('/register', methods=['GET', 'POST'])
def register():
    if request.method == 'POST':
        username = request.form.get('username')
        email = request.form.get('email')
        password = request.form.get('password')
        
        # Check if user exists
        existing_user = User.query.filter((User.username == username) | (User.email == email)).first()
        if existing_user:
            flash('Username or email already exists', 'danger')
            return render_template('register.html')
        
        # Create new user
        new_user = User()
        new_user.username = username
        new_user.email = email
        new_user.set_password(password)
        
        db.session.add(new_user)
        db.session.commit()
        
        # Log the user in
        session['user_id'] = new_user.id
        session['username'] = new_user.username
        
        # Redirect to wallet setup
        return redirect(url_for('wallets'))
    
    return render_template('register.html')

@app.route('/logout')
def logout():
    session.clear()
    return redirect(url_for('home'))

@app.route('/wallets')
def wallets():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    wallets = get_user_wallets()
    return render_template('wallets.html', wallets=wallets)

@app.route('/wallets/deposit/<currency>')
def deposit(currency):
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    wallet = Wallet.query.filter_by(user_id=session['user_id'], currency=currency).first()
    if not wallet:
        flash('Wallet not found', 'danger')
        return redirect(url_for('wallets'))
    
    return render_template('deposit.html', wallet=wallet)

@app.route('/wallets/withdraw/<currency>')
def withdraw(currency):
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    wallet = Wallet.query.filter_by(user_id=session['user_id'], currency=currency).first()
    if not wallet:
        flash('Wallet not found', 'danger')
        return redirect(url_for('wallets'))
    
    return render_template('withdraw.html', wallet=wallet)

@app.route('/games/dice')
def dice_game():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    wallets = get_user_wallets()
    recent_bets = get_recent_bets()
    
    return render_template('dice.html', wallets=wallets, recent_bets=recent_bets)

@app.route('/games/dice/play', methods=['POST'])
def play_dice():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    data = request.get_json()
    bet_amount = float(data.get('bet_amount', 0))
    client_seed = data.get('client_seed', '')
    wallet_id = int(data.get('wallet_id', 0))
    
    wallet = Wallet.query.get(wallet_id)
    
    # Validate wallet and balance
    if not wallet or wallet.user_id != session['user_id']:
        return json.dumps({'error': 'Invalid wallet selected'})
    
    if wallet.balance < bet_amount:
        return json.dumps({'error': 'Insufficient balance'})
    
    # Generate server seed and hash
    server_seed = hashlib.sha256(str(uuid.uuid4()).encode()).hexdigest()
    server_seed_hash = hashlib.sha256(server_seed.encode()).hexdigest()
    
    # Generate roll result (0.00-99.99)
    combined_seed = server_seed + client_seed
    hash_value = hashlib.sha256(combined_seed.encode()).hexdigest()
    rolled_number = float(int(hash_value[:8], 16) % 10000) / 100
    
    # Determine win (roll under 50.00 is a win with 2x payout)
    is_win = rolled_number < 50.00
    multiplier = 2.0 if is_win else 0.0
    payout = bet_amount * multiplier
    
    # Create bet record
    new_bet = DiceBet()
    new_bet.user_id = session['user_id']
    new_bet.bet_amount = bet_amount
    new_bet.client_seed = client_seed
    new_bet.server_seed = server_seed
    new_bet.server_seed_hash = server_seed_hash
    new_bet.rolled_number = rolled_number
    new_bet.payout = payout
    new_bet.currency = wallet.currency
    
    # Update wallet balance
    wallet.balance -= bet_amount
    if is_win:
        wallet.balance += payout
    
    db.session.add(new_bet)
    db.session.commit()
    
    return json.dumps({
        'bet': {
            'id': new_bet.id,
            'bet_amount': new_bet.bet_amount,
            'client_seed': new_bet.client_seed,
            'server_seed': new_bet.server_seed,
            'server_seed_hash': new_bet.server_seed_hash,
            'rolled_number': new_bet.rolled_number,
            'payout': new_bet.payout,
            'currency': new_bet.currency,
            'created_at': new_bet.created_at.strftime('%H:%M:%S')
        },
        'wallet_balance': wallet.balance,
        'is_win': is_win
    })

@app.route('/kyc')
def kyc():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    user = User.query.get(session['user_id'])
    if user is None:
        return redirect(url_for('logout'))
        
    documents = KycDocument.query.filter_by(user_id=user.id).all()
    
    return render_template('kyc.html', user=user, documents=documents)

@app.route('/admin')
def admin():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    user = User.query.get(session['user_id'])
    if not user or not user.is_admin():
        flash('Access denied', 'danger')
        return redirect(url_for('home'))
    
    pending_kyc = KycDocument.query.filter_by(status='pending').all()
    users = User.query.all()
    
    return render_template('admin.html', pending_kyc=pending_kyc, users=users)

# Initialize the database and create admin user for demo
with app.app_context():
    db.create_all()
    
    # Create admin user if it doesn't exist
    existing_admin = User.query.filter_by(email='admin@xaxino.com').first()
    if existing_admin is None:
        admin_user = User()
        admin_user.username = 'admin'
        admin_user.email = 'admin@xaxino.com'
        admin_user.role = 'admin'
        admin_user.kyc_status = 'verified'
        admin_user.set_password('admin123')
        db.session.add(admin_user)
        
        # Create demo user
        existing_user = User.query.filter_by(email='user@example.com').first()
        if existing_user is None:
            demo_user = User()
            demo_user.username = 'demouser'
            demo_user.email = 'user@example.com'
            demo_user.kyc_status = 'pending'
            demo_user.set_password('password123')
            db.session.add(demo_user)
        
        db.session.commit()

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)