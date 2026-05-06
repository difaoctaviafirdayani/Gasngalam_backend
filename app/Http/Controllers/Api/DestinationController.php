<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DestinationController extends Controller
{
    // Urutan kategori yang baku — selalu dalam urutan ini di mana pun
    const CATEGORY_ORDER = [
        'Wisata Budaya',
        'Taman Kota',
        'Wisata Edukasi',
        'Kuliner & Belanja',
        'Wisata Hiburan',
        'Wisata Alam',
    ];

    /**
     * GET /api/destinations
     * Ambil semua destinasi aktif; bisa filter kategori, search, sort
     * Query opsional: lat, lng (float) → hitung jarak real-time dari user
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
        $allowedSorts = ['rating', 'review_count', 'name'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        }

        $destinations = $query->get();

        // Kalau user kirim koordinat, hitung jarak Haversine (km)
        $userLat = $request->filled('lat') ? (float) $request->lat : null;
        $userLng = $request->filled('lng') ? (float) $request->lng : null;

        if ($userLat !== null && $userLng !== null) {
            $destinations = $destinations->map(function ($d) use ($userLat, $userLng) {
                if ($d->lat && $d->lng) {
                    $d->distance_km = round($this->haversine($userLat, $userLng, $d->lat, $d->lng), 1);
                    $d->distance    = $d->distance_km . ' km';
                }
                return $d;
            });

            // Kalau sort=nearest, urutkan berdasarkan jarak
            if ($request->sort === 'nearest') {
                $destinations = $destinations->sortBy('distance_km')->values();
            }
        }

        // Tambahkan URL foto lengkap
        $destinations = $destinations->map(function ($d) {
            $d->photo_full_url = $d->photo_url
                ? asset('storage/' . $d->photo_url)
                : null;
            return $d;
        });

        return response()->json($destinations);
    }

    /**
     * GET /api/destinations/{id}
     */
    public function show($id)
    {
        $d = Destination::findOrFail($id);
        $d->photo_full_url = $d->photo_url ? asset('storage/' . $d->photo_url) : null;
        return response()->json($d);
    }

    /**
     * GET /api/categories
     * Kembalikan kategori dalam urutan baku; kategori lain di akhir
     */
    public function categories()
    {
        $fromDb = Destination::where('is_active', true)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values()
            ->toArray();

        // Urutkan sesuai CATEGORY_ORDER, sisanya di belakang
        $ordered = collect(self::CATEGORY_ORDER)
            ->filter(fn($c) => in_array($c, $fromDb))
            ->values();

        $rest = collect($fromDb)
            ->filter(fn($c) => !in_array($c, self::CATEGORY_ORDER))
            ->values();

        return response()->json($ordered->merge($rest)->values());
    }

    /**
     * POST /api/admin/destinations (admin only)
     * Menerima multipart/form-data karena ada upload foto
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'category'     => 'required|string|max:100',
            'location'     => 'required|string|max:255',
            'ticket_price' => 'nullable|string|max:100',
            'open_hours'   => 'nullable|string|max:100',
            'contact'      => 'nullable|string|max:100',
            'social_media' => 'nullable|string|max:100',
            'address'      => 'nullable|string|max:500',
            'description'  => 'nullable|string',
            'lat'          => 'nullable|numeric',
            'lng'          => 'nullable|numeric',
            'photo'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('destinations', 'public');
        }

        $destination = Destination::create([
            'name'         => $request->name,
            'category'     => $request->category,
            'location'     => $request->location,
            'ticket_price' => $request->ticket_price,
            'open_hours'   => $request->open_hours,
            'contact'      => $request->contact,
            'social_media' => $request->social_media,
            'address'      => $request->address,
            'description'  => $request->description,
            'lat'          => $request->lat,
            'lng'          => $request->lng,
            'photo_url'    => $photoPath,
            'is_active'    => true,
            // emoji & color dihapus dari form, tapi model masih simpan default
            'emoji'        => null,
            'color'        => null,
            'gradient'     => null,
        ]);

        $destination->photo_full_url = $destination->photo_url
            ? asset('storage/' . $destination->photo_url)
            : null;

        return response()->json($destination, 201);
    }

    /**
     * PUT /api/admin/destinations/{id} (admin only)
     * Menerima multipart/form-data
     */
    public function update(Request $request, $id)
    {
        $destination = Destination::findOrFail($id);

        $request->validate([
            'name'         => 'sometimes|required|string|max:255',
            'category'     => 'sometimes|required|string|max:100',
            'location'     => 'sometimes|required|string|max:255',
            'ticket_price' => 'nullable|string|max:100',
            'open_hours'   => 'nullable|string|max:100',
            'contact'      => 'nullable|string|max:100',
            'social_media' => 'nullable|string|max:100',
            'address'      => 'nullable|string|max:500',
            'description'  => 'nullable|string',
            'lat'          => 'nullable|numeric',
            'lng'          => 'nullable|numeric',
            'is_active'    => 'nullable|boolean',
            'photo'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $data = $request->only([
            'name', 'category', 'location',
            'ticket_price', 'open_hours', 'contact', 'social_media',
            'address', 'description', 'lat', 'lng', 'is_active',
        ]);

        // Upload foto baru jika ada; hapus foto lama
        if ($request->hasFile('photo')) {
            if ($destination->photo_url) {
                Storage::disk('public')->delete($destination->photo_url);
            }
            $data['photo_url'] = $request->file('photo')->store('destinations', 'public');
        }

        $destination->update($data);

        $destination->photo_full_url = $destination->photo_url
            ? asset('storage/' . $destination->photo_url)
            : null;

        return response()->json($destination);
    }

    /**
     * DELETE /api/admin/destinations/{id} (admin only)
     */
    public function destroy($id)
    {
        $destination = Destination::findOrFail($id);

        // Hapus foto dari storage kalau ada
        if ($destination->photo_url) {
            Storage::disk('public')->delete($destination->photo_url);
        }

        $destination->delete();
        return response()->json(['message' => 'Destinasi berhasil dihapus']);
    }

    // ─── Helper Haversine ────────────────────────────────────────────────────
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R    = 6371; // radius bumi km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2
              + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}