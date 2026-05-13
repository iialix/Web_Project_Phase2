/**
 * Movie Tracker - Main JavaScript Module
 * Handles SPA navigation, AJAX calls, and UI interactions
 */

// ========================================
// State Management
// ========================================
let currentUser = JSON.parse(localStorage.getItem('movieTrackerUser')) || null;
let currentMovieId = null;
let isAdmin = false;

// ========================================
// Admin Check
// ========================================
async function checkAdminStatus() {
    if (!currentUser) {
        isAdmin = false;
        return;
    }

    try {
        const result = await get(`DB_Ops.php?action=isAdmin&userId=${currentUser.id}`);
        isAdmin = result.isAdmin || false;
    } catch (error) {
        console.error('Admin check error:', error);
        isAdmin = false;
    }
}

// ========================================
// SPA View Navigation
// ========================================

/**
 * Switch between SPA views without page reload
 * @param {string} viewName - The view ID to show (e.g., 'home', 'movies', 'auth')
 */
function showView(viewName) {
    // Hide all views
    document.querySelectorAll('.view').forEach(view => {
        view.classList.remove('active');
    });
    
    // Show target view
    const targetView = document.getElementById(`view-${viewName}`);
    if (targetView) {
        targetView.classList.add('active');
    }
    
    // Update nav active state
    document.querySelectorAll('.main-nav a').forEach(link => {
        link.classList.toggle('active', link.dataset.view === viewName);
    });
    
    // Close mobile menu
    document.querySelector('.main-nav').classList.remove('active');
    
    // Load data based on view
    if (viewName === 'movies' || viewName === 'home') {
        loadMovies(viewName === 'home' ? 'featured-movies' : 'movies-container');
    } else if (viewName === 'profile') {
        if (!currentUser) {
            showToast('Please login to view profile', 'error');
            showView('auth');
            return;
        }
        loadUserProfile();
        loadUserRatings(currentUser.id);
    } else if (viewName === 'add') {
        if (!currentUser) {
            showToast('Please login to add movies', 'error');
            showView('auth');
            return;
        }
        if (!isAdmin) {
            showToast('Only admin can add movies', 'error');
            showView('movies');
            return;
        }
    } else if (viewName === 'wishlist') {
        if (!currentUser) {
            showToast('Please login to view wishlist', 'error');
            showView('auth');
            return;
        }
        loadWishlist();
    } else if (viewName === 'auth') {
        Validation.clearErrors();
    }
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Navigation click handlers
document.querySelectorAll('.main-nav a').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const viewName = link.dataset.view;
        
        // Handle logout - check if link text is "Logout"
        if (link.textContent === 'Logout' && currentUser) {
            logout();
            return;
        }
        
        showView(viewName);
    });
});

// Mobile menu toggle
document.querySelector('.menu-toggle').addEventListener('click', () => {
    document.querySelector('.main-nav').classList.toggle('active');
});

// ========================================
// AJAX Helper Functions
// ========================================

/**
 * Make a GET request
 * @param {string} url - The URL to fetch
 * @returns {Promise<Object>} - JSON response
 */
async function get(url) {
    try {
        const response = await fetch(url);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return await response.json();
    } catch (error) {
        console.error('GET Error:', error);
        showToast('Network error. Please try again.', 'error');
        throw error;
    }
}

/**
 * Make a POST request with JSON data
 * @param {string} url - The URL to post to
 * @param {Object} data - The data to send
 * @returns {Promise<Object>} - JSON response
 */
async function post(url, data) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (!response.ok) {
            console.error('Server Error Response:', result);
        }
        return result;
    } catch (error) {
        console.error('POST Error:', error);
        showToast('Network error. Please try again.', 'error');
        throw error;
    }
}

/**
 * Make a POST request with FormData (for file uploads)
 * @param {string} url - The URL to post to
 * @param {FormData} formData - The FormData object
 * @returns {Promise<Object>} - JSON response
 */
async function postFormData(url, formData) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return await response.json();
    } catch (error) {
        console.error('POST FormData Error:', error);
        showToast('Network error. Please try again.', 'error');
        throw error;
    }
}

// ========================================
// Movie Operations
// ========================================

/**
 * Load all movies from the database
 * @param {string} containerId - The container element ID
 */
async function loadMovies(containerId = 'movies-container') {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    container.innerHTML = '<div class="spinner"></div>';
    
    // Load wishlist status if user is logged in
    let wishlistMovieIds = new Set();
    if (currentUser) {
        try {
            const wishlistResult = await get(`DB_Ops.php?action=getWishlistByUser&userId=${currentUser.id}`);
            if (wishlistResult.success && wishlistResult.data) {
                wishlistMovieIds = new Set(wishlistResult.data.map(m => parseInt(m.MovieID)));
                console.log('Wishlist movie IDs:', [...wishlistMovieIds]);
            }
        } catch (e) {
            console.error('Wishlist load error:', e);
        }
    }
    
    try {
        const result = await get('DB_Ops.php?action=getAllMovies');
        
        if (result.success && result.data && result.data.length > 0) {
            container.innerHTML = result.data.map((movie, index) => {
                const movieId = parseInt(movie.id);
                const isInWishlist = wishlistMovieIds.has(movieId);
                console.log(`Movie ${movie.name} (ID: ${movieId}) in wishlist:`, isInWishlist);
                const posterSrc = movie.poster ? `uploads/${movie.poster}` : 'https://via.placeholder.com/300x450?text=No+Poster';
                return `
                <div class="movie-card" style="animation-delay: ${index * 0.1}s" onclick="showMovieDetail(${movie.id})">
                    <div class="poster-wrapper">
                        <img src="${posterSrc}" alt="${escapeHtml(movie.name)}" loading="lazy">
                        <div class="overlay">
                            <h3>${escapeHtml(movie.name)}</h3>
                            <p class="categories">${escapeHtml(movie.categories)}</p>
                            <div class="card-actions">
                                <button class="btn-primary" onclick="event.stopPropagation(); showMovieDetail(${movie.id})">View Details</button>
                            </div>
                        </div>
                    </div>
                    ${currentUser ? `<button class="wishlist-btn ${isInWishlist ? 'active' : ''}" data-movie-id="${movie.id}" onclick="event.stopPropagation(); ${isInWishlist ? `removeFromWishlist(${movie.id})` : `addToWishlist(${movie.id})`}" title="${isInWishlist ? 'Remove from wishlist' : 'Add to wishlist'}">${isInWishlist ? '&#10084;' : '&#9825;'}</button>` : ''}
                </div>
            `}).join('');
        } else {
            container.innerHTML = '<p class="empty-state">No movies found. Add the first one!</p>';
        }
    } catch (error) {
        container.innerHTML = '<p class="empty-state">Failed to load movies. Please try again.</p>';
    }
}

