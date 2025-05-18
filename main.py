from flask import Flask, render_template, redirect, url_for, flash, request, session
from flask_sqlalchemy import SQLAlchemy
from werkzeug.security import generate_password_hash, check_password_hash
import os
import json
import uuid
import hashlib
import random
from datetime import datetime, timedelta

# Create the app
app = Flask(__name__)
app.secret_key = os.environ.get("SESSION_SECRET", "xaxino_secret_key")

# Configure the database
# Make sure we use the right PostgreSQL URL format
database_url = os.environ.get("DATABASE_URL")
if database_url and database_url.startswith("postgres://"):
    database_url = database_url.replace("postgres://", "postgresql://", 1)

# Always provide a fallback if DATABASE_URL is not set
app.config["SQLALCHEMY_DATABASE_URI"] = database_url or "sqlite:///casino.db"
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
    slot_bets = db.relationship('SlotBet', backref='user', lazy=True)
    blackjack_games = db.relationship('BlackjackGame', backref='user', lazy=True)
    responsible_gaming = db.relationship('ResponsibleGaming', backref='user', uselist=False, lazy=True)
    deposit_periods = db.relationship('DepositLimitPeriod', backref='user', lazy=True)
    bonuses = db.relationship('Bonus', backref='user', lazy=True)

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

