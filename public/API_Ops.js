/**
 * Movie Tracker - API Operations Module
 * Handles integration with external movie APIs via backend
 */

const API = {
    /**
     * Search movies using external API through backend
     * @param {string} query - The search query
     * @returns {Promise<Array>} - Array of movie objects
     */
    async searchMovies(query) {
        try {
            const response = await fetch(`API_Ops.php?action=search&query=${encodeURIComponent(query)}`);
            const data = await response.json();
            return data.data || [];
        } catch (error) {
            console.error('API search error:', error);
            showToast('API search failed', 'error');
            throw error;
        }
    },

    /**
     * Get movie details from external API
     * @param {number|string} movieId - The movie ID from external API
     * @returns {Promise<Object>} - Movie details object
     */
    async getMovieDetails(movieId) {
        try {
            const response = await fetch(`API_Ops.php?action=detail&id=${encodeURIComponent(movieId)}`);
            return await response.json();
        } catch (error) {
            console.error('API detail error:', error);
            showToast('Failed to fetch movie details', 'error');
            throw error;
        }
    },

};
