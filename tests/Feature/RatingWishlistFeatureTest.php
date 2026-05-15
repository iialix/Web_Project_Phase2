<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Movie;
use App\Models\Rating;
use App\Models\Wishlist;

/**
 * Feature Tests — Rating & Wishlist HTTP endpoints (end-to-end).
 *
 * Covers:
 *  1. Authenticated user can submit a rating and it appears in the DB.
 *  2. Guest cannot submit a rating (401 JSON response).
 *  3. Duplicate rating is rejected with 409.
 *  4. Authenticated user can add a movie to their wishlist.
 *  5. Guest is redirected away from the wishlist page.
 *  6. Removing a movie from the wishlist returns success JSON.
 */
class RatingWishlistFeatureTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /** Create a regular (non-admin) user. */
    private function makeUser(array $overrides = []): User
    {
        // Create a dummy "first" user first so our test user is NOT the admin.
        User::create([
            'UserName' => 'FirstEver',
            'Email'    => 'first@example.com',
            'Password' => bcrypt('secret'),
            'BirthDate'=> '1980-01-01',
        ]);

        return User::create(array_merge([
            'UserName' => 'TestUser',
            'Email'    => 'testuser@example.com',
            'Password' => bcrypt('secret'),
            'BirthDate'=> '1995-06-15',
        ], $overrides));
    }

    /** Create a sample movie. */
    private function makeMovie(array $overrides = []): Movie
    {
        return Movie::create(array_merge([
            'name'        => 'Interstellar',
            'categories'  => 'Sci-Fi, Drama',
            'description' => 'A team of explorers travel through a wormhole in space.',
            'poster'      => null,
        ], $overrides));
    }

    // ─── Rating Feature Tests ─────────────────────────────────────────────────

    /**
     * TEST 1 (Feature)
     * An authenticated user can POST /ratings, which saves a record to the DB
     * and returns a success JSON response.
     */
    public function test_authenticated_user_can_submit_a_rating()
    {
        $user  = $this->makeUser();
        $movie = $this->makeMovie();

        $response = $this->actingAs($user)->postJson('/ratings', [
            'movieId'     => $movie->id,
            'rating'      => 8,
            'description' => 'Amazing cinematography!',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Rating added successfully.',
                 ]);

        $this->assertDatabaseHas('ratings', [
            'MovieID'     => $movie->id,
            'UserID'      => $user->id,
            'Rating'      => 8,
            'Description' => 'Amazing cinematography!',
        ]);
    }

    /**
     * TEST 2 (Feature)
     * A guest (unauthenticated) user POSTing to /ratings should receive
     * a 401 JSON error — not a redirect.
     */
    public function test_guest_cannot_submit_a_rating()
    {
        $movie = $this->makeMovie();

        $response = $this->postJson('/ratings', [
            'movieId' => $movie->id,
            'rating'  => 7,
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                 ]);

        $this->assertDatabaseMissing('ratings', ['MovieID' => $movie->id]);
    }

    /**
     * TEST 3 (Feature)
     * A user who has already rated a movie cannot rate it again; the
     * endpoint returns 409 with an error message.
     */
    public function test_duplicate_rating_is_rejected_with_409()
    {
        $user  = $this->makeUser();
        $movie = $this->makeMovie();

        // First rating — should succeed.
        $this->actingAs($user)->postJson('/ratings', [
            'movieId' => $movie->id,
            'rating'  => 5,
        ]);

        // Second rating for the same movie — should be rejected.
        $response = $this->actingAs($user)->postJson('/ratings', [
            'movieId' => $movie->id,
            'rating'  => 9,
        ]);

        $response->assertStatus(409)
                 ->assertJson([
                     'success' => false,
                     'error'   => 'You have already rated this movie.',
                 ]);

        // Only one rating should exist.
        $this->assertCount(1, Rating::where('MovieID', $movie->id)->get());
    }

    // ─── Wishlist Feature Tests ───────────────────────────────────────────────

    /**
     * TEST 4 (Feature)
     * An authenticated user can add a movie to their wishlist and a DB
     * record is created.
     */
    public function test_authenticated_user_can_add_movie_to_wishlist()
    {
        $user  = $this->makeUser();
        $movie = $this->makeMovie();

        $response = $this->actingAs($user)->postJson('/wishlist', [
            'movieId' => $movie->id,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Added to wishlist successfully.',
                 ]);

        $this->assertDatabaseHas('wishlists', [
            'MovieID' => $movie->id,
            'UserID'  => $user->id,
        ]);
    }

    /**
     * TEST 5 (Feature)
     * A guest visiting GET /wishlist is redirected to the login page.
     */
    public function test_guest_is_redirected_from_wishlist_page()
    {
        $response = $this->get('/wishlist');

        $response->assertRedirect('/login');
    }

    /**
     * TEST 6 (Feature)
     * An authenticated user can remove a movie from their wishlist via
     * DELETE /wishlist/{movie}.
     */
    public function test_authenticated_user_can_remove_movie_from_wishlist()
    {
        $user  = $this->makeUser();
        $movie = $this->makeMovie();

        // Seed the wishlist entry directly.
        Wishlist::create([
            'MovieID' => $movie->id,
            'UserID'  => $user->id,
        ]);

        $response = $this->actingAs($user)->deleteJson("/wishlist/{$movie->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Removed from wishlist successfully.',
                 ]);

        $this->assertDatabaseMissing('wishlists', [
            'MovieID' => $movie->id,
            'UserID'  => $user->id,
        ]);
    }
}