/**
 * Show movie detail view
 * @param {number} movieId - The movie ID
 */
async function showMovieDetail(movieId) {
    currentMovieId = movieId;
    
    const container = document.getElementById('movie-detail-content');
    container.innerHTML = '<div class="spinner"></div>';
    showView('movie-detail');
    
    // Check wishlist status if user is logged in
    let isInWishlist = false;
    if (currentUser) {
        try {
            const wishlistResult = await get(`DB_Ops.php?action=getWishlistByUser&userId=${currentUser.id}`);
            if (wishlistResult.success && wishlistResult.data) {
                isInWishlist = wishlistResult.data.some(m => m.MovieID == movieId);
            }
        } catch (e) {
            // Silent fail
        }
    }
    
    try {
        // Get all movies and find the one we need
        const result = await get('DB_Ops.php?action=getAllMovies');
        
        if (result.success && result.data) {
            const movie = result.data.find(m => m.id == movieId);
            
            if (movie) {
                const detailPosterSrc = movie.poster ? `uploads/${movie.poster}` : 'https://via.placeholder.com/300x450?text=No+Poster';
                container.innerHTML = `
                    <div class="movie-detail">
                        <div class="movie-detail-poster">
                            <img src="${detailPosterSrc}" alt="${escapeHtml(movie.name)}">
                        </div>
                        <div class="movie-detail-info">
                            <h2>${escapeHtml(movie.name)}</h2>
                            <p class="categories">${escapeHtml(movie.categories)}</p>
                            <p class="description">${escapeHtml(movie.description)}</p>
                            <div class="detail-actions">
                                <button class="btn-primary" id="rate-movie-btn" onclick="handleRateMovieClick()">Rate Movie</button>
                                ${currentUser ? `<button class="btn-secondary wishlist-btn-detail ${isInWishlist ? 'active' : ''}" data-movie-id="${movieId}" onclick="${isInWishlist ? `removeFromWishlist(${movieId})` : `addToWishlist(${movieId})`}">${isInWishlist ? '&#10084; Remove from Wishlist' : '&#9825; Add to Wishlist'}</button>` : ''}
                                ${currentUser && isAdmin ? `<button class="btn-secondary" onclick="editMovie(${movie.id}, '${escapeHtml(movie.name)}', '${escapeHtml(movie.categories)}', '${escapeHtml(movie.description)}')">Edit</button>` : ''}
                                ${currentUser && isAdmin ? `<button class="btn-secondary btn-danger" onclick="deleteMovie(${movie.id})">Delete</button>` : ''}
                                <button class="btn-secondary" onclick="showView('movies')">Back to List</button>
                            </div>
                        </div>
                    </div>
                `;
                
                // Load ratings for this movie
                loadMovieRatings(movieId);
                
                // Show rating form if user is logged in
                const ratingForm = document.getElementById('add-rating-form');
                if (ratingForm) {
                    ratingForm.classList.toggle('hidden', !currentUser);
                }
            } else {
                container.innerHTML = '<p class="empty-state">Movie not found.</p>';
            }
        }
    } catch (error) {
        container.innerHTML = '<p class="empty-state">Failed to load movie details.</p>';
    }
}

/**
 * Scroll to user's rating
 */
function scrollToMyRating() {
    const myRating = document.querySelector('.rating-item.my-rating');
    if (myRating) {
        myRating.scrollIntoView({ behavior: 'smooth', block: 'center' });
        // Flash animation
        myRating.style.animation = 'none';
        myRating.offsetHeight; // Trigger reflow
        myRating.style.animation = 'highlight 1s ease-in-out';
    }
}

/**
 * Handle Rate Movie button click
 */
function handleRateMovieClick() {
    const btn = document.getElementById('rate-movie-btn');
    if (btn.textContent === 'View My Rating') {
        scrollToMyRating();
    } else {
        showAddRatingForm();
    }
}

