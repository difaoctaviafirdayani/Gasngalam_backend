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

    public function users()
    {
        $users = User::select('id', 'name', 'email', 'phone', 'role', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($users);
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Tidak bisa menghapus admin'], 403);
        }
        $user->delete();
        return response()->json(['message' => 'User berhasil dihapus']);
    }

    public function reviews(Request $request)
    {
        $appUrl = rtrim(env('APP_URL', 'http://127.0.0.1:8000'), '/');

        $query = Review::with(['user:id,name', 'destination:id,name']);

        if ($request->boolean('reported')) {
            $query->where('is_reported', true);
        }

        $reviews = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($r) use ($appUrl) {
                $r->photo_full_url = $r->photo_url
                    ? $appUrl . '/storage/' . $r->photo_url
                    : null;
                return $r;
            });

        return response()->json($reviews);
    }

    public function toggleReport($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['is_reported' => !$review->is_reported]);
        return response()->json($review);
    }
}