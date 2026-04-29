<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * GET /api/favorites
     * Daftar destinasi favorit user login
     */
    public function index(Request $request)
    {
        $favorites = Favorite::with('destination')
            ->where('user_id', $request->user()->id)
            ->get()
            ->pluck('destination')
            ->filter()
            ->values();

        return response()->json($favorites);
    }

    /**
     * GET /api/favorites/ids
     * Ambil semua destination_id favorit (untuk state frontend)
     */
    public function ids(Request $request)
    {
        $ids = Favorite::where('user_id', $request->user()->id)
            ->pluck('destination_id');

        return response()->json($ids);
    }

    /**
     * POST /api/favorites/{destinationId}
     * Tambah destinasi ke favorit
     */
    public function store(Request $request, $destinationId)
    {
        $favorite = Favorite::firstOrCreate([
            'user_id'        => $request->user()->id,
            'destination_id' => $destinationId,
        ]);

        return response()->json([
            'message' => 'Berhasil ditambahkan ke favorit',
            'id'      => $favorite->id,
        ]);
    }

    /**
     * DELETE /api/favorites/{destinationId}
     * Hapus destinasi dari favorit
     */
    public function destroy(Request $request, $destinationId)
    {
        Favorite::where('user_id', $request->user()->id)
            ->where('destination_id', $destinationId)
            ->delete();

        return response()->json(['message' => 'Dihapus dari favorit']);
    }
}