function showAddRatingForm() {
    const ratingForm = document.getElementById('add-rating-form');
    if (ratingForm) {
        ratingForm.classList.remove('hidden');
        ratingForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

/**
 * Search TMDB for movies
 */
async function searchTMDB() {
    const query = document.getElementById('tmdb-search-input').value.trim();
    const resultsContainer = document.getElementById('tmdb-search-results');
    
    if (!query) {
        showToast('Please enter a movie title', 'error');
        return;
    }
    
    resultsContainer.innerHTML = '<div class="spinner"></div>';
    
    try {
        const result = await get(`API_Ops.php?action=search&query=${encodeURIComponent(query)}`);
        
        if (result.success && result.data && result.data.length > 0) {
            resultsContainer.innerHTML = result.data.map(movie => `
                <div class="tmdb-result-card" onclick="selectTMDBMovie(${movie.id}, '${escapeHtml(movie.title)}', '${escapeHtml(movie.year || '')}', '${escapeHtml(movie.overview || '')}', '${movie.poster || ''}')">
                    <img src="${movie.poster || 'https://via.placeholder.com/200x300?text=No+Poster'}" alt="${escapeHtml(movie.title)}">
                    <h4>${escapeHtml(movie.title)}</h4>
                    <p>${movie.year || 'N/A'}</p>
                </div>
            `).join('');
        } else {
            resultsContainer.innerHTML = '<p class="empty-state">No results found from TMDB</p>';
        }
    } catch (error) {
        resultsContainer.innerHTML = '<p class="empty-state">Failed to search TMDB</p>';
        console.error('TMDB search error:', error);
    }
}

/**
 * Select a movie from TMDB and fill the form
 */
function selectTMDBMovie(id, title, year, overview, poster) {
    document.getElementById('movie-name').value = title;
    document.getElementById('movie-description').value = overview || '';

    // Extract categories from overview (simple heuristic)
    const categories = ['Drama', 'Action']; // Default
    document.getElementById('movie-categories').value = categories.join(', ');

    // Show poster preview if available
    const posterPreviewSection = document.getElementById('poster-preview-section');
    const posterPreviewImg = document.getElementById('poster-preview-img');
    const downloadPosterBtn = document.getElementById('download-poster-btn');
    const tmdbUrlField = document.getElementById('tmdb-poster-url');

    if (poster) {
        posterPreviewImg.src = poster;
        downloadPosterBtn.href = poster;
        posterPreviewSection.classList.remove('hidden');
        // Store URL for server-side download (CORS blocks client-side fetch)
        tmdbUrlField.value = poster;
        showToast('Movie details filled! Poster will be downloaded automatically.', 'success');
    } else {
        posterPreviewSection.classList.add('hidden');
        // Clear hidden field if no poster
        tmdbUrlField.value = '';
    }

    // Hide TMDB results
    document.getElementById('tmdb-search-results').innerHTML = '';
}

/**
 * Clear poster preview
 */
function clearPosterPreview() {
    document.getElementById('poster-preview-section').classList.add('hidden');
    document.getElementById('poster-preview-img').src = '';
    // Also clear the file input and hidden URL field
    document.getElementById('movie-poster').value = '';
    document.getElementById('tmdb-poster-url').value = '';
}

/**
 * Load ratings for a specific movie
 * @param {number} movieId - The movie ID
 */
// Helper function to generate star display
function getStarDisplay(rating) {
    const stars = '★'.repeat(rating) + '☆'.repeat(10 - rating);
    return `<span class="rating-stars">${stars}</span> <span class="rating-number">${rating}/10</span>`;
}

async function loadMovieRatings(movieId) {
    const container = document.getElementById('movie-ratings');
    const ratingForm = document.getElementById('add-rating-form');
    if (!container) return;
    
    container.innerHTML = '<div class="spinner"></div>';
    
    try {
        const result = await get(`DB_Ops.php?action=getRatingsByMovie&movieId=${movieId}`);
        
        if (result.success && result.data && result.data.length > 0) {
            const averageRating = result.averageRating || 0;
            
            // Check if current user has rated this movie
            const userRating = currentUser ? result.data.find(r => r.UserID == currentUser.id) : null;
            const hasUserRated = !!userRating;
            
            // Show/hide rating form and update Rate Movie button based on whether user already rated
            const rateMovieBtn = document.getElementById('rate-movie-btn');
            if (ratingForm) {
                ratingForm.classList.toggle('hidden', !(currentUser && !hasUserRated));
            }
            if (rateMovieBtn) {
                if (currentUser && hasUserRated) {
                    rateMovieBtn.textContent = 'View My Rating';
                } else {
                    rateMovieBtn.textContent = 'Rate Movie';
                }
            }
            
            container.innerHTML = `
                <div class="average-rating">
                    <strong>Average: ${getStarDisplay(Math.round(averageRating))}</strong> 
                    <span class="review-count">(${result.totalRatings} reviews)</span>
                </div>
                ${result.data.map(rating => {
                    const isOwnRating = currentUser && rating.UserID == currentUser.id;
                    return `
                    <div class="rating-item ${isOwnRating ? 'my-rating' : ''}" data-rating-id="${rating.Id}" id="rating-${rating.Id}">
                        <div class="rating-header">
                            <span class="username">${escapeHtml(rating.UserName)} ${isOwnRating ? '(You)' : ''}</span>
                            <span class="rating-value">${getStarDisplay(rating.Rating)}</span>
                        </div>
                        ${rating.Description ? `<p>${escapeHtml(rating.Description)}</p>` : ''}
                        ${isOwnRating ? `
                        <div class="rating-actions">
                            <button class="btn-text" onclick="editRating(${rating.Id}, ${rating.Rating}, '${escapeHtml(rating.Description || '')}')">Edit</button>
                            <button class="btn-text btn-danger" onclick="deleteRating(${rating.Id})">Delete</button>
                        </div>
                        ` : ''}
                    </div>
                `}).join('')}
            `;
        } else {
            container.innerHTML = '<p class="empty-state">No ratings yet. Be the first to rate!</p>';
            // Show rating form if logged in and no ratings
            if (ratingForm && currentUser) {
                ratingForm.classList.remove('hidden');
            }
        }
    } catch (error) {
        container.innerHTML = '<p class="empty-state">Failed to load ratings.</p>';
    }
}

// File input change handler - hide TMDB preview when user selects their own file
document.getElementById('movie-poster')?.addEventListener('change', (e) => {
    if (e.target.files && e.target.files.length > 0) {
        // User selected a file, hide the TMDB preview
        document.getElementById('poster-preview-section').classList.add('hidden');
        // Also clear the TMDB URL so validation knows manual file takes priority
        document.getElementById('tmdb-poster-url').value = '';
    }
});

// Add movie form handler
document.getElementById('add-movie-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(e.target);
    const tmdbPosterUrl = formData.get('posterUrl');
    const errors = Validation.validateMovie(formData, tmdbPosterUrl);

    if (!Validation.showErrors(errors)) return;

    try {
        // Step 1: Upload poster file to Upload.php
        const posterFile = formData.get('poster');
        let posterFilename = '';

        if (posterFile && posterFile.size > 0) {
            // Manual file upload takes priority
            const uploadFormData = new FormData();
            uploadFormData.append('poster', posterFile);
            const uploadResult = await postFormData('Upload.php', uploadFormData);
            if (!uploadResult.success) {
                showToast(uploadResult.error || 'Failed to upload poster', 'error');
                return;
            }
            posterFilename = uploadResult.filename;
        } else if (tmdbPosterUrl) {
            // Server-side download from TMDB URL (for CORS-restricted images)
            const uploadFormData = new FormData();
            uploadFormData.append('posterUrl', tmdbPosterUrl);
            const uploadResult = await postFormData('Upload.php', uploadFormData);
            if (!uploadResult.success) {
                showToast(uploadResult.error || 'Failed to download TMDB poster', 'error');
                return;
            }
            posterFilename = uploadResult.filename;
        }

        // Step 2: Send movie data (with filename) to DB_Ops.php
        const movieData = {
            name: formData.get('name'),
            categories: formData.get('categories'),
            description: formData.get('description'),
            poster: posterFilename,
            userId: currentUser.id
        };

        const result = await post('DB_Ops.php?action=insertMovie', movieData);

        if (result.success) {
            showToast('Movie added successfully!', 'success');
            e.target.reset();
            clearPosterPreview();
            Validation.clearErrors();
            showView('movies');
        } else {
            showToast(result.message || 'Failed to add movie', 'error');
        }
    } catch (error) {
        showToast('Error adding movie', 'error');
    }
});

// ========================================
// Authentication
// ========================================

// Login form handler
document.getElementById('login-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const data = {
        email: document.getElementById('login-email').value.trim(),
        password: document.getElementById('login-password').value
    };
    
    const errors = Validation.validateLogin(data);
    if (!Validation.showErrors(errors)) return;
    
    try {
        const result = await post('DB_Ops.php?action=login', data);
        
        if (result.success) {
            currentUser = result.user;
            localStorage.setItem('movieTrackerUser', JSON.stringify(currentUser));
            await checkAdminStatus();
            showToast('Login successful!', 'success');
            updateAuthUI();
            showView('home');
        } else {
            showToast(result.error || 'Login failed. Please check your credentials.', 'error');
        }
    } catch (error) {
        showToast('Login error. Please try again.', 'error');
    }
});

