<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    private function photoUrl(?string $path): ?string
    {
        if (!$path) return null;
        return rtrim(env('APP_URL', 'http://127.0.0.1:8000'), '/') . '/storage/' . $path;
    }

    public function index($destinationId)
    {
        $reviews = Review::with('user:id,name')
            ->where('destination_id', $destinationId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($r) {
                $r->photo_full_url = $this->photoUrl($r->photo_url);
                return $r;
            });

        return response()->json($reviews);
    }

    public function myReviews(Request $request)
    {
        $reviews = Review::with('destination:id,name,location')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($r) {
                $r->photo_full_url = $this->photoUrl($r->photo_url);
                return $r;
            });

        return response()->json($reviews);
    }

    public function store(Request $request, $destinationId)
    {
        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:5',
            'photo'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

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

        $destination = Destination::findOrFail($destinationId);
        $avgRating   = Review::where('destination_id', $destinationId)->avg('rating');
        $count       = Review::where('destination_id', $destinationId)->count();
        $destination->update([
            'rating'       => round($avgRating, 1),
            'review_count' => $count,
        ]);

        $review->load('user:id,name');
        $review->photo_full_url = $this->photoUrl($photoPath);

        return response()->json($review, 201);
    }

    public function report(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        $review->update(['is_reported' => true]);
        return response()->json(['message' => 'Ulasan berhasil dilaporkan.']);
    }

    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        $destinationId = $review->destination_id;

        if ($review->photo_url) {
            Storage::disk('public')->delete($review->photo_url);
        }
        $review->delete();

        $avgRating = Review::where('destination_id', $destinationId)->avg('rating') ?? 0;
        $count     = Review::where('destination_id', $destinationId)->count();
        Destination::where('id', $destinationId)->update([
            'rating'       => round($avgRating, 1),
            'review_count' => $count,
        ]);

        return response()->json(['message' => 'Ulasan berhasil dihapus']);
    }
}