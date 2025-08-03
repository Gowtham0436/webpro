// Fifteen Puzzle Game Logic with Database Integration
class FifteenPuzzle {
    constructor() {
        this.grid = [];
        this.puzzleSize = 4; // Default 4x4 puzzle
        this.totalTiles = this.puzzleSize * this.puzzleSize;
        this.emptyPosition = this.totalTiles - 1; // Bottom-right position
        
        // Get DOM elements with retries if needed
        this.initializeElements();
        
        // Game tracking variables
        this.startTime = null;
        this.moveCount = 0;
        this.gameTimer = null;
        this.isGameActive = false;
        this.isShuffled = false; // Track if puzzle has been shuffled
        this.currentBackgroundId = 1;
        this.availableBackgrounds = [];
        
        // Check if required elements exist
        if (!this.gridElement) {
            console.error('puzzle-grid element not found!');
            return;
        }
        
        this.initializeGame();
        this.bindEvents();
        this.loadBackgroundImages();
    }
    
    // Initialize DOM elements with retries
    initializeElements() {
        this.gridElement = document.getElementById('puzzle-grid');
        this.shuffleButton = document.getElementById('shuffle-btn');
        this.timerElement = document.getElementById('timer');
        this.moveCounterElement = document.getElementById('move-counter');
        this.backgroundSelect = document.getElementById('background-select');
        this.sizeSelect = document.getElementById('size-select');
        
        // Log what we found
        console.log('Element check:', {
            gridElement: !!this.gridElement,
            shuffleButton: !!this.shuffleButton,
            timerElement: !!this.timerElement,
            moveCounterElement: !!this.moveCounterElement,
            backgroundSelect: !!this.backgroundSelect,
            sizeSelect: !!this.sizeSelect
        });
    }
    
    // Initialize the game with tiles in correct order
    initializeGame() {
        // Create initial grid with numbers 1 to (n²-1) and empty space (0)
        this.grid = [];
        for (let i = 1; i < this.totalTiles; i++) {
            this.grid.push(i);
        }
        this.grid.push(0); // Empty space represented by 0
        
        // Reset game tracking
        this.moveCount = 0;
        this.startTime = null;
        this.isGameActive = false;
        this.isShuffled = false; // Reset shuffle state
        this.updateMoveCounter();
        this.updateTimer(0);
        
        this.setupGrid();
        this.renderGrid();
    }
    
    // Setup the grid container based on puzzle size
    setupGrid() {
        const tileSize = Math.min(400 / this.puzzleSize, 100); // Responsive tile size
        const gridSize = tileSize * this.puzzleSize;
        
        // Update CSS grid
        this.gridElement.style.gridTemplateColumns = `repeat(${this.puzzleSize}, ${tileSize}px)`;
        this.gridElement.style.gridTemplateRows = `repeat(${this.puzzleSize}, ${tileSize}px)`;
        this.gridElement.style.width = `${gridSize}px`;
        this.gridElement.style.height = `${gridSize}px`;
        
        // Update CSS custom property for background size
        document.documentElement.style.setProperty('--grid-size', `${gridSize}px`);
        document.documentElement.style.setProperty('--tile-size', `${tileSize}px`);
        document.documentElement.style.setProperty('--puzzle-size', this.puzzleSize);
    }
    
    // Render the grid on the page
    renderGrid() {
        this.gridElement.innerHTML = '';
        
        // Apply disabled state if not shuffled
        const instructionElement = document.getElementById('shuffle-instruction');
        
        if (!this.isShuffled) {
            this.gridElement.classList.add('disabled');
            if (instructionElement) {
                instructionElement.classList.remove('hidden');
            }
        } else {
            this.gridElement.classList.remove('disabled');
            if (instructionElement) {
                instructionElement.classList.add('hidden');
            }
        }
        
        this.grid.forEach((value, index) => {
            const tile = document.createElement('div');
            tile.className = 'tile';
            
            if (value === 0) {
                // Empty tile
                tile.classList.add('empty');
                tile.style.visibility = 'hidden';
            } else {
                // Numbered tile
                tile.textContent = value;
                tile.dataset.value = value;
                tile.dataset.index = index;
                // Set background position based on the tile's correct position (value - 1)
                tile.dataset.position = value - 1;
                
                // Calculate background position for different puzzle sizes
                const correctRow = Math.floor((value - 1) / this.puzzleSize);
                const correctCol = (value - 1) % this.puzzleSize;
                const tileSize = Math.min(400 / this.puzzleSize, 100);
                const backgroundPosX = -correctCol * tileSize;
                const backgroundPosY = -correctRow * tileSize;
                
                tile.style.backgroundPosition = `${backgroundPosX}px ${backgroundPosY}px`;
                tile.style.backgroundSize = `${tileSize * this.puzzleSize}px ${tileSize * this.puzzleSize}px`;
                
                // Adjust font size based on puzzle size
                const fontSize = Math.max(12, Math.min(32, tileSize / 3));
                tile.style.fontSize = `${fontSize}pt`;
                
                // Add event listeners for click and hover
                tile.addEventListener('click', () => this.handleTileClick(index));
                tile.addEventListener('mouseenter', () => this.handleTileHover(tile, index));
                tile.addEventListener('mouseleave', () => this.handleTileLeave(tile, index));
            }
            
            this.gridElement.appendChild(tile);
        });
    }
    
