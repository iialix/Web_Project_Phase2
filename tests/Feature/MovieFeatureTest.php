<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Movie;

class MovieFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_displays_movies()
    {
        $movie = Movie::create([
            'name' => 'Inception',
            'categories' => 'Sci-Fi',
            'description' => 'A thief who steals corporate secrets through the use of dream-sharing technology.',
            'poster' => null,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Inception');
    }

    public function test_guest_cannot_access_create_movie_page()
    {
        $response = $this->get('/movies/create');

        // Based on MovieController logic, it redirects unauthenticated users to movies.index
        $response->assertRedirect('/movies');
    }

    public function test_admin_can_access_create_movie_page()
    {
        $admin = User::create([
            'UserName' => 'AdminUser',
            'Email' => 'admin@example.com',
            'Password' => bcrypt('password'),
            'BirthDate' => '1990-01-01',
            'IsAdmin' => true,
        ]);

        $response = $this->actingAs($admin)->get('/movies/create');

        $response->assertStatus(200);
        $response->assertSee('Add New Movie');
    }

    public function test_admin_can_create_a_movie()
    {
        $admin = User::create([
            'UserName' => 'AdminUser',
            'Email' => 'admin@example.com',
            'Password' => bcrypt('password'),
            'BirthDate' => '1990-01-01',
            'IsAdmin' => true,
        ]);

        $response = $this->actingAs($admin)->post('/movies', [
            'name' => 'The Matrix',
            'categories' => 'Action, Sci-Fi',
            'description' => 'A computer hacker learns from mysterious rebels about the true nature of his reality.',
        ]);

        $movie = Movie::where('name', 'The Matrix')->first();

        $response->assertRedirect('/movies/' . $movie->id);
        $this->assertDatabaseHas('movies', [
            'name' => 'The Matrix',
        ]);
    }
}
