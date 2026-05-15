<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Movie;
use App\Models\Rating;
use App\Models\User;

/**
 * Unit Tests — isolated model logic.
 *
 * Covers:
 *  1. Movie::scopeSearch() matches on name.
 *  2. Movie::scopeSearch() matches on categories.
 *  3. Movie::scopeSearch() returns nothing for a non-matching term.
 *  4. Rating value must be between 1 and 10 (validation rules).
 *  5. Movie has-many Ratings relationship.
 */
class MovieRatingUnitTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function createMovie(array $data = []): Movie
    {
        return Movie::create(array_merge([
            'name'        => 'Default Movie',
            'categories'  => 'Drama',
            'description' => 'A default description for testing.',
            'poster'      => null,
        ], $data));
    }

    private function createUser(): User
    {
        return User::create([
            'UserName' => 'UnitTester',
            'Email'    => 'unit@example.com',
            'Password' => bcrypt('secret'),
            'BirthDate'=> '1990-01-01',
        ]);
    }

    // ─── Movie Scope Tests ────────────────────────────────────────────────────

    /**
     * TEST 1 (Unit)
     * scopeSearch() finds a movie when the search term matches its name.
     */
    public function test_scope_search_finds_movie_by_name()
    {
        $this->createMovie(['name' => 'The Dark Knight', 'categories' => 'Action']);
        $this->createMovie(['name' => 'Toy Story',       'categories' => 'Animation']);

        $results = Movie::search('Dark Knight')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('The Dark Knight', $results->first()->name);
    }

    /**
     * TEST 2 (Unit)
     * scopeSearch() finds a movie when the search term matches its categories.
     */
    public function test_scope_search_finds_movie_by_category()
    {
        $this->createMovie(['name' => 'Finding Nemo',  'categories' => 'Animation, Family']);
        $this->createMovie(['name' => 'The Godfather', 'categories' => 'Crime, Drama']);

        $results = Movie::search('Animation')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Finding Nemo', $results->first()->name);
    }

    /**
     * TEST 3 (Unit)
     * scopeSearch() returns an empty collection when no movies match.
     */
    public function test_scope_search_returns_empty_for_no_match()
    {
        $this->createMovie(['name' => 'Inception', 'categories' => 'Sci-Fi']);

        $results = Movie::search('zzz_no_match_zzz')->get();

        $this->assertCount(0, $results);
    }

    // ─── Rating Validation Rules (Unit) ──────────────────────────────────────

    /**
     * TEST 4 (Unit)
     * The rating validation rules reject values outside 1–10.
     * We test the rules array in isolation — no HTTP call needed.
     */
    public function test_rating_value_must_be_between_1_and_10()
    {
        $rules = [
            'movieId' => 'required|integer',
            'rating'  => 'required|integer|between:1,10',
        ];

        $validator = \Illuminate\Support\Facades\Validator::make(
            ['movieId' => 1, 'rating' => 0],
            $rules
        );
        $this->assertTrue($validator->fails(), 'Rating of 0 should fail validation.');

        $validator = \Illuminate\Support\Facades\Validator::make(
            ['movieId' => 1, 'rating' => 11],
            $rules
        );
        $this->assertTrue($validator->fails(), 'Rating of 11 should fail validation.');

        $validator = \Illuminate\Support\Facades\Validator::make(
            ['movieId' => 1, 'rating' => 7],
            $rules
        );
        $this->assertFalse($validator->fails(), 'Rating of 7 should pass validation.');
    }

    // ─── Eloquent Relationship Test ───────────────────────────────────────────

    /**
     * TEST 5 (Unit)
     * A Movie returns its related Ratings through the hasMany relationship.
     */
    public function test_movie_has_many_ratings_relationship()
    {
        $user  = $this->createUser();
        $movie = $this->createMovie(['name' => 'Pulp Fiction', 'categories' => 'Crime']);

        Rating::create([
            'MovieID'     => $movie->id,
            'UserID'      => $user->id,
            'Rating'      => 9,
            'Description' => 'A masterpiece.',
        ]);

        $loadedMovie = Movie::with('ratings')->find($movie->id);

        $this->assertCount(1, $loadedMovie->ratings);
        $this->assertEquals(9, $loadedMovie->ratings->first()->Rating);
    }
}