    // Handle tile click
    handleTileClick(clickedIndex) {
        // Prevent moves if puzzle hasn't been shuffled yet
        if (!this.isShuffled) {
            return;
        }
        
        const emptyIndex = this.grid.indexOf(0);
        
        if (this.canMoveTile(clickedIndex, emptyIndex)) {
            // Start timer on first move
            if (!this.isGameActive) {
                this.startGame();
            }
            
            // Swap the clicked tile with the empty space
            [this.grid[clickedIndex], this.grid[emptyIndex]] = [this.grid[emptyIndex], this.grid[clickedIndex]];
            this.moveCount++;
            this.updateMoveCounter();
            this.renderGrid();
            
            // Check if puzzle is solved
            if (this.isSolved()) {
                this.endGame(true);
            }
        }
        // If tile cannot be moved, no action occurs (as specified)
    }
    
    // Handle mouse hover over tile
    handleTileHover(tile, index) {
        // Don't show hover effects if puzzle hasn't been shuffled
        if (!this.isShuffled) {
            return;
        }
        
        const emptyIndex = this.grid.indexOf(0);
        
        if (this.canMoveTile(index, emptyIndex)) {
            tile.classList.add('movablepiece');
        }
        // If tile cannot be moved, no effect (as specified)
    }
    
    // Handle mouse leave tile
    handleTileLeave(tile, index) {
        tile.classList.remove('movablepiece');
    }
    
    // Check if a tile can be moved (is adjacent to empty space)
    canMoveTile(tileIndex, emptyIndex) {
        const tileRow = Math.floor(tileIndex / this.puzzleSize);
        const tileCol = tileIndex % this.puzzleSize;
        const emptyRow = Math.floor(emptyIndex / this.puzzleSize);
        const emptyCol = emptyIndex % this.puzzleSize;
        
        // Check if tiles are adjacent (horizontally or vertically)
        const rowDiff = Math.abs(tileRow - emptyRow);
        const colDiff = Math.abs(tileCol - emptyCol);
        
        return (rowDiff === 1 && colDiff === 0) || (rowDiff === 0 && colDiff === 1);
    }
    
    // Check if the puzzle is solved
    isSolved() {
        for (let i = 0; i < this.totalTiles - 1; i++) {
            if (this.grid[i] !== i + 1) {
                return false;
            }
        }
        return this.grid[this.totalTiles - 1] === 0; // Empty space should be in last position
    }
    
    // Efficient shuffle algorithm that ensures solvability
    shuffle() {
        // Start with solved state and perform random valid moves
        // This guarantees a solvable state
        const numberOfMoves = 500; // Sufficient for good randomization
        
        for (let i = 0; i < numberOfMoves; i++) {
            const emptyIndex = this.grid.indexOf(0);
            const validMoves = this.getValidMoves(emptyIndex);
            
            if (validMoves.length > 0) {
                // Choose a random valid move
                const randomMoveIndex = Math.floor(Math.random() * validMoves.length);
                const targetIndex = validMoves[randomMoveIndex];
                
                // Perform the move
                [this.grid[emptyIndex], this.grid[targetIndex]] = [this.grid[targetIndex], this.grid[emptyIndex]];
            }
        }
        
        // Reset game state after shuffle
        this.moveCount = 0;
        this.startTime = null;
        this.isGameActive = false;
        this.isShuffled = true; // Mark puzzle as shuffled and playable
        this.updateMoveCounter();
        this.updateTimer(0);
        if (this.gameTimer) {
            clearInterval(this.gameTimer);
        }
        
        this.renderGrid();
    }
    
