# Xaxino Ultimate Casino Platform

A comprehensive cryptocurrency casino platform featuring multiple games, wallet integration, KYC compliance, and responsible gaming features.

## 🎰 Features

### Gaming
- **Dice Game**: Provably fair dice rolling with customizable odds
- **Slot Machine**: Multi-symbol slot games with various winning combinations
- **Blackjack**: Classic 21 card game with dealer AI
- **Featured Slots**: Collection of themed slot machines

### Security & Compliance
- **KYC Verification**: Document upload and verification system
- **Two-Factor Authentication**: Google 2FA integration
- **Provably Fair Gaming**: Cryptographic proof of game fairness
- **Responsible Gaming**: Deposit limits, session timers, self-exclusion

### Cryptocurrency Support
- **Multi-Currency Wallets**: BTC and ETH support
- **On-Chain Payouts**: Direct blockchain transactions
- **Wallet Management**: Secure key generation and storage

### User Experience
- **VIP Program**: Tiered rewards and exclusive benefits
- **Bonus System**: Welcome bonuses, cashback, and promotions
- **Real-time Gaming**: Live game results and balance updates
- **Mobile Responsive**: Optimized for all device types

## 🛠 Technology Stack

### Backend
- **Python Flask**: Web framework
- **SQLAlchemy**: Database ORM
- **PostgreSQL**: Primary database
- **SQLite**: Development database fallback

### Frontend
- **HTML5/CSS3**: Modern web standards
- **Bootstrap**: Responsive framework
- **JavaScript**: Interactive components
- **Jinja2**: Template engine

### Blockchain Integration
- **Web3.py**: Ethereum interaction
- **Bitcoin RPC**: Bitcoin network integration
- **Smart Contracts**: Solidity-based payout contracts

## 🚀 Quick Start