// Signup form handler
document.getElementById('signup-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const data = {
        userName: document.getElementById('signup-username').value.trim(),
        email: document.getElementById('signup-email').value.trim(),
        password: document.getElementById('signup-password').value,
        birthDate: document.getElementById('signup-birthdate').value
    };
    
    console.log('Signup data:', data);
    
    const errors = Validation.validateSignup(data);
    console.log('Validation errors:', errors);
    
    if (!Validation.showErrors(errors)) return;
    
    try {
        const result = await post('DB_Ops.php?action=signup', data);
        
        if (result.success) {
            showToast('Account created! Please login.', 'success');
            showLoginForm();
            Validation.clearErrors();
        } else {
            // Show server error in form and toast
            const errorMsg = result.error || 'Signup failed.';
            showToast(errorMsg, 'error');
            
            // Try to show error in specific field
            if (errorMsg.toLowerCase().includes('email')) {
                const emailError = document.getElementById('signup-email-error');
                if (emailError) {
                    emailError.textContent = errorMsg;
                    emailError.classList.add('active');
                }
            } else if (errorMsg.toLowerCase().includes('username')) {
                const userError = document.getElementById('signup-username-error');
                if (userError) {
                    userError.textContent = errorMsg;
                    userError.classList.add('active');
                }
            }
        }
    } catch (error) {
        showToast('Signup error. Please try again.', 'error');
    }
});

// Toggle between login and signup forms
document.getElementById('show-signup')?.addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('login-form-container').classList.add('hidden');
    document.getElementById('signup-form-container').classList.remove('hidden');
    Validation.clearErrors();
});

document.getElementById('show-login')?.addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('signup-form-container').classList.add('hidden');
    document.getElementById('login-form-container').classList.remove('hidden');
    Validation.clearErrors();
});

function showLoginForm() {
    document.getElementById('signup-form-container').classList.add('hidden');
    document.getElementById('login-form-container').classList.remove('hidden');
}

/**
 * Update UI based on authentication state
 */
function updateAuthUI() {
    const authLink = document.getElementById('authLink');
    const wishlistLink = document.getElementById('wishlistLink');
    const addMovieLink = document.getElementById('addMovieLink');
    const profileLink = document.getElementById('profileLink');
    if (currentUser) {
        authLink.textContent = 'Logout';
        authLink.dataset.view = '';
        if (wishlistLink) wishlistLink.classList.remove('hidden');
        if (addMovieLink) addMovieLink.classList.toggle('hidden', !isAdmin);
        if (profileLink) profileLink.classList.remove('hidden');
    } else {
        authLink.textContent = 'Login';
        authLink.dataset.view = 'auth';
        if (wishlistLink) wishlistLink.classList.add('hidden');
        if (addMovieLink) addMovieLink.classList.add('hidden');
        if (profileLink) profileLink.classList.add('hidden');
    }
}