    // Start the game timer
    startGame() {
        this.startTime = Date.now();
        this.isGameActive = true;
        
        this.gameTimer = setInterval(() => {
            const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
            this.updateTimer(elapsed);
        }, 1000);
    }
    
    // End the game
    endGame(won) {
        this.isGameActive = false;
        if (this.gameTimer) {
            clearInterval(this.gameTimer);
        }
        
        const timeElapsed = Math.floor((Date.now() - this.startTime) / 1000);
        
        if (won) {
            setTimeout(() => {
                // Save game stats first
                this.saveGameStats(timeElapsed, this.moveCount, true);
                
                // Show completion modal instead of alert
                if (typeof showCompletionModal !== 'undefined') {
                    showCompletionModal(timeElapsed, this.moveCount);
                } else {
                    // Fallback for if modal function isn't available
                    alert(`Congratulations! You solved the puzzle!\nTime: ${this.formatTime(timeElapsed)}\nMoves: ${this.moveCount}`);
                }
            }, 100);
        }
    }
    
    // Update move counter display
    updateMoveCounter() {
        if (this.moveCounterElement) {
            this.moveCounterElement.textContent = `Moves: ${this.moveCount}`;
        }
    }
    
    // Update timer display
    updateTimer(seconds) {
        if (this.timerElement) {
            this.timerElement.textContent = `Time: ${this.formatTime(seconds)}`;
        }
    }
    
