<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BusinessClaimController extends Controller
{
    /**
     * POST /api/claims
     */
    public function store(Request $request)
    {
        $request->validate([
            'destination_id' => 'required|exists:destinations,id',
            'full_name'      => 'required|string|max:255',
            'email'          => 'required|email|max:255',
            'phone'          => 'required|string|max:20',
            'description'    => 'required|string|min:5',
            'document'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        // Cek sudah pernah klaim destinasi yang sama
        $exists = BusinessClaim::where('user_id', $request->user()->id)
            ->where('destination_id', $request->destination_id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Anda sudah memiliki klaim untuk destinasi ini.',
            ], 422);
        }

        // Simpan file jika ada
        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('claims', 'public');
        }

        $claim = BusinessClaim::create([
            'user_id'        => $request->user()->id,
            'destination_id' => $request->destination_id,
            'full_name'      => $request->full_name,
            'email'          => $request->email,
            'phone'          => $request->phone,
            'description'    => $request->description,
            'document_path'  => $documentPath,
            'status'         => 'pending',
        ]);

        return response()->json($claim->load(['destination:id,name', 'user:id,name']), 201);
    }

    /**
     * GET /api/admin/claims
     */
    public function index(Request $request)
    {
        $claims = BusinessClaim::with(['destination:id,name', 'user:id,name,email'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->get()
            ->map(function ($claim) {
                // Tambahkan URL file jika ada
                if ($claim->document_path) {
                    $claim->document_url = asset('storage/' . $claim->document_path);
                }
                return $claim;
            });

        return response()->json($claims);
    }

    /**
     * PATCH /api/admin/claims/{id}
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'status'      => 'required|in:pending,approved,rejected',
            'admin_notes' => 'nullable|string',
        ]);

        $claim = BusinessClaim::findOrFail($id);
        $claim->update([
            'status'      => $request->status,
            'admin_notes' => $request->admin_notes,
        ]);

        return response()->json($claim->load(['destination:id,name', 'user:id,name']));
    }
}
