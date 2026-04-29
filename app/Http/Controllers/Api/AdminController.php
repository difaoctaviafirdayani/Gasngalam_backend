<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Review;
use App\Models\BusinessClaim;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * GET /api/admin/stats
     * Statistik dashboard admin
     */
    public function stats()
    {
        return response()->json([
            'total_destinations' => Destination::count(),
            'total_users'        => User::where('role', 'user')->count(),
            'total_reviews'      => Review::count(),
            'pending_claims'     => BusinessClaim::where('status', 'pending')->count(),
            'reported_reviews'   => Review::where('is_reported', true)->count(),
        ]);
    }

    /**
     * GET /api/admin/users
     * List semua user
     */
    public function users()
    {
        $users = User::select('id', 'name', 'email', 'phone', 'role', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($users);
    }

    /**
     * DELETE /api/admin/users/{id}
     * Hapus user
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json(['message' => 'Tidak bisa menghapus admin'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'User berhasil dihapus']);
    }

    /**
     * GET /api/admin/reviews
     * List semua ulasan (admin bisa lihat semua, termasuk yang dilaporkan)
     */
    public function reviews(Request $request)
    {
        $query = Review::with(['user:id,name', 'destination:id,name']);

        if ($request->boolean('reported')) {
            $query->where('is_reported', true);
        }

        return response()->json($query->latest()->get());
    }

    /**
     * PATCH /api/admin/reviews/{id}/report
     * Toggle flag laporan ulasan
     */
    public function toggleReport($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['is_reported' => !$review->is_reported]);
        return response()->json($review);
    }
}
