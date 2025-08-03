# 🎮 Fifteen Puzzle Game

A sophisticated web-based implementation of the classic 15-puzzle game with advanced algorithms, user authentication, and administrative features.

[![Live Demo](https://img.shields.io/badge/Live%20Demo-CODD%20Server-blue)](https://codd.cs.gsu.edu/~sjuyal1/Puzzle_Game/fifteen.html)
[![GitHub](https://img.shields.io/badge/GitHub-Repository-green)](https://github.com/Gowtham0436/webpro.git)

## 🌟 Features

### 🎯 Core Game Features
- **Mathematical Solvability Algorithm**: Ensures every generated puzzle is solvable
- **Responsive Design**: Works seamlessly on desktop and mobile devices
- **Smooth Animations**: CSS3 transitions for enhanced user experience
- **Timer & Move Counter**: Track your performance in real-time
- **Multiple Difficulty Levels**: Customizable puzzle complexity

### 🔐 User Management
- **Secure Authentication**: Registration and login system
- **User Profiles**: Personal statistics and preferences
- **Session Management**: Secure PHP session handling
- **Password Encryption**: Bcrypt hashing for security

### 📊 Statistics & Analytics
- **Real-time Game Stats**: Track moves, time, and completion rates
- **Personal Leaderboards**: Compare your best times
- **Global Rankings**: Compete with other players
- **Performance Analytics**: Detailed game statistics

### ⚙️ Administrative Panel
- **Content Management System**: Full CRUD operations
- **User Management**: Admin control over user accounts
- **Game Configuration**: Customize game settings
- **Statistics Dashboard**: Monitor platform usage

## 🚀 Live Demo

**🎮 Play the Game**: [https://codd.cs.gsu.edu/~sjuyal1/Puzzle_Game/fifteen.html](https://codd.cs.gsu.edu/~sjuyal1/Puzzle_Game/fifteen.html)

**📋 Project Info**: [https://codd.cs.gsu.edu/~sjuyal1/Puzzle_Game/info.html](https://codd.cs.gsu.edu/~sjuyal1/Puzzle_Game/info.html)

**🔧 Admin Panel**: [https://codd.cs.gsu.edu/~sjuyal1/Puzzle_Game/admin_dashboard.html](https://codd.cs.gsu.edu/~sjuyal1/Puzzle_Game/admin_dashboard.html)

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **API**: RESTful architecture
- **Security**: Bcrypt password hashing, CSRF protection
- **Design**: Responsive CSS Grid/Flexbox

## 📦 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Quick Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/Gowtham0436/webpro.git
   cd webpro
   ```

2. **Configure Database**
   ```bash
   # Update database credentials in config.php
   cp config.php.example config.php
   # Edit config.php with your database details
   ```

3. **Setup Database**
   ```bash
   # Import the database schema
   mysql -u username -p database_name < database_setup.sql
   
   # Or run the setup script
   php setup_database.php
   ```

4. **Set Permissions**
   ```bash
   chmod 755 *.html *.php
   chmod 644 *.css *.js
   ```

5. **Access the Application**
   - Navigate to `http://your-server.com/path-to-project/fifteen.html`

### CODD Server Setup
```bash
# Extract files to your public_html directory
cd ~/public_html/
# Upload project files here
# Update config.php with CODD database credentials
# Access via: https://codd.cs.gsu.edu/~username/Puzzle_Game/fifteen.html
```

## 🎮 How to Play

1. **Objective**: Arrange numbered tiles in order from 1-15
2. **Movement**: Click tiles adjacent to the empty space
3. **Goal**: Complete the puzzle in minimum moves and time
4. **Features**: Use shuffle for new games, reset to start over

## 🔑 Default Login Credentials

**Administrator Account:**
- Username: `admin`
- Password: `admin123`

**Player Account:**
- Username: `player1` 
- Password: `player123`

## 📁 Project Structure

```
Puzzle_Game/
├── 🎮 Game Files
│   ├── fifteen.html          # Main game interface
│   ├── fifteen.js            # Game logic & algorithms
│   └── styles.css            # Game styling
├── 🔐 Authentication
│   ├── auth.php              # Authentication logic
│   ├── login.html            # Login interface
│   └── register.html         # Registration interface
├── ⚙️ Admin Panel
│   ├── admin_dashboard.html  # Admin interface
│   ├── admin_dashboard.js    # Admin functionality
│   └── admin_styles.css      # Admin styling
├── 📊 API & Backend
│   ├── api.php               # Main API endpoint
│   ├── game_stats.php        # Statistics handling
│   ├── leaderboard_api.php   # Leaderboard functionality
│   └── user_preferences_api.php # User settings
├── 🗄️ Database
│   ├── config.php            # Database configuration
│   ├── setup_database.php    # Database setup script
│   └── database_dump.sql     # Sample database
├── 📋 Documentation
│   ├── README.md             # This file
│   └── info.html            # Project information
└── 🧪 Testing
    ├── test_auth.php         # Authentication tests
    ├── test_game.php         # Game logic tests
    └── debug_profile.php     # Performance profiling
```

## 🧮 Algorithm Details

### Solvability Check
The game implements a mathematical algorithm to ensure puzzle solvability:
- Counts inversion pairs in the puzzle state
- Considers blank tile position for even-width grids
- Guarantees every generated puzzle has a solution

### Performance Optimization
- Efficient tile movement detection
- Optimized DOM manipulation
- Minimal API calls for better performance

## 🛡️ Security Features

- **Input Validation**: All user inputs are sanitized
- **SQL Injection Prevention**: Prepared statements used
- **XSS Protection**: Output encoding implemented
- **CSRF Protection**: Token-based request validation
- **Secure Sessions**: Proper session management
- **Password Security**: Bcrypt hashing with salt

## 🎨 Design Philosophy

- **Mobile-First**: Responsive design for all devices
- **Accessibility**: ARIA labels and keyboard navigation
- **User Experience**: Intuitive interface with visual feedback
- **Performance**: Optimized loading and smooth animations

## 👥 Team

**Team Name**: Flash Coders

**Team Members**:
- **Silvia Juyal** - Chief Programmer and Designer
- **Satchigolla Gowtham Karthikeya** - Developer and Tester

## 📈 Project Resources

- **📹 Presentation Video**: [YouTube Demo](https://www.youtube.com/watch?v=rXQJSoYruYI)
- **📊 PowerPoint**: [Project Presentation](https://www.youtube.com/watch?v=rXQJSoYruYI)
- **🌐 Live Demo**: [CODD Server](https://codd.cs.gsu.edu/~sjuyal1/Puzzle_Game/)
- **💻 Source Code**: [GitHub Repository](https://github.com/Gowtham0436/webpro.git)

## 🚀 API Endpoints

### Game API
- `GET /api.php?action=getStats` - Retrieve game statistics
- `POST /api.php?action=saveGame` - Save game progress
- `GET /leaderboard_api.php` - Get leaderboard data

### User API
- `POST /auth.php?action=login` - User authentication
- `POST /auth.php?action=register` - User registration
- `GET /user_preferences_api.php` - User settings

### Admin API
- `GET /admin_api.php?action=getUsers` - Manage users
- `POST /admin_api.php?action=updateSettings` - Update configurations

## 🧪 Testing

Run the test suite:
```bash
php test_auth.php      # Authentication tests
php test_game.php      # Game logic tests
php test_connection.php # Database connectivity
```

## 📝 Configuration

### Database Setup (config.php)
```php
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'your_username');
define('DB_PASSWORD', 'your_password');
define('DB_DATABASE', 'fifteen_puzzle');
```

### Game Settings
- Modify difficulty levels in `fifteen.js`
- Customize themes in `styles.css`
- Adjust API endpoints in `api-client.js`

## 🐛 Troubleshooting

**Common Issues:**

1. **Database Connection Failed**
   - Check config.php credentials
   - Verify MySQL service is running
   - Confirm database exists

2. **Permission Denied**
   - Set proper file permissions: `chmod 755 *.php`
   - Check web server configuration

3. **Game Won't Load**
   - Verify all files are uploaded
   - Check browser console for errors
   - Ensure JavaScript is enabled

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature-name`
3. Commit changes: `git commit -am 'Add feature'`
4. Push to branch: `git push origin feature-name`
5. Submit a Pull Request

## 📄 License

This project is created for educational purposes as part of a web development course.

## 🙏 Acknowledgments

- Classic 15-puzzle game inspiration
- Mathematical algorithms for puzzle solvability
- Modern web development best practices
- Georgia State University Computer Science Department

---

**🎮 Ready to play?** [Start the Fifteen Puzzle Game!](https://codd.cs.gsu.edu/~sjuyal1/Puzzle_Game/fifteen.html)

---

*Last Updated: August 2025 | Built with ❤️ by Flash Coders Team*
