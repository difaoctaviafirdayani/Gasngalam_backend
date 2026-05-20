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
            // Ekstrak angka pertama dari string harga
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