### Prerequisites
- Python 3.11+
- PostgreSQL (optional, uses SQLite by default)
- Node.js (for frontend dependencies)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/mattciolino/XaxinoCasino.git
   cd XaxinoCasino
   ```

2. **Set up virtual environment**
   ```bash
   python -m venv venv
   source venv/bin/activate  # On Windows: venv\Scripts\activate
   ```

3. **Install dependencies**
   ```bash
   pip install -r requirements.txt
   ```

4. **Configure environment variables**
   ```bash
   export DATABASE_URL="postgresql://user:pass@localhost/casino"
   export SESSION_SECRET="your-secret-key"
   ```

5. **Initialize database**
   ```bash
   python -c "from main import app, db; app.app_context().push(); db.create_all()"
   ```

6. **Run the application**
   ```bash
   python main.py
   ```

## 📁 Project Structure

```
XaxinoCasino/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # Game and user controllers
│   │   └── Middleware/      # Authentication and admin middleware
│   ├── Models/              # Database models
│   └── Services/            # Blockchain and game services
├── contracts/               # Solidity smart contracts
├── database/
│   ├── migrations/          # Database schema changes
│   └── seeders/            # Initial data setup
├── public/                 # Static assets
├── resources/
│   ├── js/                 # JavaScript components
│   └── views/              # HTML templates
├── routes/                 # URL routing
├── storage/                # File storage and ABIs
├── templates/              # Jinja2 templates
└── main.py                 # Application entry point
```

## 🎮 Game Features

### Dice Game
- **Provably Fair**: Uses cryptographic seeds for transparency
- **Flexible Betting**: Variable bet amounts and odds
- **Real-time Results**: Instant game outcomes
- **Verification**: Players can verify game fairness

### Slot Machine
- **Multiple Symbols**: 7, BAR, BELL, CHERRY, LEMON
- **Various Payouts**: Different winning combinations
- **Progressive Features**: Bonus rounds and multipliers
- **Theme Variations**: Multiple slot themes

### Blackjack
- **Standard Rules**: Classic 21 gameplay
- **Dealer AI**: Automated dealer following casino rules
- **Side Bets**: Insurance and other betting options
- **Card Counting Protection**: Shuffle tracking prevention

## 💰 VIP Program

### Tier Benefits
- **Bronze**: 0.5% cashback
- **Silver**: 1% cashback + monthly bonus
- **Gold**: 1.5% cashback + dedicated support
- **Platinum**: 2% cashback + exclusive events
- **Diamond**: 2.5% cashback + personal manager

## 🔒 Security Features

### Data Protection
- **Encrypted Storage**: Sensitive data encryption
- **Secure Sessions**: Session management
- **Input Validation**: XSS and injection prevention
- **Rate Limiting**: API abuse protection

### Compliance
- **AML Compliance**: Anti-money laundering checks
- **Transaction Monitoring**: Suspicious activity detection
- **Audit Trails**: Complete transaction logging
- **Regulatory Reporting**: Compliance documentation

## 🌐 API Documentation

### Authentication Endpoints
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration
- `POST /api/auth/2fa/setup` - Enable 2FA
- `POST /api/auth/logout` - User logout

### Game Endpoints
- `POST /api/games/dice/play` - Place dice bet
- `POST /api/games/slots/spin` - Spin slot machine
- `POST /api/games/blackjack/deal` - Start blackjack game
- `POST /api/games/blackjack/action` - Player action

### Wallet Endpoints
- `GET /api/wallets` - Get user wallets
- `POST /api/wallets/deposit` - Generate deposit address
- `POST /api/wallets/withdraw` - Request withdrawal

## 📊 Database Schema

### Core Tables
- **users**: User accounts and authentication
- **wallets**: Cryptocurrency wallet data
- **dice_bets**: Dice game history
- **slot_bets**: Slot machine spins
- **blackjack_games**: Blackjack game records
- **kyc_documents**: KYC verification files

## 🎨 Frontend Components

### Game Interface
- **Live Balance**: Real-time balance updates
- **Game History**: Recent game results
- **Verification Tools**: Fairness verification
- **Mobile Controls**: Touch-optimized interface

### Admin Panel
- **User Management**: Account administration
- **Game Monitoring**: Live game statistics
- **KYC Review**: Document verification
- **Financial Reports**: Revenue analytics

## 🔧 Configuration

### Environment Variables
```bash
DATABASE_URL=postgresql://localhost/casino
SESSION_SECRET=your-secret-key
BITCOIN_RPC_URL=http://localhost:8332
ETHEREUM_RPC_URL=http://localhost:8545
MAIL_SERVER=smtp.gmail.com
MAIL_USERNAME=noreply@casino.com
MAIL_PASSWORD=app-password
```

### Database Configuration
```python
SQLALCHEMY_DATABASE_URI = os.environ.get('DATABASE_URL')
SQLALCHEMY_TRACK_MODIFICATIONS = False
SQLALCHEMY_ENGINE_OPTIONS = {
    'pool_pre_ping': True,
    'pool_recycle': 300,
}
```

## 🧪 Testing

### Run Tests
```bash
python -m pytest tests/
```

### Test Coverage
```bash
python -m pytest --cov=app tests/
```

## 📦 Deployment

### Docker Deployment
```bash
docker build -t xaxino-casino .
docker run -p 5000:5000 xaxino-casino
```

### Production Setup
1. Use PostgreSQL for production database
2. Configure Redis for session storage
3. Set up SSL certificates
4. Enable monitoring and logging
5. Configure backup systems

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new features
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## ⚠️ Disclaimer

This software is for educational and demonstration purposes. Ensure compliance with local gambling laws and regulations before deploying in production. The developers are not responsible for any legal issues arising from the use of this software.

## 📞 Support

For questions and support, please open an issue in the GitHub repository or contact the development team.

---

**Note**: This is a demonstration casino platform. Always implement proper security measures, compliance checks, and legal requirements before deploying to production environments.
