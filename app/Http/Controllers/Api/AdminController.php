public function reviews(Request $request)
{
    $reviews = Review::with(['user:id,name', 'destination:id,name'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($r) {
            $appUrl = rtrim(env('APP_URL', 'http://127.0.0.1:8000'), '/');
            $r->photo_full_url = $r->photo_url
                ? $appUrl . '/storage/' . $r->photo_url
                : null;
            return $r;
        });

    return response()->json($reviews);
}