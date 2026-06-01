<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BusinessClaimController extends Controller
{
    private function docUrl(?string $path): ?string
    {
        if (!$path) return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return rtrim(env('APP_URL', 'http://127.0.0.1:8000'), '/') . '/storage/' . $path;
    }

    public function index()
    {
        $claims = BusinessClaim::with(['user', 'destination'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($c) {
                $c->document_url = $this->docUrl($c->document_path);
                return $c;
            });

        return response()->json($claims);
    }

    public function myKlaims(Request $request)
    {
        $claims = BusinessClaim::with('destination:id,name,location')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($c) {
                $c->document_url = $this->docUrl($c->document_path);
                return $c;
            });

        return response()->json($claims);
    }

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

    public function update(Request $request, $id)
    {
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
        $updated->document_url = $this->docUrl($updated->document_path);

        return response()->json($updated);
    }
}