class SlotBet(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    bet_amount = db.Column(db.Float, nullable=False)
    client_seed = db.Column(db.String(64), nullable=False)
    server_seed = db.Column(db.String(64), nullable=False)
    server_seed_hash = db.Column(db.String(64), nullable=False)
    result_symbols = db.Column(db.String(64), nullable=False)  # Comma-separated symbols
    multiplier = db.Column(db.Float, nullable=False)
    payout = db.Column(db.Float, default=0.0)
    currency = db.Column(db.String(10), nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
class BlackjackGame(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    bet_amount = db.Column(db.Float, nullable=False)
    client_seed = db.Column(db.String(64), nullable=False)
    server_seed = db.Column(db.String(64), nullable=False)
    server_seed_hash = db.Column(db.String(64), nullable=False)
    player_hand = db.Column(db.String(128), nullable=False)  # Comma-separated cards
    dealer_hand = db.Column(db.String(128), nullable=False)  # Comma-separated cards
    player_score = db.Column(db.Integer, nullable=False)
    dealer_score = db.Column(db.Integer, nullable=False)
    game_status = db.Column(db.String(20), nullable=False)  # 'win', 'lose', 'push', 'blackjack'
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
    
class ResponsibleGaming(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    daily_deposit_limit = db.Column(db.Float, default=1.0)  # BTC
    weekly_deposit_limit = db.Column(db.Float, default=5.0)  # BTC
    monthly_deposit_limit = db.Column(db.Float, default=10.0)  # BTC
    session_reminder = db.Column(db.Integer, default=60)  # Minutes
    self_exclusion_until = db.Column(db.DateTime, nullable=True)
    is_permanently_excluded = db.Column(db.Boolean, default=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
class DepositLimitPeriod(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    period_type = db.Column(db.String(10), nullable=False)  # 'daily', 'weekly', 'monthly'
    start_date = db.Column(db.DateTime, nullable=False, default=datetime.utcnow)
    end_date = db.Column(db.DateTime, nullable=False)
    total_deposited = db.Column(db.Float, default=0.0)  # BTC
    
class Bonus(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    bonus_type = db.Column(db.String(20), nullable=False)  # 'deposit', 'free_spin', 'cashback', 'welcome'
    amount = db.Column(db.Float, nullable=False)  # Bonus amount in BTC or number of free spins
    currency = db.Column(db.String(10), nullable=False, default='BTC')
    wagering_requirement = db.Column(db.Float, default=0.0)  # Multiplier (e.g., 30x means bet 30x bonus amount)
    wagered_amount = db.Column(db.Float, default=0.0)  # Amount already wagered
    game_restrictions = db.Column(db.String(256), nullable=True)  # Comma-separated list of allowed games
    is_active = db.Column(db.Boolean, default=True)
    is_claimed = db.Column(db.Boolean, default=False)
    expires_at = db.Column(db.DateTime, nullable=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
class Promotion(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    description = db.Column(db.Text, nullable=False)
    bonus_type = db.Column(db.String(20), nullable=False)  # 'deposit', 'free_spin', 'cashback', 'welcome'
    bonus_amount = db.Column(db.Float, nullable=False)  # Bonus amount in BTC or number of free spins
    currency = db.Column(db.String(10), nullable=False, default='BTC')
    min_deposit = db.Column(db.Float, nullable=True)  # Minimum deposit to qualify
    wagering_requirement = db.Column(db.Float, default=30.0)  # Multiplier
    promo_code = db.Column(db.String(20), nullable=True, unique=True)
    game_restrictions = db.Column(db.String(256), nullable=True)  # Comma-separated list of allowed games
    is_active = db.Column(db.Boolean, default=True)
    start_date = db.Column(db.DateTime, default=datetime.utcnow)
    end_date = db.Column(db.DateTime, nullable=True)
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
    
def evaluate_slot_symbols(symbols):
    """Evaluate slot machine symbols and return multiplier"""
    # Define base multipliers for different combinations
    multipliers = {
        '7,7,7': 10.0,       # Jackpot
        'BAR,BAR,BAR': 5.0,  # Triple BAR
        'BELL,BELL,BELL': 4.0, # Triple Bell
        'CHERRY,CHERRY,CHERRY': 3.0, # Triple Cherry
        'LEMON,LEMON,LEMON': 2.0, # Triple Lemon
    }
    
    # Create a key for lookup
    symbols_key = ','.join(symbols)
    
    # Check if we have an exact match
    if symbols_key in multipliers:
        return multipliers[symbols_key]
    
    # Check for any two matching symbols (1.5x)
    symbol_counts = {}
    for symbol in symbols:
        symbol_counts[symbol] = symbol_counts.get(symbol, 0) + 1
    
    for symbol, count in symbol_counts.items():
        if count >= 2:
            return 1.5
    
    # No matches
    return 0.0
    
def create_deck():
    """Create a standard 52-card deck"""
    suits = ['Hearts', 'Diamonds', 'Clubs', 'Spades']
    values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A']
    deck = [f"{value} of {suit}" for suit in suits for value in values]
    return deck
    
def shuffle_deck(deck, seed):
    """Deterministically shuffle the deck based on a seed"""
    # Create a list of indices and sort them based on hash values
    shuffled_deck = deck.copy()
    indices = list(range(len(deck)))
    
    # Convert seed to a list of integers (deterministic shuffle)
    seed_values = []
    for i in range(0, len(seed), 8):
        # Take 8 characters from seed and convert to integer
        if i + 8 <= len(seed):
            seed_values.append(int(seed[i:i+8], 16))
        else:
            seed_values.append(int(seed[i:], 16))
    
    # Shuffle indices based on seed values
    for i in range(len(indices) - 1, 0, -1):
        # Use seed_values[i % len(seed_values)] as a source of randomness
        j = seed_values[i % len(seed_values)] % (i + 1)
        indices[i], indices[j] = indices[j], indices[i]
    
    # Create new deck based on shuffled indices
    for i in range(len(shuffled_deck)):
        shuffled_deck[i] = deck[indices[i]]
    
    return shuffled_deck
    
def card_value(card):
    """Get the numerical value of a card"""
    # Extract the value part (e.g., "10 of Hearts" -> "10")
    value = card.split(' of ')[0]
    
    # Face cards worth 10
    if value in ['J', 'Q', 'K']:
        return 10
    # Ace is worth 11 initially (will be adjusted to 1 if needed)
    elif value == 'A':
        return 11
    # Number cards worth their number
    else:
        return int(value)
        
def calculate_score(hand):
    """Calculate the score of a blackjack hand, accounting for aces"""
    score = 0
    aces = 0
    
    for card in hand:
        card_val = card_value(card)
        if card_val == 11:  # It's an ace
            aces += 1
        score += card_val
    
    # Adjust aces if score is over 21
    while score > 21 and aces > 0:
        score -= 10  # Change an ace from 11 to 1
        aces -= 1
        
    return score
    
def determine_winner(player_score, dealer_score, player_hand_length, dealer_hand_length):
    """Determine the winner of a blackjack hand"""
    
    # Player busts
    if player_score > 21:
        return 'lose'
        
    # Dealer busts
    if dealer_score > 21:
        return 'win'
        
    # Player has blackjack
    if player_score == 21 and player_hand_length == 2:
        # Check if dealer also has blackjack
        if dealer_score == 21 and dealer_hand_length == 2:
            return 'push'  # Both have blackjack, it's a push
        return 'blackjack'  # Player has blackjack, dealer doesn't
        
    # Compare scores
    if player_score > dealer_score:
        return 'win'
    elif player_score < dealer_score:
        return 'lose'
    else:
        return 'push'  # Scores are equal
    
def calculate_payout(game_status, bet_amount):
    """Calculate payout based on game status"""
    
    payouts = {
        'blackjack': 2.5,  # 3:2 payout for blackjack
        'win': 2.0,        # 1:1 payout for win
        'push': 1.0,       # Return bet for push
        'lose': 0.0        # No payout for loss
    }
    
    multiplier = payouts.get(game_status, 0.0)
    return bet_amount * multiplier
    
def get_recent_blackjack_games():
    """Get the user's recent blackjack games"""
    if 'user_id' not in session:
        return []
    
    return BlackjackGame.query.filter_by(user_id=session['user_id']).order_by(BlackjackGame.created_at.desc()).limit(10).all()

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
    """Get the user's recent dice bets"""
    if 'user_id' not in session:
        return []
    
    return DiceBet.query.filter_by(user_id=session['user_id']).order_by(DiceBet.created_at.desc()).limit(10).all()

def get_recent_spins():
    """Get the user's recent slot machine spins"""
    if 'user_id' not in session:
        return []
    
    return SlotBet.query.filter_by(user_id=session['user_id']).order_by(SlotBet.created_at.desc()).limit(10).all()

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

@app.route('/games/slots')
def slots_game():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    wallets = get_user_wallets()
    recent_spins = get_recent_spins()
    
    return render_template('slots.html', wallets=wallets, recent_spins=recent_spins)

@app.route('/games/blackjack')
def blackjack_game():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    wallets = get_user_wallets()
    recent_games = get_recent_blackjack_games()
    
    return render_template('blackjack.html', wallets=wallets, recent_games=recent_games)

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

@app.route('/games/slots/spin', methods=['POST'])
def play_slots():
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
    
    # Available symbols for slot machine
    symbols = ['7', 'BAR', 'BELL', 'CHERRY', 'LEMON']
    
    # Generate three random symbols based on the combined seed
    combined_seed = server_seed + client_seed
    hash_value = hashlib.sha256(combined_seed.encode()).hexdigest()
    
    result_symbols = []
    for i in range(3):
        # Use 8 hex chars (32 bits) for each reel
        slice_value = int(hash_value[i*8:(i+1)*8], 16)
        symbol_index = slice_value % len(symbols)
        result_symbols.append(symbols[symbol_index])
    
    # Determine win and multiplier
    multiplier = evaluate_slot_symbols(result_symbols)
    payout = bet_amount * multiplier
    is_win = payout > 0
    
    # Create slot bet record
    new_bet = SlotBet()
    new_bet.user_id = session['user_id']
    new_bet.bet_amount = bet_amount
    new_bet.client_seed = client_seed
    new_bet.server_seed = server_seed
    new_bet.server_seed_hash = server_seed_hash
    new_bet.result_symbols = ','.join(result_symbols)
    new_bet.multiplier = multiplier
    new_bet.payout = payout
    new_bet.currency = wallet.currency
    
    # Update wallet balance
    wallet.balance -= bet_amount
    if is_win:
        wallet.balance += payout
    
    db.session.add(new_bet)
    db.session.commit()
    
    return json.dumps({
        'id': new_bet.id,
        'bet_amount': new_bet.bet_amount,
        'client_seed': new_bet.client_seed,
        'server_seed': new_bet.server_seed,
        'server_seed_hash': new_bet.server_seed_hash,
        'result_symbols': result_symbols,
        'multiplier': multiplier,
        'payout': payout,
        'currency': wallet.currency,
        'created_at': new_bet.created_at.strftime('%H:%M:%S'),
        'wallet_balance': wallet.balance,
        'is_win': is_win,
        'hash': hash_value[:16]  # Return part of the hash for verification
    })

@app.route('/games/blackjack/deal', methods=['POST'])
def blackjack_deal():
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
    
    # Create and shuffle the deck
    deck = create_deck()
    shuffled_deck = shuffle_deck(deck, server_seed + client_seed)
    
    # Deal initial cards
    player_hand = [shuffled_deck.pop(0), shuffled_deck.pop(0)]
    dealer_hand = [shuffled_deck.pop(0), shuffled_deck.pop(0)]
    
    # Calculate scores
    player_score = calculate_score(player_hand)
    dealer_score = calculate_score(dealer_hand)
    
    # Check for blackjack
    game_status = 'in_progress'
    payout = 0.0
    
    # If player has blackjack, game ends immediately
    if player_score == 21:
        # Check if dealer also has blackjack
        if dealer_score == 21:
            game_status = 'push'
            payout = bet_amount  # Return bet
        else:
            game_status = 'blackjack'
            payout = bet_amount * 2.5  # 3:2 payout for blackjack
            
        # Update wallet balance for immediate result
        wallet.balance -= bet_amount
        wallet.balance += payout
    else:
        # Game continues, deduct bet from wallet
        wallet.balance -= bet_amount
    
    # Create game record
    game = BlackjackGame()
    game.user_id = session['user_id']
    game.bet_amount = bet_amount
    game.client_seed = client_seed
    game.server_seed = server_seed
    game.server_seed_hash = server_seed_hash
    game.player_hand = ','.join(player_hand)
    game.dealer_hand = ','.join(dealer_hand)
    game.player_score = player_score
    game.dealer_score = dealer_score
    game.game_status = game_status
    game.payout = payout
    game.currency = wallet.currency
    
    db.session.add(game)
    db.session.commit()
    
    # Return initial game state
    return json.dumps({
        'game_id': game.id,
        'bet_amount': game.bet_amount,
        'player_hand': player_hand,
        'dealer_hand': dealer_hand,
        'dealer_hand_hidden': game_status == 'in_progress',  # Hide second card if game continues
        'player_score': player_score,
        'dealer_score': dealer_score if game_status != 'in_progress' else dealer_hand[0].split(' of ')[0],
        'game_status': game_status,
        'payout': payout,
        'currency': wallet.currency,
        'wallet_balance': wallet.balance
    })

@app.route('/games/blackjack/action', methods=['POST'])
def blackjack_action():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    data = request.get_json()
    game_id = int(data.get('game_id', 0))
    action = data.get('action', '')  # 'hit', 'stand', 'double'
    
    # Find the game
    game = BlackjackGame.query.get(game_id)
    
    # Validate game
    if not game or game.user_id != session['user_id']:
        return json.dumps({'error': 'Invalid game'})
    
    if game.game_status != 'in_progress':
        return json.dumps({'error': 'Game is already complete'})
    
    # Get wallet
    wallet = Wallet.query.filter_by(user_id=session['user_id'], currency=game.currency).first()
    if not wallet:
        return json.dumps({'error': 'Wallet not found'})
    
    # Convert stored hands back to arrays
    player_hand = game.player_hand.split(',')
    dealer_hand = game.dealer_hand.split(',')
    
    # Create and shuffle the deck again using the same seed
    deck = create_deck()
    shuffled_deck = shuffle_deck(deck, game.server_seed + game.client_seed)
    
    # Remove cards already dealt
    for card in player_hand + dealer_hand:
        shuffled_deck.remove(card)
    
    # Handle player's action
    if action == 'hit':
        # Deal another card to player
        new_card = shuffled_deck.pop(0)
        player_hand.append(new_card)
        
        # Update player score
        player_score = calculate_score(player_hand)
        game.player_score = player_score
        
        # Check if player busts
        if player_score > 21:
            game.game_status = 'lose'
            game.payout = 0.0
        else:
            game.game_status = 'in_progress'
            
    elif action == 'stand':
        # Dealer's turn
        player_score = game.player_score
        dealer_score = calculate_score(dealer_hand)
        
        # Dealer draws until 17 or higher
        while dealer_score < 17:
            new_card = shuffled_deck.pop(0)
            dealer_hand.append(new_card)
            dealer_score = calculate_score(dealer_hand)
        
        # Determine winner
        game_status = determine_winner(player_score, dealer_score, len(player_hand), len(dealer_hand))
        payout = calculate_payout(game_status, game.bet_amount)
        
        # Update game and wallet
        game.game_status = game_status
        game.dealer_score = dealer_score
        game.payout = payout
        
        wallet.balance += payout
        
    elif action == 'double':
        # Validate balance for double
        if wallet.balance < game.bet_amount:
            return json.dumps({'error': 'Insufficient balance to double'})
        
        # Double the bet
        wallet.balance -= game.bet_amount
        game.bet_amount *= 2
        
        # Deal one more card to player
        new_card = shuffled_deck.pop(0)
        player_hand.append(new_card)
        
        # Update player score
        player_score = calculate_score(player_hand)
        game.player_score = player_score
        
        # Check if player busts
        if player_score > 21:
            game.game_status = 'lose'
            game.payout = 0.0
        else:
            # Player stands after doubling (dealer's turn handled after this response)
            game.game_status = 'in_progress'
    
    # Update hands in database
    game.player_hand = ','.join(player_hand)
    game.dealer_hand = ','.join(dealer_hand)
    
    db.session.commit()
    
    # Return updated game state
    return json.dumps({
        'game_id': game.id,
        'bet_amount': game.bet_amount,
        'player_hand': player_hand,
        'dealer_hand': dealer_hand,
        'player_score': game.player_score,
        'dealer_score': game.dealer_score,
        'game_status': game.game_status,
        'payout': game.payout,
        'currency': game.currency,
        'wallet_balance': wallet.balance
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

@app.route('/profile')
def profile():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    user = User.query.get(session['user_id'])
    if not user:
        return redirect(url_for('logout'))
    
    wallets = get_user_wallets()
    
    # Count total bets and wins for stats
    dice_bets = DiceBet.query.filter_by(user_id=user.id).all()
    slot_bets = SlotBet.query.filter_by(user_id=user.id).all()
    blackjack_games = BlackjackGame.query.filter_by(user_id=user.id).all()
    
    total_bets = len(dice_bets) + len(slot_bets) + len(blackjack_games)
    
    # Count wins
    dice_wins = sum(1 for bet in dice_bets if bet.payout > bet.bet_amount)
    slot_wins = sum(1 for bet in slot_bets if bet.payout > bet.bet_amount)
    blackjack_wins = sum(1 for game in blackjack_games if game.payout > game.bet_amount)
    
    total_wins = dice_wins + slot_wins + blackjack_wins
    
    # Mock activity log for demo
    activities = [
        {'created_at': datetime.utcnow() - timedelta(hours=1), 'activity_type': 'Login', 'details': 'Successful login from IP 192.168.1.1'},
        {'created_at': datetime.utcnow() - timedelta(hours=2), 'activity_type': 'Game Play', 'details': 'Played Dice Game'},
        {'created_at': datetime.utcnow() - timedelta(hours=3), 'activity_type': 'Deposit', 'details': 'Deposited 0.1 BTC'},
        {'created_at': datetime.utcnow() - timedelta(days=1), 'activity_type': 'Withdrawal', 'details': 'Withdrew 0.05 BTC'},
        {'created_at': datetime.utcnow() - timedelta(days=2), 'activity_type': 'Password Change', 'details': 'Changed account password'}
    ]
    
    return render_template('profile.html', user=user, wallets=wallets, total_bets=total_bets, 
                          total_wins=total_wins, activities=activities)

@app.route('/responsible-gaming', methods=['GET', 'POST'])
def responsible_gaming():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    user = User.query.get(session['user_id'])
    if not user:
        return redirect(url_for('logout'))
    
    # Get or create responsible gaming settings
    rg_settings = ResponsibleGaming.query.filter_by(user_id=user.id).first()
    if not rg_settings:
        rg_settings = ResponsibleGaming()
        rg_settings.user_id = user.id
        db.session.add(rg_settings)
        db.session.commit()
    
    if request.method == 'POST':
        action = request.form.get('action')
        
        if action == 'update_limits':
            # Update deposit limits
            daily_limit = float(request.form.get('daily_limit', 1.0))
            weekly_limit = float(request.form.get('weekly_limit', 5.0))
            monthly_limit = float(request.form.get('monthly_limit', 10.0))
            
            rg_settings.daily_deposit_limit = daily_limit
            rg_settings.weekly_deposit_limit = weekly_limit
            rg_settings.monthly_deposit_limit = monthly_limit
            
            db.session.commit()
            flash('Deposit limits updated successfully', 'success')
            
        elif action == 'update_session':
            # Update session reminder
            session_reminder = int(request.form.get('session_reminder', 60))
            rg_settings.session_reminder = session_reminder
            
            db.session.commit()
            flash('Session reminder updated successfully', 'success')
            
        elif action == 'self_exclusion':
            # Self-exclusion
            exclusion_period = request.form.get('exclusion_period')
            
            if exclusion_period == 'permanent':
                rg_settings.is_permanently_excluded = True
                rg_settings.self_exclusion_until = None
                
                db.session.commit()
                
                flash('Your account has been permanently excluded. Contact support for assistance.', 'warning')
                session.clear()
                return redirect(url_for('home'))
            elif exclusion_period:
                try:
                    days = int(exclusion_period)
                    rg_settings.self_exclusion_until = datetime.utcnow() + timedelta(days=days)
                    rg_settings.is_permanently_excluded = False
                    
                    db.session.commit()
                    
                    flash(f'Your account has been temporarily excluded for {days} days.', 'warning')
                    session.clear()
                    return redirect(url_for('home'))
                except (ValueError, TypeError):
                    flash('Invalid exclusion period selected', 'danger')
    
    # Create mock data for deposit usage
    daily_usage = 0.25
    weekly_usage = 3.25
    monthly_usage = 8.5
    
    daily_percent = (daily_usage / rg_settings.daily_deposit_limit) * 100
    weekly_percent = (weekly_usage / rg_settings.weekly_deposit_limit) * 100
    monthly_percent = (monthly_usage / rg_settings.monthly_deposit_limit) * 100
    
    return render_template('responsible_gaming.html', 
                          user=user, 
                          rg_settings=rg_settings,
                          daily_usage=daily_usage,
                          weekly_usage=weekly_usage,
                          monthly_usage=monthly_usage,
                          daily_percent=daily_percent,
                          weekly_percent=weekly_percent,
                          monthly_percent=monthly_percent)

@app.route('/bonuses')
def bonuses():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    user = User.query.get(session['user_id'])
    if not user:
        return redirect(url_for('logout'))
    
    # Get the user's active bonuses
    active_bonuses = Bonus.query.filter_by(user_id=user.id, is_active=True).all()
    
    # Get available promotions
    available_promotions = Promotion.query.filter_by(is_active=True).all()
    valid_promotions = []
    
    for promo in available_promotions:
        # Check if promotion is still valid (not expired)
        if promo.end_date is None or promo.end_date > datetime.utcnow():
            valid_promotions.append(promo)
    
    return render_template('bonuses.html', 
                          user=user, 
                          active_bonuses=active_bonuses, 
                          available_promotions=valid_promotions)

@app.route('/claim-bonus/<int:promotion_id>', methods=['POST'])
def claim_bonus(promotion_id):
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    user = User.query.get(session['user_id'])
    if not user:
        return redirect(url_for('logout'))
    
    # Find the promotion
    promotion = Promotion.query.get_or_404(promotion_id)
    
    # Check if promotion is active
    if not promotion.is_active:
        flash('This promotion is no longer available', 'danger')
        return redirect(url_for('bonuses'))
    
    # Check if promotion is still valid (not expired)
    if promotion.end_date and promotion.end_date < datetime.utcnow():
        flash('This promotion has expired', 'danger')
        return redirect(url_for('bonuses'))
    
    # Check if the user already has this bonus
    existing_bonus = Bonus.query.filter_by(
        user_id=user.id,
        bonus_type=promotion.bonus_type,
        is_active=True
    ).first()
    
    if existing_bonus:
        flash('You already have an active bonus of this type', 'warning')
        return redirect(url_for('bonuses'))
    
    # Create the new bonus
    new_bonus = Bonus()
    new_bonus.user_id = user.id
    new_bonus.bonus_type = promotion.bonus_type
    new_bonus.amount = promotion.bonus_amount
    new_bonus.currency = promotion.currency
    new_bonus.wagering_requirement = promotion.wagering_requirement
    new_bonus.game_restrictions = promotion.game_restrictions
    new_bonus.is_active = True
    new_bonus.is_claimed = True
    new_bonus.expires_at = datetime.utcnow() + timedelta(days=7)  # Bonus expires in 7 days
    
    db.session.add(new_bonus)
    db.session.commit()
    
    flash(f'You have successfully claimed the {promotion.name} bonus!', 'success')
    return redirect(url_for('bonuses'))

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