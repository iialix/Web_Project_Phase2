<?php

namespace App\Services;
class TMDBService
{
    const BASE_URL = 'https://api.themoviedb.org/3';
    const IMAGE_BASE_URL = 'https://image.tmdb.org/t/p/w500';
    public function searchMovies(string $query): array
    {
        if (empty(trim($query))) {
            return ['success' => false, 'data' => null, 'error' => 'Search query is required.'];
        }

        $apiKey = config('services.tmdb.key');

        if (empty($apiKey)) {
            return [
                'success' => false,
                'data'    => null,
                'error'   => 'TMDB API key is not configured. Please add TMDB_API_KEY to your .env file.',
            ];
        }

        $url = self::BASE_URL . '/search/movie?api_key=' . $apiKey
             . '&query=' . urlencode($query)
             . '&language=en-US&page=1';

        $result = $this->fetchUrl($url);

        if ($result['httpCode'] !== 200 || !$result['data']) {
            return [
                'success' => false,
                'data'    => null,
                'error'   => 'TMDB API is currently unavailable. Please try again later or fill in details manually.',
            ];
        }

        $formatted = [];
        $results = $result['data']['results'] ?? [];

        foreach ($results as $movie) {
            $formatted[] = [
                'id'           => $movie['id'],
                'title'        => e($movie['title'] ?? ''),
                'year'         => !empty($movie['release_date']) ? substr($movie['release_date'], 0, 4) : 'N/A',
                'poster'       => !empty($movie['poster_path']) ? self::IMAGE_BASE_URL . $movie['poster_path'] : '',
                'overview'     => e($movie['overview'] ?? ''),
                'vote_average' => $movie['vote_average'] ?? 0,
            ];
        }

        return ['success' => true, 'data' => $formatted, 'error' => null];
    }
    public function getMovieDetail(int $tmdbId): array
    {
        if ($tmdbId <= 0) {
            return ['success' => false, 'data' => null, 'error' => 'Valid movie ID is required.'];
        }

        $apiKey = config('services.tmdb.key');

        if (empty($apiKey)) {
            return [
                'success' => false,
                'data'    => null,
                'error'   => 'TMDB API key is not configured. Please add TMDB_API_KEY to your .env file.',
            ];
        }

        $url = self::BASE_URL . '/movie/' . $tmdbId
             . '?api_key=' . $apiKey
             . '&language=en-US';

        $result = $this->fetchUrl($url);

        if ($result['httpCode'] !== 200 || !$result['data']) {
            return [
                'success' => false,
                'data'    => null,
                'error'   => 'Could not fetch movie details from TMDB. The API may be unavailable.',
            ];
        }

        $data = $result['data'];

        $formatted = [
            'id'                   => $data['id'] ?? 0,
            'title'                => e($data['title'] ?? ''),
            'original_title'       => e($data['original_title'] ?? ''),
            'overview'             => e($data['overview'] ?? ''),
            'release_date'         => e($data['release_date'] ?? ''),
            'runtime'              => $data['runtime'] ?? 0,
            'vote_average'         => $data['vote_average'] ?? 0,
            'vote_count'           => $data['vote_count'] ?? 0,
            'poster_path'          => !empty($data['poster_path'])
                ? self::IMAGE_BASE_URL . $data['poster_path']
                : '',
            'genres'               => isset($data['genres']) && is_array($data['genres'])
                ? array_column($data['genres'], 'name')
                : [],
            'production_companies' => isset($data['production_companies']) && is_array($data['production_companies'])
                ? array_column($data['production_companies'], 'name')
                : [],
        ];

        return ['success' => true, 'data' => $formatted, 'error' => null];
    }
    private function fetchUrl(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $data     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr || $httpCode !== 200 || !$data) {
            return ['httpCode' => $httpCode ?: 0, 'data' => null];
        }

        $decoded = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['httpCode' => $httpCode, 'data' => null];
        }

        return ['httpCode' => $httpCode, 'data' => $decoded];
    }
}
