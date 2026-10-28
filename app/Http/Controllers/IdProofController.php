<?php

namespace App\Http\Controllers;

use App\Models\IdProof;
use Illuminate\Http\Request;
use App\Support\IdProofFiles;

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
            'document_front' => 'required|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
            'document_back' => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
        ]);

        $profile = auth()->user()->profile;

        // private disk, opened only through signed links (App\Support\IdProofFiles)
        $frontPath = IdProofFiles::store($request->file('document_front'), $profile->id);
        $backPath = $request->hasFile('document_back')
            ? IdProofFiles::store($request->file('document_back'), $profile->id)
            : null;

        IdProof::create([
            'profile_id' => $profile->id,
            'document_type' => $request->document_type,
            'document_number' => IdProofFiles::maskNumber($request->document_number),   // last 4 only
            'document_url' => $frontPath,
            'document_back_url' => $backPath,
            'verification_status' => 'pending',
        ]);

        return back()->with('success', 'ID Proof uploaded successfully! It will be reviewed by our team.');
    }

    public function destroy(IdProof $idProof)
    {
        $profile = auth()->user()->profile;
        if ($idProof->profile_id !== $profile->id) abort(403);

        IdProofFiles::delete($idProof->document_url);
        IdProofFiles::delete($idProof->document_back_url);

        $idProof->delete();
        return back()->with('success', 'ID Proof removed.');
    }
}
