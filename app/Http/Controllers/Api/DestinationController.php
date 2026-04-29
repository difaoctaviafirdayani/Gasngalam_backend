<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    /**
     * GET /api/destinations
     * Ambil semua destinasi, bisa difilter by kategori & search
     */
    public function index(Request $request)
    {
        $query = Destination::query()->where('is_active', true);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sort = $request->sort ?? 'rating';
        $dir  = $request->dir  ?? 'desc';
        $allowedSorts = ['rating', 'review_count', 'name', 'distance'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        }

        return response()->json($query->get());
    }

    /**
     * GET /api/destinations/{id}
     */
    public function show($id)
    {
        return response()->json(Destination::findOrFail($id));
    }

    /**
     * POST /api/admin/destinations (admin only)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'location' => 'required|string|max:255',
        ]);

        $destination = Destination::create($request->only([
            'name', 'category', 'location', 'distance',
            'ticket_price', 'open_hours', 'contact', 'social_media',
            'address', 'description', 'emoji', 'color', 'gradient', 'is_active',
        ]));

        return response()->json($destination, 201);
    }

    /**
     * PUT /api/admin/destinations/{id} (admin only)
     */
    public function update(Request $request, $id)
    {
        $destination = Destination::findOrFail($id);
        $destination->update($request->only([
            'name', 'category', 'location', 'distance',
            'ticket_price', 'open_hours', 'contact', 'social_media',
            'address', 'description', 'emoji', 'color', 'gradient', 'is_active',
        ]));

        return response()->json($destination);
    }

    /**
     * DELETE /api/admin/destinations/{id} (admin only)
     */
    public function destroy($id)
    {
        Destination::findOrFail($id)->delete();
        return response()->json(['message' => 'Destinasi berhasil dihapus']);
    }

    /**
     * GET /api/categories
     */
    public function categories()
    {
        $categories = Destination::where('is_active', true)
            ->distinct()->pluck('category')->filter()->values();
        return response()->json($categories);
    }
}
