// Admin Dashboard JavaScript
class AdminDashboard {
    constructor() {
        this.currentUser = null;
        this.init();
    }

    async init() {
        // Check if user is logged in and is admin
        await this.checkAuth();
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Load initial data
        this.loadUsers();
        this.loadImages();
        this.loadGameStats();
        this.loadAnnouncements();
    }

    async checkAuth() {
        try {
            const response = await fetch('auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'check_session' })
            });
            
            const data = await response.json();
            
            if (!data.success || !data.logged_in || data.role !== 'admin') {
                window.location.href = 'login.html';
                return;
            }
            
            this.currentUser = data;
            document.getElementById('admin-username').textContent = data.username;
        } catch (error) {
            console.error('Auth check failed:', error);
            window.location.href = 'login.html';
        }
    }

    setupEventListeners() {
        // Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.showSection(link.dataset.section);
                
                // Update active nav
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                link.classList.add('active');
            });
        });

        // Logout
        document.getElementById('logout-btn').addEventListener('click', this.logout.bind(this));

        // User Management
        document.getElementById('refresh-users').addEventListener('click', this.loadUsers.bind(this));
        document.getElementById('user-search').addEventListener('input', this.searchUsers.bind(this));

        // Content Management
        document.getElementById('add-image-btn').addEventListener('click', this.showAddImageForm.bind(this));
        document.getElementById('cancel-add-image').addEventListener('click', this.hideAddImageForm.bind(this));
        document.getElementById('image-upload-form').addEventListener('submit', this.uploadImage.bind(this));
        document.getElementById('refresh-images').addEventListener('click', this.loadImages.bind(this));

        // Game Statistics
        document.getElementById('refresh-stats').addEventListener('click', this.loadGameStats.bind(this));
        document.getElementById('stats-filter').addEventListener('change', this.loadGameStats.bind(this));

        // Announcements
        document.getElementById('add-announcement-btn').addEventListener('click', this.showAddAnnouncementForm.bind(this));
        document.getElementById('cancel-add-announcement').addEventListener('click', this.hideAddAnnouncementForm.bind(this));
        document.getElementById('announcement-form').addEventListener('submit', this.createAnnouncement.bind(this));
        document.getElementById('refresh-announcements').addEventListener('click', this.loadAnnouncements.bind(this));

        // Modal
        document.querySelector('.close').addEventListener('click', this.closeModal.bind(this));
        window.addEventListener('click', (e) => {
            if (e.target === document.getElementById('edit-modal')) {
                this.closeModal();
            }
        });
    }

    showSection(sectionId) {
        document.querySelectorAll('.admin-section').forEach(section => {
            section.classList.remove('active');
        });
        document.getElementById(sectionId).classList.add('active');
    }

    async logout() {
        try {
            const response = await fetch('auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'logout' })
            });
            
            window.location.href = 'login.html';
        } catch (error) {
            console.error('Logout failed:', error);
        }
    }

    // User Management Methods
    async loadUsers() {
        try {
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'get_users' })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.displayUsers(data.users);
            } else {
                this.showMessage('Error loading users: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error loading users:', error);
            this.showMessage('Error loading users', 'error');
        }
    }

    displayUsers(users) {
        const tbody = document.getElementById('users-tbody');
        tbody.innerHTML = '';
        
        users.forEach(user => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${user.user_id}</td>
                <td>${user.username}</td>
                <td>${user.email}</td>
                <td>${user.role}</td>
                <td>${new Date(user.registration_date).toLocaleDateString()}</td>
                <td>${user.last_login ? new Date(user.last_login).toLocaleDateString() : 'Never'}</td>
                <td><span class="status-${user.is_active ? 'active' : 'inactive'}">${user.is_active ? 'Active' : 'Inactive'}</span></td>
                <td>
                    <button class="btn btn-small btn-primary" onclick="adminDashboard.editUser(${user.user_id})">Edit</button>
                    <button class="btn btn-small btn-${user.is_active ? 'warning' : 'success'}" onclick="adminDashboard.toggleUserStatus(${user.user_id}, ${!user.is_active})">${user.is_active ? 'Deactivate' : 'Activate'}</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    searchUsers() {
        const query = document.getElementById('user-search').value.toLowerCase();
        const rows = document.querySelectorAll('#users-tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    }

    async toggleUserStatus(userId, isActive) {
        try {
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    action: 'toggle_user_status', 
                    user_id: userId, 
                    is_active: isActive 
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage('User status updated successfully', 'success');
                this.loadUsers();
            } else {
                this.showMessage('Error updating user status: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error updating user status:', error);
            this.showMessage('Error updating user status', 'error');
        }
    }

    async editUser(userId) {
        // Implementation for editing user details
        this.showMessage('Edit user functionality coming soon', 'warning');
    }

    // Content Management Methods
    showAddImageForm() {
        document.getElementById('add-image-form').style.display = 'block';
    }

    hideAddImageForm() {
        document.getElementById('add-image-form').style.display = 'none';
        document.getElementById('image-upload-form').reset();
    }

    async uploadImage(event) {
        event.preventDefault();
        
        const formData = new FormData();
        const imageFile = document.getElementById('image-file').files[0];
        const imageName = document.getElementById('image-name').value;
        const isActive = document.getElementById('image-active').checked;
        
        if (!imageFile) {
            this.showMessage('Please select an image file', 'error');
            return;
        }
        
        formData.append('action', 'upload_image');
        formData.append('image_name', imageName);
        formData.append('image_file', imageFile);
        formData.append('is_active', isActive);
        
        try {
            const response = await fetch('admin_api.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage('Image uploaded successfully', 'success');
                this.hideAddImageForm();
                this.loadImages();
            } else {
                this.showMessage('Error uploading image: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error uploading image:', error);
            this.showMessage('Error uploading image', 'error');
        }
    }

    async loadImages() {
        try {
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'get_images' })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.displayImages(data.images);
            } else {
                this.showMessage('Error loading images: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error loading images:', error);
            this.showMessage('Error loading images', 'error');
        }
    }

    displayImages(images) {
        const grid = document.getElementById('images-grid');
        grid.innerHTML = '';
        
        images.forEach(image => {
            const card = document.createElement('div');
            card.className = 'image-card';
            card.innerHTML = `
                <img src="${image.image_url}" alt="${image.image_name}" onerror="this.src='background.jpg'">
                <div class="image-card-body">
                    <div class="image-card-title">${image.image_name}</div>
                    <div class="image-card-meta">
                        Status: <span class="status-${image.is_active ? 'active' : 'inactive'}">${image.is_active ? 'Active' : 'Inactive'}</span><br>
                        Uploaded: ${new Date(image.upload_date).toLocaleDateString()}
                    </div>
                    <div class="image-card-actions">
                        <button class="btn btn-small btn-${image.is_active ? 'warning' : 'success'}" onclick="adminDashboard.toggleImageStatus(${image.image_id}, ${!image.is_active})">${image.is_active ? 'Deactivate' : 'Activate'}</button>
                        <button class="btn btn-small btn-danger" onclick="adminDashboard.deleteImage(${image.image_id})">Delete</button>
                    </div>
                </div>
            `;
            grid.appendChild(card);
        });
    }

    async toggleImageStatus(imageId, isActive) {
        try {
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    action: 'toggle_image_status', 
                    image_id: imageId, 
                    is_active: isActive 
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage('Image status updated successfully', 'success');
                this.loadImages();
            } else {
                this.showMessage('Error updating image status: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error updating image status:', error);
            this.showMessage('Error updating image status', 'error');
        }
    }

    async deleteImage(imageId) {
        if (!confirm('Are you sure you want to delete this image?')) {
            return;
        }
        
        try {
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    action: 'delete_image', 
                    image_id: imageId 
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage('Image deleted successfully', 'success');
                this.loadImages();
            } else {
                this.showMessage('Error deleting image: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error deleting image:', error);
            this.showMessage('Error deleting image', 'error');
        }
    }

    // Game Statistics Methods
    async loadGameStats() {
        const filter = document.getElementById('stats-filter').value;
        
        try {
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'get_stats', filter: filter })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.displayStats(data.stats);
                this.displayRecentGames(data.recent_games);
            } else {
                this.showMessage('Error loading statistics: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error loading statistics:', error);
            this.showMessage('Error loading statistics', 'error');
        }
    }

    displayStats(stats) {
        document.getElementById('total-players').textContent = stats.total_players || 0;
        document.getElementById('total-games').textContent = stats.total_games || 0;
        document.getElementById('average-time').textContent = stats.average_time ? `${Math.round(stats.average_time)}s` : '-';
        document.getElementById('win-rate').textContent = stats.win_rate ? `${Math.round(stats.win_rate)}%` : '-';
    }

    displayRecentGames(games) {
        const tbody = document.getElementById('games-tbody');
        tbody.innerHTML = '';
        
        games.forEach(game => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${game.stat_id}</td>
                <td>${game.username}</td>
                <td>${game.puzzle_size}</td>
                <td>${game.time_taken_seconds}s</td>
                <td>${game.moves_count}</td>
                <td><span class="status-${game.win_status ? 'active' : 'inactive'}">${game.win_status ? 'Won' : 'Lost'}</span></td>
                <td>${new Date(game.game_date).toLocaleDateString()}</td>
            `;
            tbody.appendChild(row);
        });
    }

    // Announcements Methods
    showAddAnnouncementForm() {
        document.getElementById('add-announcement-form').style.display = 'block';
    }

    hideAddAnnouncementForm() {
        document.getElementById('add-announcement-form').style.display = 'none';
        document.getElementById('announcement-form').reset();
    }

    async createAnnouncement(event) {
        event.preventDefault();
        
        const title = document.getElementById('announcement-title').value;
        const content = document.getElementById('announcement-content').value;
        const isActive = document.getElementById('announcement-active').checked;
        
        try {
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    action: 'create_announcement',
                    title: title,
                    content: content,
                    is_active: isActive
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage('Announcement created successfully', 'success');
                this.hideAddAnnouncementForm();
                this.loadAnnouncements();
            } else {
                this.showMessage('Error creating announcement: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error creating announcement:', error);
            this.showMessage('Error creating announcement', 'error');
        }
    }

    async loadAnnouncements() {
        try {
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'get_announcements' })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.displayAnnouncements(data.announcements);
            } else {
                this.showMessage('Error loading announcements: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error loading announcements:', error);
            this.showMessage('Error loading announcements', 'error');
        }
    }

    displayAnnouncements(announcements) {
        const list = document.getElementById('announcements-list');
        list.innerHTML = '';
        
        announcements.forEach(announcement => {
            const card = document.createElement('div');
            card.className = 'announcement-card';
            card.innerHTML = `
                <div class="announcement-header">
                    <h3 class="announcement-title">${announcement.title}</h3>
                    <div class="announcement-meta">
                        <div>Status: <span class="status-${announcement.is_active ? 'active' : 'inactive'}">${announcement.is_active ? 'Active' : 'Inactive'}</span></div>
                        <div>Created: ${new Date(announcement.created_date).toLocaleDateString()}</div>
                    </div>
                </div>
                <div class="announcement-content">${announcement.content}</div>
                <div class="announcement-actions">
                    <button class="btn btn-small btn-primary" onclick="adminDashboard.editAnnouncement(${announcement.id})">Edit</button>
                    <button class="btn btn-small btn-${announcement.is_active ? 'warning' : 'success'}" onclick="adminDashboard.toggleAnnouncementStatus(${announcement.id}, ${!announcement.is_active})">${announcement.is_active ? 'Deactivate' : 'Activate'}</button>
                    <button class="btn btn-small btn-danger" onclick="adminDashboard.deleteAnnouncement(${announcement.id})">Delete</button>
                </div>
            `;
            list.appendChild(card);
        });
    }

    async toggleAnnouncementStatus(announcementId, isActive) {
        try {
            // Ensure isActive is a boolean
            const isActiveBool = Boolean(isActive);
            
            const requestData = { 
                action: 'toggle_announcement_status', 
                announcement_id: parseInt(announcementId), 
                is_active: isActiveBool 
            };
            
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage('Announcement status updated successfully', 'success');
                this.loadAnnouncements();
            } else {
                this.showMessage('Error updating announcement status: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error updating announcement status:', error);
            this.showMessage('Error updating announcement status', 'error');
        }
    }

    async deleteAnnouncement(announcementId) {
        if (!confirm('Are you sure you want to delete this announcement?')) {
            return;
        }
        
        try {
            const requestData = { 
                action: 'delete_announcement', 
                announcement_id: parseInt(announcementId)
            };
            
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage('Announcement deleted successfully', 'success');
                this.loadAnnouncements();
            } else {
                this.showMessage('Error deleting announcement: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error deleting announcement:', error);
            this.showMessage('Error deleting announcement', 'error');
        }
    }

    async editAnnouncement(announcementId) {
        try {
            console.log('editAnnouncement called with:', { announcementId, type: typeof announcementId });
            
            // First get the current announcement data
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'get_announcements' })
            });
            
            const data = await response.json();
            if (!data.success) {
                this.showMessage('Error loading announcement data', 'error');
                return;
            }
            
            const announcement = data.announcements.find(a => a.id == announcementId);
            if (!announcement) {
                this.showMessage('Announcement not found', 'error');
                return;
            }
            
            // Show edit modal
            this.showEditAnnouncementModal(announcement);
            
        } catch (error) {
            console.error('Error loading announcement for edit:', error);
            this.showMessage('Error loading announcement data', 'error');
        }
    }
    
    showEditAnnouncementModal(announcement) {
        const modal = document.getElementById('edit-modal');
        const modalBody = document.getElementById('modal-body');
        
        modalBody.innerHTML = `
            <h3>Edit Announcement</h3>
            <form id="edit-announcement-form">
                <div class="form-group">
                    <label for="edit-title">Title:</label>
                    <input type="text" id="edit-title" value="${announcement.title}" required>
                </div>
                <div class="form-group">
                    <label for="edit-content">Content:</label>
                    <textarea id="edit-content" rows="4" required>${announcement.content}</textarea>
                </div>
                <div class="form-group">
                    <label for="edit-active">Active:</label>
                    <input type="checkbox" id="edit-active" ${announcement.is_active ? 'checked' : ''}>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Announcement</button>
                    <button type="button" class="btn btn-secondary" onclick="adminDashboard.closeModal()">Cancel</button>
                </div>
            </form>
        `;
        
        modal.style.display = 'block';
        
        // Handle form submission
        document.getElementById('edit-announcement-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.updateAnnouncement(announcement.id);
        });
    }
    
    async updateAnnouncement(announcementId) {
        const title = document.getElementById('edit-title').value;
        const content = document.getElementById('edit-content').value;
        const isActive = document.getElementById('edit-active').checked;
        
        try {
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'edit_announcement',
                    announcement_id: announcementId,
                    title: title,
                    content: content,
                    is_active: isActive
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage('Announcement updated successfully', 'success');
                this.closeModal();
                this.loadAnnouncements();
            } else {
                this.showMessage('Error updating announcement: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error updating announcement:', error);
            this.showMessage('Error updating announcement', 'error');
        }
    }

    // Utility Methods
    showMessage(message, type) {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.message');
        existingMessages.forEach(msg => msg.remove());
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `message message-${type}`;
        messageDiv.textContent = message;
        
        const content = document.querySelector('.admin-content');
        content.insertBefore(messageDiv, content.firstChild);
        
        // Auto-remove message after 5 seconds
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }

    closeModal() {
        document.getElementById('edit-modal').style.display = 'none';
    }
}

// Initialize the admin dashboard when the page loads
const adminDashboard = new AdminDashboard();
