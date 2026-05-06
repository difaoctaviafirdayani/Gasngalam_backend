<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            ->get()
            ->map(function ($r) {
                $r->photo_full_url = $r->photo_url
                    ? asset('storage/' . $r->photo_url)
                    : null;
                return $r;
            });

        return response()->json($reviews);
    }

    /**
     * POST /api/destinations/{id}/reviews
     * Menerima multipart/form-data karena ada upload foto opsional
     */
    public function store(Request $request, $destinationId)
    {
        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:5',
            'photo'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        // Cek sudah pernah review
        $exists = Review::where('user_id', $request->user()->id)
            ->where('destination_id', $destinationId)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Anda sudah memberikan ulasan untuk destinasi ini.'], 422);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('reviews', 'public');
        }

        $review = Review::create([
            'user_id'        => $request->user()->id,
            'destination_id' => $destinationId,
            'rating'         => $request->rating,
            'comment'        => $request->comment,
            'photo_url'      => $photoPath,
        ]);

        $this->updateDestinationRating($destinationId);

        $review->load('user:id,name');
        $review->photo_full_url = $review->photo_url
            ? asset('storage/' . $review->photo_url)
            : null;

        return response()->json($review, 201);
    }

    /**
     * POST /api/reviews/{id}/report  (user login)
     * User melaporkan review untuk ditinjau admin
     */
    public function report(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        // Jangan bisa laporkan review sendiri
        if ($review->user_id === $request->user()->id) {
            return response()->json(['message' => 'Tidak bisa melaporkan ulasan sendiri.'], 422);
        }

        $review->update(['is_reported' => true]);
        return response()->json(['message' => 'Ulasan berhasil dilaporkan. Admin akan meninjau.']);
    }

    /**
     * DELETE /api/admin/reviews/{id}  (admin only)
     */
    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        $destinationId = $review->destination_id;

        if ($review->photo_url) {
            Storage::disk('public')->delete($review->photo_url);
        }

        $review->delete();
        $this->updateDestinationRating($destinationId);

        return response()->json(['message' => 'Ulasan berhasil dihapus']);
    }

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