<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TMDBController extends Controller
{
    /**
     * The TMDB API base URL.
     */
    const TMDB_BASE_URL = 'https://api.themoviedb.org/3';

    /**
     * The TMDB image base URL.
     */
    const TMDB_IMAGE_BASE_URL = 'https://image.tmdb.org/t/p/w500';

    /**
     * Search for movies on TMDB (AJAX).
     */
    public function search(Request $request)
    {
        $query = $request->query('query');

        if (empty($query)) {
            return response()->json(['success' => false, 'error' => 'Search query is required.']);
        }

        $apiKey = config('services.tmdb.key');
        $url = self::TMDB_BASE_URL . '/search/movie?api_key=' . $apiKey . '&query=' . urlencode($query) . '&language=en-US&page=1';

        $result = $this->fetchUrl($url);
        $httpCode = $result['httpCode'] ?? 0;
        $data = $result['data'] ?? null;

        if ($httpCode !== 200 || !$data) {
            return response()->json(['success' => false, 'error' => 'TMDB API request failed.']);
        }

        $formattedResults = [];
        if (isset($data['results']) && is_array($data['results'])) {
            foreach ($data['results'] as $movie) {
                $formattedResults[] = [
                    'id'           => $movie['id'],
                    'title'        => e($movie['title'] ?? ''),
                    'year'         => !empty($movie['release_date']) ? substr($movie['release_date'], 0, 4) : 'N/A',
                    'poster'       => !empty($movie['poster_path']) ? self::TMDB_IMAGE_BASE_URL . $movie['poster_path'] : '',
                    'overview'     => e($movie['overview'] ?? ''),
                    'vote_average' => $movie['vote_average'] ?? 0,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $formattedResults,
        ]);
    }

    /**
     * Get movie details from TMDB (AJAX).
     */
    public function detail($id)
    {
        if (empty($id) || !is_numeric($id)) {
            return response()->json(['success' => false, 'error' => 'Valid movie ID is required.']);
        }

        $apiKey = config('services.tmdb.key');
        $url = self::TMDB_BASE_URL . '/movie/' . $id . '?api_key=' . $apiKey . '&language=en-US';

        $result = $this->fetchUrl($url);
        $httpCode = $result['httpCode'] ?? 0;
        $data = $result['data'] ?? null;

        if ($httpCode !== 200 || !$data) {
            return response()->json(['success' => false, 'error' => 'TMDB API request failed.']);
        }

        $formattedDetail = [
            'id'                   => $data['id'] ?? 0,
            'title'                => e($data['title'] ?? ''),
            'original_title'       => e($data['original_title'] ?? ''),
            'overview'             => e($data['overview'] ?? ''),
            'release_date'         => e($data['release_date'] ?? ''),
            'runtime'              => $data['runtime'] ?? 0,
            'vote_average'         => $data['vote_average'] ?? 0,
            'vote_count'           => $data['vote_count'] ?? 0,
            'poster_path'          => !empty($data['poster_path'])
                ? self::TMDB_IMAGE_BASE_URL . $data['poster_path']
                : '',
            'genres'               => isset($data['genres']) && is_array($data['genres'])
                ? array_column($data['genres'], 'name')
                : [],
            'production_companies' => isset($data['production_companies']) && is_array($data['production_companies'])
                ? array_column($data['production_companies'], 'name')
                : [],
        ];

        return response()->json([
            'success' => true,
            'data'    => $formattedDetail,
        ]);
    }

    /**
     * Helper to fetch a URL via cURL.
     */
    private function fetchUrl($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$data) {
            return ['httpCode' => $httpCode, 'data' => null];
        }

        $decoded = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['httpCode' => $httpCode, 'data' => null];
        }

        return ['httpCode' => $httpCode, 'data' => $decoded];
    }
}