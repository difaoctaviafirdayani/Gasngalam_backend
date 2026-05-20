<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\DestinationPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DestinationController extends Controller
{
    const CATEGORY_ORDER = [
        'Wisata Budaya', 'Taman Kota', 'Wisata Edukasi',
        'Kuliner & Belanja', 'Wisata Hiburan', 'Wisata Alam',
    ];

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

        // Filter harga — ambil semua dulu, filter di PHP agar tidak crash REGEXP
        if ($request->filled('free') && $request->free == '1') {
            $query->where(function($q) {
                $q->where('ticket_price', 'like', '%gratis%')
                  ->orWhere('ticket_price', 'like', '%Gratis%')
                  ->orWhere('ticket_price', '0')
                  ->orWhereNull('ticket_price');
            });
        }

        if ($request->filled('open_now')) {
            $query->whereNotNull('open_hours')->where('open_hours', '!=', '-');
        }

        $sort = $request->sort ?? 'rating';
        $dir  = $request->dir  ?? 'desc';
        $allowedSorts = ['rating', 'review_count', 'name'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        }

        $destinations = $query->with('photos')->get();

        // Filter harga min/max di PHP level (hindari REGEXP_REPLACE error)
        if ($request->filled('price_min') || $request->filled('price_max')) {
            $priceMin = $request->filled('price_min') ? (int)$request->price_min : null;
            $priceMax = $request->filled('price_max') ? (int)$request->price_max : null;
            $destinations = $destinations->filter(function ($d) use ($priceMin, $priceMax) {
                preg_match('/\d+/', str_replace(['.', ','], '', $d->ticket_price ?? ''), $m);
                $price = isset($m[0]) ? (int)$m[0] : 0;
                if ($priceMin !== null && $price < $priceMin) return false;
                if ($priceMax !== null && $price > $priceMax) return false;
                return true;
            })->values();
        }

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
            if ($request->sort === 'nearest') {
                $destinations = $destinations->sortBy('distance_km')->values();
            }
        }

        $destinations = $destinations->map(function ($d) {
            $d->photo_full_url = $d->photo_url ? asset('storage/' . $d->photo_url) : null;
            $d->gallery = $d->photos->map(function ($p) {
                return [
                    'id'        => $p->id,
                    'url'       => asset('storage/' . $p->photo_url),
                    'caption'   => $p->caption,
                    'sort_order'=> $p->sort_order,
                ];
            });
            return $d;
        });

        return response()->json($destinations->values());
    }

    public function show($id)
    {
        $d = Destination::with('photos')->findOrFail($id);
        $d->photo_full_url = $d->photo_url ? asset('storage/' . $d->photo_url) : null;
        $d->gallery = $d->photos->map(function ($p) {
            return [
                'id'        => $p->id,
                'url'       => asset('storage/' . $p->photo_url),
                'caption'   => $p->caption,
                'sort_order'=> $p->sort_order,
            ];
        });
        return response()->json($d);
    }

    public function categories()
    {
        $fromDb = Destination::where('is_active', true)
            ->distinct()->pluck('category')->filter()->values()->toArray();
        $ordered = collect(self::CATEGORY_ORDER)->filter(fn($c) => in_array($c, $fromDb))->values();
        $rest = collect($fromDb)->filter(fn($c) => !in_array($c, self::CATEGORY_ORDER))->values();
        return response()->json($ordered->merge($rest)->values());
    }

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
            'gallery.*'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
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
            'emoji'        => null,
            'color'        => null,
            'gradient'     => null,
        ]);

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $i => $file) {
                $path = $file->store('destinations/gallery', 'public');
                DestinationPhoto::create([
                    'destination_id' => $destination->id,
                    'photo_url'      => $path,
                    'sort_order'     => $i,
                ]);
            }
        }

        $destination->photo_full_url = $destination->photo_url
            ? asset('storage/' . $destination->photo_url) : null;
        return response()->json($destination, 201);
    }

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
            'gallery.*'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $data = $request->only([
            'name','category','location','ticket_price','open_hours',
            'contact','social_media','address','description','lat','lng','is_active',
        ]);

        if ($request->hasFile('photo')) {
            if ($destination->photo_url) Storage::disk('public')->delete($destination->photo_url);
            $data['photo_url'] = $request->file('photo')->store('destinations', 'public');
        }

        $destination->update($data);

        if ($request->hasFile('gallery')) {
            $maxOrder = DestinationPhoto::where('destination_id', $destination->id)->max('sort_order') ?? -1;
            foreach ($request->file('gallery') as $i => $file) {
                $path = $file->store('destinations/gallery', 'public');
                DestinationPhoto::create([
                    'destination_id' => $destination->id,
                    'photo_url'      => $path,
                    'sort_order'     => $maxOrder + $i + 1,
                ]);
            }
        }

        $destination->photo_full_url = $destination->photo_url
            ? asset('storage/' . $destination->photo_url) : null;
        return response()->json($destination);
    }

    public function deletePhoto($id, $photoId)
    {
        $photo = DestinationPhoto::where('destination_id', $id)->where('id', $photoId)->firstOrFail();
        Storage::disk('public')->delete($photo->photo_url);
        $photo->delete();
        return response()->json(['message' => 'Foto dihapus']);
    }

    public function destroy($id)
    {
        $destination = Destination::findOrFail($id);
        if ($destination->photo_url) Storage::disk('public')->delete($destination->photo_url);
        foreach ($destination->photos as $photo) {
            Storage::disk('public')->delete($photo->photo_url);
        }
        $destination->photos()->delete();
        $destination->delete();
        return response()->json(['message' => 'Destinasi berhasil dihapus']);
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R    = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2
              + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}