/**
 * Logout the current user
 */
function logout() {
    currentUser = null;
    isAdmin = false;
    localStorage.removeItem('movieTrackerUser');
    showToast('Logged out successfully', 'success');
    updateAuthUI();
    showView('home');
}

// ========================================
// Rating Operations
// ========================================

function showAddRatingForm() {
    if (!currentUser) {
        showToast('Please login to rate movies', 'error');
        showView('auth');
        return;
    }
    document.getElementById('add-rating-form').scrollIntoView({ behavior: 'smooth' });
}

// Star rating selection handler
document.querySelectorAll('input[name="rating"]').forEach(star => {
    star.addEventListener('change', (e) => {
        document.getElementById('selected-rating').textContent = `You rated: ${e.target.value}/10`;
    });
});

// Rating form handler
document.getElementById('rating-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    if (!currentUser) {
        showToast('Please login to rate', 'error');
        return;
    }
    
    const selectedStar = document.querySelector('input[name="rating"]:checked');
    const rating = selectedStar ? parseInt(selectedStar.value) : 0;
    
    const data = {
        movieId: currentMovieId,
        userId: currentUser.id,
        rating: rating,
        description: document.getElementById('rating-description').value.trim()
    };
    
    if (rating < 1 || rating > 10) {
        document.getElementById('rating-error').textContent = 'Please select a rating';
        return;
    }
    document.getElementById('rating-error').textContent = '';
    
    try {
        const result = await post('DB_Ops.php?action=addRating', data);
        
        if (result.success) {
            showToast('Rating added!', 'success');
            e.target.reset();
            Validation.clearErrors();
            loadMovieRatings(currentMovieId);
        } else {
            showToast(result.error || 'Failed to add rating', 'error');
        }
    } catch (error) {
        showToast('Error adding rating', 'error');
    }
});

/**
 * Load user profile data
 */
function loadUserProfile() {
    if (!currentUser) return;
    
    document.getElementById('profile-name').textContent = currentUser.userName;
    document.getElementById('profile-email').textContent = currentUser.email;
}

/**
 * Load ratings made by the current user
 * @param {number} userId - The user ID
 */
async function loadUserRatings(userId) {
    const container = document.getElementById('user-ratings');
    if (!container) return;
    
    container.innerHTML = '<div class="spinner"></div>';
    
    try {
        const result = await get(`DB_Ops.php?action=getRatingsByUser&userId=${userId}`);
        
        if (result.success && result.data && result.data.length > 0) {
            document.getElementById('total-ratings').textContent = result.totalRatings;
            
            container.innerHTML = result.data.map(rating => `
                <div class="rating-item" data-rating-id="${rating.Id}">
                    <div class="rating-header">
                        <span class="username">${escapeHtml(rating.movieName)}</span>
                        <span class="rating-value">${getStarDisplay(rating.Rating)}</span>
                    </div>
                    ${rating.Description ? `<p>${escapeHtml(rating.Description)}</p>` : ''}
                    <div class="rating-actions">
                        <button class="btn-text" onclick="editRating(${rating.Id}, ${rating.Rating}, '${escapeHtml(rating.Description || '')}')">Edit</button>
                        <button class="btn-text btn-danger" onclick="deleteRating(${rating.Id})">Delete</button>
                    </div>
                </div>
            `).join('');
        } else {
            document.getElementById('total-ratings').textContent = '0';
            container.innerHTML = '<p class="empty-state">No ratings yet. Start rating movies!</p>';
        }
    } catch (error) {
        container.innerHTML = '<p class="empty-state">Failed to load your ratings.</p>';
    }
}

// ========================================
// Rating Edit/Delete Operations
// ========================================

/**
 * Show edit rating modal
 * @param {number} ratingId - The rating ID
 * @param {number} currentRating - Current rating value
 * @param {string} currentDescription - Current description
 */
function editRating(ratingId, currentRating, currentDescription) {
    // Create modal if doesn't exist
    let modal = document.getElementById('edit-rating-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'edit-rating-modal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <h2>Edit Your Rating</h2>
                <form id="edit-rating-form">
                    <input type="hidden" id="edit-rating-id">
                    <div class="form-group rating-stars-container">
                        <label class="rating-label">Your Rating</label>
                        <div class="star-rating">
                            <input type="radio" id="edit-star10" name="edit-rating" value="10"><label for="edit-star10">★</label>
                            <input type="radio" id="edit-star9" name="edit-rating" value="9"><label for="edit-star9">★</label>
                            <input type="radio" id="edit-star8" name="edit-rating" value="8"><label for="edit-star8">★</label>
                            <input type="radio" id="edit-star7" name="edit-rating" value="7"><label for="edit-star7">★</label>
                            <input type="radio" id="edit-star6" name="edit-rating" value="6"><label for="edit-star6">★</label>
                            <input type="radio" id="edit-star5" name="edit-rating" value="5"><label for="edit-star5">★</label>
                            <input type="radio" id="edit-star4" name="edit-rating" value="4"><label for="edit-star4">★</label>
                            <input type="radio" id="edit-star3" name="edit-rating" value="3"><label for="edit-star3">★</label>
                            <input type="radio" id="edit-star2" name="edit-rating" value="2"><label for="edit-star2">★</label>
                            <input type="radio" id="edit-star1" name="edit-rating" value="1"><label for="edit-star1">★</label>
                        </div>
                        <span class="error-msg" id="edit-rating-error"></span>
                    </div>
                    <div class="form-group">
                        <label for="edit-rating-description">Your Review (Optional)</label>
                        <textarea id="edit-rating-description" rows="4" placeholder="Write your thoughts..."></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Update Rating</button>
                        <button type="button" class="btn-secondary" onclick="closeEditRatingModal()">Cancel</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);
        
        document.getElementById('edit-rating-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            await submitEditRating();
        });
    }
    
    // Populate form
    document.getElementById('edit-rating-id').value = ratingId;
    document.getElementById('edit-rating-description').value = currentDescription || '';
    document.getElementById('edit-rating-error').textContent = '';
    
    // Set star rating
    const star = document.getElementById(`edit-star${currentRating}`);
    if (star) star.checked = true;
    
    // Show modal
    modal.classList.add('active');
}

