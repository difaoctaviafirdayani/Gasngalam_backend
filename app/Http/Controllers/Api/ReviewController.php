<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Destination;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * GET /api/destinations/{id}/reviews
     */
    public function index($destinationId)
    {
        $reviews = Review::with('user:id,name')
            ->where('destination_id', $destinationId)
            ->latest()
            ->get();

        return response()->json($reviews);
    }

    /**
     * POST /api/destinations/{id}/reviews
     */
    public function store(Request $request, $destinationId)
    {
        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:5',
        ]);

        // Cek sudah pernah review
        $exists = Review::where('user_id', $request->user()->id)
            ->where('destination_id', $destinationId)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Anda sudah memberikan ulasan untuk destinasi ini.'], 422);
        }

        $review = Review::create([
            'user_id'        => $request->user()->id,
            'destination_id' => $destinationId,
            'rating'         => $request->rating,
            'comment'        => $request->comment,
        ]);

        // Update rata-rata rating di tabel destinations
        $this->updateDestinationRating($destinationId);

        return response()->json($review->load('user:id,name'), 201);
    }

    /**
     * DELETE /api/admin/reviews/{id}  (admin only)
     */
    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        $destinationId = $review->destination_id;

        $review->delete();

        $this->updateDestinationRating($destinationId);

        return response()->json(['message' => 'Ulasan berhasil dihapus']);
    }

    /**
     * Helper: recalculate destination rating after review changes
     */
    private function updateDestinationRating($destinationId)
    {
        $avg   = Review::where('destination_id', $destinationId)->avg('rating') ?? 0;
        $count = Review::where('destination_id', $destinationId)->count();

        Destination::where('id', $destinationId)->update([
            'rating'       => round($avg, 1),
            'review_count' => $count,
        ]);
    }
}