    // Format time as MM:SS
    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    }
    
    // Save game statistics to database
    async saveGameStats(timeSeconds, moves, won) {
        const userId = localStorage.getItem('user_id');
        if (!userId) return;
        
        const gameData = {
            action: 'save_game_stats',
            user_id: parseInt(userId),
            time_taken_seconds: timeSeconds,
            moves_count: moves,
            win_status: won,
            puzzle_size: `${this.puzzleSize}x${this.puzzleSize}`,
            background_image_id: this.currentBackgroundId
        };
        
        try {
            const response = await fetch('game_stats.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(gameData)
            });
            
            const result = await response.json();
            if (result.success) {
                console.log('Game stats saved successfully');
            } else {
                console.error('Failed to save game stats:', result.message);
            }
        } catch (error) {
            console.error('Error saving game stats:', error);
        }
    }
    
    // Get all valid moves for the empty space (neighboring tiles)
    getValidMoves(emptyIndex) {
        const validMoves = [];
        const emptyRow = Math.floor(emptyIndex / this.puzzleSize);
        const emptyCol = emptyIndex % this.puzzleSize;
        
        // Check all four directions: up, down, left, right
        const directions = [
            { row: -1, col: 0 }, // Up
            { row: 1, col: 0 },  // Down
            { row: 0, col: -1 }, // Left
            { row: 0, col: 1 }   // Right
        ];
        
        directions.forEach(dir => {
            const newRow = emptyRow + dir.row;
            const newCol = emptyCol + dir.col;
            
            // Check if the new position is within bounds
            if (newRow >= 0 && newRow < this.puzzleSize && newCol >= 0 && newCol < this.puzzleSize) {
                validMoves.push(newRow * this.puzzleSize + newCol);
            }
        });
        
        return validMoves;
    }
    
    // Change puzzle size
    changePuzzleSize(newSize) {
        // Stop current game if active
        if (this.isGameActive && this.gameTimer) {
            clearInterval(this.gameTimer);
        }
        
        this.puzzleSize = newSize;
        this.totalTiles = this.puzzleSize * this.puzzleSize;
        this.emptyPosition = this.totalTiles - 1;
        
        // Reinitialize the game
        this.initializeGame();
    }
    
    // Bind event listeners
    bindEvents() {
        console.log('Binding events...');
        
        if (this.shuffleButton) {
            this.shuffleButton.addEventListener('click', () => {
                this.shuffle();
            });
            console.log('✓ Shuffle button event bound');
        } else {
            console.log('⚠ Shuffle button not found');
        }
        
        // Background selector change
        if (this.backgroundSelect) {
            this.backgroundSelect.addEventListener('change', (e) => {
                this.currentBackgroundId = parseInt(e.target.value);
                this.updateBackgroundImage();
            });
            console.log('✓ Background selector event bound');
        } else {
            console.log('⚠ Background selector not found');
        }
        
        // Size selector change - with more robust binding
        if (this.sizeSelect) {
            // Set initial value to match default puzzleSize
            this.sizeSelect.value = this.puzzleSize;
            console.log('✓ Size selector found during initial binding');
            console.log('Set initial size selector value to:', this.puzzleSize);
            console.log('Size selector parent:', this.sizeSelect.parentElement);
            console.log('Size selector visible:', this.sizeSelect.offsetParent !== null);
            console.log('Size selector style display:', this.sizeSelect.style.display);
            console.log('Size selector computed display:', getComputedStyle(this.sizeSelect).display);
            
            // Force a small delay to ensure DOM is fully ready
            setTimeout(() => {
                this.sizeSelect.addEventListener('change', (e) => {
                    const newSize = parseInt(e.target.value);
                    console.log('Size changed via game event to:', newSize);
                    this.changePuzzleSize(newSize);
                });
                console.log('✓ Size selector event bound successfully (with delay)');
            }, 50);
        } else {
            console.log('⚠ Size selector not found during initial binding - will retry later');
            // Retry binding size selector after a delay
            setTimeout(() => {
                console.log('Retrying size selector binding...');
                this.sizeSelect = document.getElementById('size-select');
                if (this.sizeSelect) {
                    // Set initial value to match default puzzleSize
                    this.sizeSelect.value = this.puzzleSize;
                    console.log('✓ Size selector found on retry');
                    console.log('Set initial size selector value to:', this.puzzleSize);
                    console.log('Size selector parent:', this.sizeSelect.parentElement);
                    console.log('Size selector visible:', this.sizeSelect.offsetParent !== null);
                    
                    this.sizeSelect.addEventListener('change', (e) => {
                        const newSize = parseInt(e.target.value);
                        console.log('Size changed via retry event to:', newSize);
                        this.changePuzzleSize(newSize);
                    });
                    console.log('✓ Size selector event bound successfully on retry');
                } else {
                    console.log('⚠ Size selector still not found after retry');
                }
            }, 1000);
        }
        
        // Prevent any action when clicking on empty space or elsewhere
        if (this.gridElement) {
            this.gridElement.addEventListener('click', (e) => {
                if (e.target === this.gridElement) {
                    // Clicked on empty space or grid background - no action
                    return;
                }
            });
            console.log('✓ Grid click event bound');
        }
        
        console.log('Event binding completed');
    }
    
    // Load available background images from admin-managed content
    async loadBackgroundImages() {
        // Skip loading backgrounds in test mode
        if (!this.backgroundSelect) {
            console.log('Background selector not found, skipping background loading');
            return;
        }
        
        try {
            const response = await fetch('public_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'get_active_background_images' })
            });
            
            const data = await response.json();
            
            if (data.success && data.images) {
                this.availableBackgrounds = data.images;
                this.populateBackgroundSelector();
            }
        } catch (error) {
            console.error('Failed to load background images:', error);
        }
    }
    
    // Populate the background selector dropdown
    populateBackgroundSelector() {
        this.backgroundSelect.innerHTML = '';
        
        this.availableBackgrounds.forEach(image => {
            const option = document.createElement('option');
            option.value = image.image_id;
            option.textContent = image.image_name;
            this.backgroundSelect.appendChild(option);
        });
        
        // Check if there's a preferred background set before game initialization
        if (window.preferredBackgroundId) {
            this.currentBackgroundId = window.preferredBackgroundId;
            delete window.preferredBackgroundId; // Clean up
            console.log('Applied preferred background from preferences:', this.currentBackgroundId);
        }
        
        // Set default selection
        this.backgroundSelect.value = this.currentBackgroundId;
        this.updateBackgroundImage();
    }
    
    // Update the background image of the puzzle
    updateBackgroundImage() {
        const selectedBackground = this.availableBackgrounds.find(
            bg => bg.image_id == this.currentBackgroundId
        );
        
        if (selectedBackground) {
            // Update CSS custom property for background image
            document.documentElement.style.setProperty('--puzzle-background', `url(${selectedBackground.image_url})`);
        }
    }
}

// Don't auto-initialize - will be called from fifteen.html after authentication