function closeEditRatingModal() {
    const modal = document.getElementById('edit-rating-modal');
    if (modal) modal.classList.remove('active');
}

async function submitEditRating() {
    const ratingId = document.getElementById('edit-rating-id').value;
    const selectedStar = document.querySelector('input[name="edit-rating"]:checked');
    const rating = selectedStar ? parseInt(selectedStar.value) : 0;
    const description = document.getElementById('edit-rating-description').value.trim();
    
    if (rating < 1 || rating > 10) {
        document.getElementById('edit-rating-error').textContent = 'Please select a rating';
        return;
    }
    
    try {
        const result = await post('DB_Ops.php?action=updateRating', {
            id: ratingId,
            rating: rating,
            description: description
        });
        
        if (result.success) {
            showToast('Rating updated!', 'success');
            closeEditRatingModal();
            if (currentUser) loadUserRatings(currentUser.id);
            if (currentMovieId) loadMovieRatings(currentMovieId);
        } else {
            document.getElementById('edit-rating-error').textContent = result.error || 'Failed to update';
        }
    } catch (error) {
        document.getElementById('edit-rating-error').textContent = 'Error updating rating';
    }
}

/**
 * Show delete rating confirmation modal
 * @param {number} ratingId - The rating ID
 */
function deleteRating(ratingId) {
    // Create modal if doesn't exist
    let modal = document.getElementById('delete-rating-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'delete-rating-modal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <h2>Delete Rating</h2>
                <p>Are you sure you want to delete this rating? This action cannot be undone.</p>
                <div class="form-actions">
                    <button type="button" class="btn-danger" onclick="confirmDeleteRating()">Delete</button>
                    <button type="button" class="btn-secondary" onclick="closeDeleteRatingModal()">Cancel</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Store rating ID
    modal.dataset.ratingId = ratingId;
    
    // Show modal
    modal.classList.add('active');
}

function closeDeleteRatingModal() {
    const modal = document.getElementById('delete-rating-modal');
    if (modal) modal.classList.remove('active');
}

async function confirmDeleteRating() {
    const modal = document.getElementById('delete-rating-modal');
    const ratingId = modal.dataset.ratingId;
    
    try {
        const result = await post('DB_Ops.php?action=deleteRating', {
            id: ratingId
        });
        
        if (result.success) {
            showToast('Rating deleted', 'success');
            closeDeleteRatingModal();
            // Remove from UI
            const ratingEl = document.querySelector(`[data-rating-id="${ratingId}"]`);
            if (ratingEl) ratingEl.remove();
            // Update count
            const totalEl = document.getElementById('total-ratings');
            if (totalEl) {
                const current = parseInt(totalEl.textContent) - 1;
                totalEl.textContent = current < 0 ? 0 : current;
            }
            // Also reload movie ratings if on detail view
            if (currentMovieId) {
                loadMovieRatings(currentMovieId);
            }
        } else {
            showToast(result.error || 'Failed to delete rating', 'error');
        }
    } catch (error) {
        showToast('Error deleting rating', 'error');
    }
}

// ========================================
// Wishlist Operations
// ========================================

/**
 * Load wishlist for the current user
 */
async function loadWishlist() {
    if (!currentUser) return;
    
    const container = document.getElementById('wishlist-container');
    const emptyMsg = document.getElementById('empty-wishlist');
    if (!container) return;
    
    container.innerHTML = '<div class="spinner"></div>';
    if (emptyMsg) emptyMsg.classList.add('hidden');
    
    try {
        const result = await get(`DB_Ops.php?action=getWishlistByUser&userId=${currentUser.id}`);
        
        if (result.success && result.data && result.data.length > 0) {
            container.innerHTML = result.data.map((movie, index) => {
                const wishlistPosterSrc = movie.poster ? `uploads/${movie.poster}` : 'https://via.placeholder.com/300x450?text=No+Poster';
                return `
                <div class="movie-card" style="animation-delay: ${index * 0.1}s">
                    <div class="poster-wrapper">
                        <img src="${wishlistPosterSrc}" alt="${escapeHtml(movie.name)}" loading="lazy">
                        <div class="overlay">
                            <h3>${escapeHtml(movie.name)}</h3>
                            <p class="categories">${escapeHtml(movie.categories)}</p>
                            <div class="card-actions">
                                <button class="btn-primary" onclick="showMovieDetail(${movie.MovieID})">View</button>
                                <button class="btn-secondary" onclick="event.stopPropagation(); removeFromWishlist(${movie.MovieID})">Remove</button>
                            </div>
                        </div>
                    </div>
                    <button class="wishlist-btn active" onclick="removeFromWishlist(${movie.MovieID})" title="Remove from wishlist">&#10084;</button>
                </div>
            `}).join('');
        } else {
            container.innerHTML = '';
            if (emptyMsg) emptyMsg.classList.remove('hidden');
        }
    } catch (error) {
        container.innerHTML = '<p class="empty-state">Failed to load wishlist.</p>';
    }
}

/**
 * Add movie to wishlist
 * @param {number} movieId - The movie ID
 */
async function addToWishlist(movieId) {
    if (!currentUser) {
        showToast('Please login to add to wishlist', 'error');
        showView('auth');
        return;
    }
    
    try {
        const result = await post('DB_Ops.php?action=addToWishlist', {
            movieId: movieId,
            userId: currentUser.id
        });
        
        if (result.success) {
            showToast('Added to wishlist!', 'success');
            updateWishlistButton(movieId, true);
            // Reload movies to refresh wishlist icons
            const moviesContainer = document.getElementById('movies-container');
            if (moviesContainer && document.getElementById('view-movies').classList.contains('active')) {
                loadMovies('movies-container');
            }
            const featuredContainer = document.getElementById('featured-movies');
            if (featuredContainer && document.getElementById('view-home').classList.contains('active')) {
                loadMovies('featured-movies');
            }
        } else {
            showToast(result.error || 'Failed to add', 'error');
        }
    } catch (error) {
        showToast('Error adding to wishlist', 'error');
    }
}

/**
 * Remove movie from wishlist
 * @param {number} movieId - The movie ID
 */
async function removeFromWishlist(movieId) {
    if (!currentUser) return;
    
    try {
        const result = await post('DB_Ops.php?action=removeFromWishlist', {
            movieId: movieId,
            userId: currentUser.id
        });
        
        if (result.success) {
            showToast('Removed from wishlist', 'success');
            // Reload wishlist if on wishlist view
            const wishlistView = document.getElementById('view-wishlist');
            if (wishlistView && wishlistView.classList.contains('active')) {
                loadWishlist();
            }
            updateWishlistButton(movieId, false);
            // Reload movies to refresh wishlist icons
            const moviesContainer = document.getElementById('movies-container');
            if (moviesContainer && document.getElementById('view-movies').classList.contains('active')) {
                loadMovies('movies-container');
            }
            const featuredContainer = document.getElementById('featured-movies');
            if (featuredContainer && document.getElementById('view-home').classList.contains('active')) {
                loadMovies('featured-movies');
            }
        } else {
            showToast(result.error || 'Failed to remove', 'error');
        }
    } catch (error) {
        showToast('Error removing from wishlist', 'error');
    }
}

/**
 * Update wishlist button appearance
 * @param {number} movieId - The movie ID
 * @param {boolean} isInWishlist - Whether movie is in wishlist
 */
function updateWishlistButton(movieId, isInWishlist) {
    // Update card buttons (heart icons only)
    const cardButtons = document.querySelectorAll(`[data-movie-id="${movieId}"].wishlist-btn`);
    cardButtons.forEach(btn => {
        btn.classList.toggle('active', isInWishlist);
        btn.innerHTML = isInWishlist ? '&#10084;' : '&#9825;';
        btn.title = isInWishlist ? 'Remove from wishlist' : 'Add to wishlist';
        // Update onclick handler
        btn.onclick = (e) => {
            e.stopPropagation();
            if (isInWishlist) {
                removeFromWishlist(movieId);
            } else {
                addToWishlist(movieId);
            }
        };
    });
    
    // Update detail page button (with text)
    const detailButton = document.querySelector(`[data-movie-id="${movieId}"].wishlist-btn-detail`);
    if (detailButton) {
        detailButton.classList.toggle('active', isInWishlist);
        detailButton.innerHTML = isInWishlist ? '&#10084; Remove from Wishlist' : '&#9825; Add to Wishlist';
        detailButton.onclick = () => {
            if (isInWishlist) {
                removeFromWishlist(movieId);
            } else {
                addToWishlist(movieId);
            }
        };
    }
}

// ========================================
// Search Movies
// ========================================

async function searchMovies() {
    const query = document.getElementById('movie-search').value.trim();
    const container = document.getElementById('movies-container');
    
    if (!query) {
        loadMovies('movies-container');
        return;
    }
    
    // Show loading state
    container.innerHTML = '<div class="spinner"></div>';
    
    // Load wishlist status if user is logged in
    let wishlistMovieIds = new Set();
    if (currentUser) {
        try {
            const wishlistResult = await get(`DB_Ops.php?action=getWishlistByUser&userId=${currentUser.id}`);
            if (wishlistResult.success && wishlistResult.data) {
                wishlistMovieIds = new Set(wishlistResult.data.map(m => m.MovieID));
            }
        } catch (e) {}
    }
    
    try {
        // Search local database first
        const localResult = await get(`DB_Ops.php?action=searchMovies&query=${encodeURIComponent(query)}`);
        
        if (localResult.success && localResult.data && localResult.data.length > 0) {
            // Display local results
            container.innerHTML = localResult.data.map((movie, index) => {
                const isInWishlist = wishlistMovieIds.has(parseInt(movie.id));
                const searchPosterSrc = movie.poster ? `uploads/${movie.poster}` : 'https://via.placeholder.com/300x450?text=No+Poster';
                return `
                <div class="movie-card" style="animation-delay: ${index * 0.1}s" onclick="showMovieDetail(${movie.id})">
                    <div class="poster-wrapper">
                        <img src="${searchPosterSrc}" alt="${escapeHtml(movie.name)}" loading="lazy">
                        <div class="overlay">
                            <h3>${escapeHtml(movie.name)}</h3>
                            <p class="categories">${escapeHtml(movie.categories)}</p>
                            <div class="card-actions">
                                <button class="btn-primary" onclick="event.stopPropagation(); showMovieDetail(${movie.id})">View Details</button>
                            </div>
                        </div>
                    </div>
                    ${currentUser ? `<button class="wishlist-btn ${isInWishlist ? 'active' : ''}" data-movie-id="${movie.id}" onclick="event.stopPropagation(); ${isInWishlist ? `removeFromWishlist(${movie.id})` : `addToWishlist(${movie.id})`}" title="${isInWishlist ? 'Remove from wishlist' : 'Add to wishlist'}">${isInWishlist ? '&#10084;' : '&#9825;'}</button>` : ''}
                </div>
            `}).join('');
        } else {
            // No local results - show message about adding from TMDB
            container.innerHTML = `
                <div class="empty-state full-width">
                    <p>No movies found in your library.</p>
                    <p class="mt-1">
                        <button class="btn-primary" onclick="showView('add')">Add a Movie</button>
                    </p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Search error:', error);
        container.innerHTML = '<p class="empty-state">Search failed. Please try again.</p>';
    }
}

// Search on Enter key
document.getElementById('movie-search')?.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') searchMovies();
});

// ========================================
// Utility Functions
// ========================================

/**
 * Show a toast notification
 * @param {string} message - The message to display
 * @param {string} type - The type: 'success', 'error', or 'info'
 */
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Escape HTML to prevent XSS attacks
 * @param {string} text - The text to escape
 * @returns {string} - Escaped HTML string
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ========================================
// Movie Edit/Delete Operations
// ========================================

/**
 * Show edit movie modal with pre-filled data
 * @param {number} movieId - The movie ID
 * @param {string} name - Current movie name
 * @param {string} categories - Current categories
 * @param {string} description - Current description
 */
function editMovie(movieId, name, categories, description) {
    // Create modal if doesn't exist
    let modal = document.getElementById('edit-movie-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'edit-movie-modal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <h2>Edit Movie</h2>
                <form id="edit-movie-form">
                    <input type="hidden" id="edit-movie-id">
                    <div class="form-group">
                        <label>Movie Name</label>
                        <input type="text" id="edit-movie-name" required>
                    </div>
                    <div class="form-group">
                        <label>Categories</label>
                        <input type="text" id="edit-movie-categories" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="edit-movie-description" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>New Poster (optional)</label>
                        <input type="file" id="edit-movie-poster" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Save Changes</button>
                        <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Add form submit handler
        document.getElementById('edit-movie-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            await submitMovieEdit();
        });
    }
    
    // Populate form
    document.getElementById('edit-movie-id').value = movieId;
    document.getElementById('edit-movie-name').value = name;
    document.getElementById('edit-movie-categories').value = categories;
    document.getElementById('edit-movie-description').value = description;
    document.getElementById('edit-movie-poster').value = '';
    
    // Show modal
    modal.classList.add('active');
}

/**
 * Close edit movie modal
 */
function closeEditModal() {
    const modal = document.getElementById('edit-movie-modal');
    if (modal) modal.classList.remove('active');
}

/**
 * Submit movie edit form
 */
async function submitMovieEdit() {
    const movieId = document.getElementById('edit-movie-id').value;
    const name = document.getElementById('edit-movie-name').value.trim();
    const categories = document.getElementById('edit-movie-categories').value.trim();
    const description = document.getElementById('edit-movie-description').value.trim();
    const posterFile = document.getElementById('edit-movie-poster').files[0];
    
    if (!name || !categories || !description) {
        showToast('All fields except poster are required', 'error');
        return;
    }
    
    try {
        // Step 1: Upload poster file if provided
        let posterFilename = null;
        if (posterFile) {
            const uploadFormData = new FormData();
            uploadFormData.append('poster', posterFile);
            const uploadResult = await postFormData('Upload.php', uploadFormData);
            if (!uploadResult.success) {
                showToast(uploadResult.error || 'Failed to upload poster', 'error');
                return;
            }
            posterFilename = uploadResult.filename;
        }
        
        // Step 2: Send movie data (with filename) to DB_Ops.php
        const movieData = {
            id: movieId,
            name: name,
            categories: categories,
            description: description,
            poster: posterFilename,
            userId: currentUser.id
        };
        
        const result = await post('DB_Ops.php?action=updateMovie', movieData);
        
        if (result.success) {
            showToast('Movie updated successfully!', 'success');
            closeEditModal();
            // Reload current view
            if (currentMovieId == movieId) {
                showMovieDetail(movieId);
            } else {
                loadMovies('movies-container');
            }
        } else {
            showToast(result.message || 'Failed to update movie', 'error');
        }
    } catch (error) {
        showToast('Error updating movie', 'error');
    }
}

/**
 * Show delete movie confirmation modal
 * @param {number} movieId - The movie ID
 */
function deleteMovie(movieId) {
    // Create modal if doesn't exist
    let modal = document.getElementById('delete-movie-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'delete-movie-modal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <h2>Delete Movie</h2>
                <p>Are you sure you want to delete this movie? This will also delete all ratings for this movie. This action cannot be undone.</p>
                <div class="form-actions">
                    <button type="button" class="btn-danger" onclick="confirmDeleteMovie()">Delete</button>
                    <button type="button" class="btn-secondary" onclick="closeDeleteMovieModal()">Cancel</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Store movie ID
    modal.dataset.movieId = movieId;
    
    // Show modal
    modal.classList.add('active');
}

function closeDeleteMovieModal() {
    const modal = document.getElementById('delete-movie-modal');
    if (modal) modal.classList.remove('active');
}

async function confirmDeleteMovie() {
    const modal = document.getElementById('delete-movie-modal');
    const movieId = modal.dataset.movieId;
    
    try {
        const result = await post('DB_Ops.php?action=deleteMovie', { id: movieId, userId: currentUser.id });
        
        if (result.success) {
            showToast('Movie deleted successfully', 'success');
            closeDeleteMovieModal();
            showView('movies');
            loadMovies('movies-container');
        } else {
            showToast(result.message || 'Failed to delete movie', 'error');
        }
    } catch (error) {
        showToast('Error deleting movie', 'error');
    }
}

// ========================================
// Initialization
// ========================================

// Restore user session on page load
if (currentUser) {
    checkAdminStatus().then(() => {
        updateAuthUI();
    });
} else {
    updateAuthUI();
}

// Show home view by default
showView('home');