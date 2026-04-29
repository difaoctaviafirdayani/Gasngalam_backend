<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Destination;

class DestinationController extends Controller
{
    public function index()
    {
        $data = Destination::all();
        return view('wisata', compact('data'));
    }

    public function create()
    {
        return view('tambah_wisata');
    }

    public function store(Request $request)
    {
        $data = $request->only(['name', 'category', 'location', 'description']);

        if ($request->hasFile('image')) {
            $fileName = time().'.'.$request->image->extension();
            $request->image->move(public_path('images'), $fileName);
            $data['image'] = $fileName;
        }

        Destination::create($data);

        return redirect('/wisata');
    }
}