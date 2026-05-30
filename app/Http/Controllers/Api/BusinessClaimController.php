<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessClaim;
use Illuminate\Http\Request;

class BusinessClaimController extends Controller
{
    // GET /api/admin/claims
    public function index()
    {
        $appUrl = rtrim(env('APP_URL', 'http://127.0.0.1:8000'), '/');

        $claims = BusinessClaim::with(['user', 'destination'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($c) use ($appUrl) {
                $c->document_url = $c->document_path
                    ? $appUrl . '/storage/' . $c->document_path
                    : null;
                return $c;
            });

        return response()->json($claims);
    }

    // GET /api/user/claims
    public function myKlaims(Request $request)
    {
        $appUrl = rtrim(env('APP_URL', 'http://127.0.0.1:8000'), '/');

        $claims = BusinessClaim::with('destination:id,name,location')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($c) use ($appUrl) {
                $c->document_url = $c->document_path
                    ? $appUrl . '/storage/' . $c->document_path
                    : null;
                return $c;
            });

        return response()->json($claims);
    }

    // POST /api/claims
    public function store(Request $request)
    {
        $request->validate([
            'destination_id' => 'required|exists:destinations,id',
            'full_name'      => 'required|string|max:255',
            'email'          => 'required|email',
            'phone'          => 'required|string|max:20',
            'description'    => 'required|string',
            'document'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $duplicate = BusinessClaim::where('user_id', $request->user()->id)
            ->where('destination_id', $request->destination_id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'Anda sudah pernah mengajukan klaim untuk destinasi ini.',
            ], 422);
        }

        $docPath = null;
        if ($request->hasFile('document')) {
            $docPath = $request->file('document')->store('claims', 'public');
        }

        $claim = BusinessClaim::create([
            'user_id'        => $request->user()->id,
            'destination_id' => $request->destination_id,
            'full_name'      => $request->full_name,
            'email'          => $request->email,
            'phone'          => $request->phone,
            'description'    => $request->description,
            'document_path'  => $docPath,
            'status'         => 'pending',
        ]);

        return response()->json([
            'message' => 'Klaim berhasil dikirim.',
            'claim'   => $claim,
        ], 201);
    }

    // PATCH /api/admin/claims/{id}
    public function update(Request $request, $id)
    {
        $appUrl = rtrim(env('APP_URL', 'http://127.0.0.1:8000'), '/');

        $claim = BusinessClaim::with(['user', 'destination'])->findOrFail($id);

        $request->validate([
            'status'      => 'required|in:pending,approved,rejected',
            'admin_notes' => 'nullable|string',
        ]);

        $oldStatus = $claim->status;

        $claim->update([
            'status'      => $request->status,
            'admin_notes' => $request->admin_notes,
        ]);

        if ($oldStatus !== $request->status && $claim->user_id) {
            $destName    = $claim->destination->name ?? 'destinasi';
            $statusLabel = match ($request->status) {
                'approved' => 'Disetujui',
                'rejected' => 'Ditolak',
                default    => 'Diproses',
            };

            $title = "Klaim Bisnis {$statusLabel}";
            $body  = "Pengajuan klaim untuk \"{$destName}\" telah {$statusLabel}.";
            if ($request->admin_notes) {
                $body .= " Catatan admin: {$request->admin_notes}";
            }

            NotificationController::send(
                $claim->user_id,
                'claim_status',
                $title,
                $body,
                [
                    'claim_id'       => $claim->id,
                    'destination_id' => $claim->destination_id,
                    'status'         => $request->status,
                ]
            );
        }

        $updated = $claim->fresh(['user', 'destination']);
        $updated->document_url = $updated->document_path
            ? $appUrl . '/storage/' . $updated->document_path
            : null;

        return response()->json($updated);
    }
}