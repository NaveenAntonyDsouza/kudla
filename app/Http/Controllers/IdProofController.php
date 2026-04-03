<?php

namespace App\Http\Controllers;

use App\Models\IdProof;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IdProofController extends Controller
{
    public function index()
    {
        $profile = auth()->user()->profile;
        $idProofs = $profile->idProofs()->latest()->get();

        return view('id-proof.index', compact('profile', 'idProofs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string|in:Passport,Voter ID,Aadhaar Card,Driving License,PAN Card',
            'document_number' => 'required|string|max:50',
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
        ]);

        $profile = auth()->user()->profile;

        $path = $request->file('document')->store('id-proofs/' . $profile->id, 'public');

        IdProof::create([
            'profile_id' => $profile->id,
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'document_url' => $path,
            'verification_status' => 'pending',
        ]);

        return back()->with('success', 'ID Proof uploaded successfully! It will be reviewed by our team.');
    }

    public function destroy(IdProof $idProof)
    {
        $profile = auth()->user()->profile;
        if ($idProof->profile_id !== $profile->id) abort(403);

        if ($idProof->document_url && Storage::disk('public')->exists($idProof->document_url)) {
            Storage::disk('public')->delete($idProof->document_url);
        }

        $idProof->delete();
        return back()->with('success', 'ID Proof removed.');
    }
}
