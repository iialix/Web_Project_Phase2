<?php

namespace App\Http\Controllers;

use App\Services\TMDBService;
use Illuminate\Http\Request;


class TMDBController extends Controller
{
    protected $tmdbService;

    public function __construct(TMDBService $tmdbService)
    {
        $this->tmdbService = $tmdbService;
    }
    public function search(Request $request)
    {
        $query = $request->query('query', '');

        $result = $this->tmdbService->searchMovies($query);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error'   => $result['error'],
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => $result['data'],
        ]);
    }
    public function detail($id)
    {
        if (empty($id) || !is_numeric($id)) {
            return response()->json([
                'success' => false,
                'error'   => 'Valid movie ID is required.',
            ]);
        }

        $result = $this->tmdbService->getMovieDetail((int) $id);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error'   => $result['error'],
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => $result['data'],
        ]);
    }
}