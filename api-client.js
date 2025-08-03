// API Client for Fifteen Puzzle Game
class PuzzleAPI {
    constructor() {
        this.baseURL = window.location.origin; // Adjust this to your server URL
        this.currentUser = null;
        this.gameStartTime = null;
        this.moveCount = 0;
    }

    // Authentication methods
    async login(username, password) {
        try {
            const response = await fetch(`${this.baseURL}/api/auth/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ username, password }),
                credentials: 'include'
            });

            const data = await response.json();
            
            if (data.success) {
                this.currentUser = data.user;
                this.showLoginStatus(true);
                return { success: true, user: data.user };
            } else {
                return { success: false, error: data.error };
            }
        } catch (error) {
            console.error('Login error:', error);
            return { success: false, error: 'Network error' };
        }
    }

    async register(username, password, email) {
        try {
            const response = await fetch(`${this.baseURL}/api/auth/register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ username, password, email }),
                credentials: 'include'
            });

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Registration error:', error);
            return { success: false, error: 'Network error' };
        }
    }

    async logout() {
        try {
            const response = await fetch(`${this.baseURL}/api/auth/logout`, {
                method: 'POST',
                credentials: 'include'
            });

            const data = await response.json();
            if (data.success) {
                this.currentUser = null;
                this.showLoginStatus(false);
            }
            return data;
        } catch (error) {
            console.error('Logout error:', error);
            return { success: false, error: 'Network error' };
        }
    }

    // Game statistics methods
    startGame() {
        this.gameStartTime = Date.now();
        this.moveCount = 0;
    }

    incrementMoveCount() {
        this.moveCount++;
        this.updateMoveCounter();
    }

    async saveGameStats(winStatus) {
        if (!this.currentUser || !this.gameStartTime) return;

        const timeInSeconds = Math.floor((Date.now() - this.gameStartTime) / 1000);
        
        try {
            const response = await fetch(`${this.baseURL}/api/game/save`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    puzzle_size: '4x4',
                    time_taken_seconds: timeInSeconds,
                    moves_count: this.moveCount,
                    background_image_id: 1,
                    win_status: winStatus
                }),
                credentials: 'include'
            });

            return await response.json();
        } catch (error) {
            console.error('Save game stats error:', error);
            return { success: false, error: 'Network error' };
        }
    }

    async getUserStats() {
        if (!this.currentUser) return { success: false, error: 'Not logged in' };

        try {
            const response = await fetch(`${this.baseURL}/api/game/stats`, {
                method: 'GET',
                credentials: 'include'
            });

            return await response.json();
        } catch (error) {
            console.error('Get user stats error:', error);
            return { success: false, error: 'Network error' };
        }
    }

    // UI Helper methods
    showLoginStatus(isLoggedIn) {
        const loginSection = document.getElementById('login-section');
        const userSection = document.getElementById('user-section');
        const gameStatsSection = document.getElementById('game-stats');

        if (isLoggedIn) {
            loginSection.style.display = 'none';
            userSection.style.display = 'block';
            gameStatsSection.style.display = 'block';
            document.getElementById('username-display').textContent = this.currentUser.username;
            this.loadUserStats();
        } else {
            loginSection.style.display = 'block';
            userSection.style.display = 'none';
            gameStatsSection.style.display = 'none';
        }
    }

    updateMoveCounter() {
        const moveCounterElement = document.getElementById('move-counter');
        if (moveCounterElement) {
            moveCounterElement.textContent = `Moves: ${this.moveCount}`;
        }
    }

    updateTimer() {
        if (!this.gameStartTime) return;
        
        const timeElapsed = Math.floor((Date.now() - this.gameStartTime) / 1000);
        const minutes = Math.floor(timeElapsed / 60);
        const seconds = timeElapsed % 60;
        
        const timerElement = document.getElementById('timer');
        if (timerElement) {
            timerElement.textContent = `Time: ${minutes}:${seconds.toString().padStart(2, '0')}`;
        }
    }

    async loadUserStats() {
        const stats = await this.getUserStats();
        if (stats.success) {
            this.displayUserStats(stats.stats);
        }
    }

    displayUserStats(stats) {
        const statsContainer = document.getElementById('stats-container');
        if (!statsContainer || !stats.length) return;

        const statsHTML = stats.map(stat => `
            <div class="stat-item">
                <span>Time: ${Math.floor(stat.time_taken_seconds / 60)}:${(stat.time_taken_seconds % 60).toString().padStart(2, '0')}</span>
                <span>Moves: ${stat.moves_count}</span>
                <span>Status: ${stat.win_status ? '✓ Won' : '✗ Incomplete'}</span>
                <span>Date: ${new Date(stat.game_date).toLocaleDateString()}</span>
            </div>
        `).join('');

        statsContainer.innerHTML = statsHTML;
    }

    // Initialize event listeners
    initializeAuthUI() {
        // Login form
        document.getElementById('login-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('login-username').value;
            const password = document.getElementById('login-password').value;
            
            const result = await this.login(username, password);
            if (!result.success) {
                alert('Login failed: ' + result.error);
            }
        });

        // Register form
        document.getElementById('register-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('register-username').value;
            const password = document.getElementById('register-password').value;
            const email = document.getElementById('register-email').value;
            
            const result = await this.register(username, password, email);
            if (result.success) {
                alert('Registration successful! Please log in.');
            } else {
                alert('Registration failed: ' + result.error);
            }
        });

        // Logout button
        document.getElementById('logout-btn')?.addEventListener('click', async () => {
            await this.logout();
        });

        // Start timer when game starts
        setInterval(() => {
            this.updateTimer();
        }, 1000);
    }
}

// Global API instance
window.puzzleAPI = new PuzzleAPI();
