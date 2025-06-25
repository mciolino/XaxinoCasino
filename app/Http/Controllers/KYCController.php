<?php

namespace App\Http\Controllers;

use App\Models\KycDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class KycController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the KYC document upload form.
     *
     * @return \Illuminate\View\View
     */
    public function showUploadForm()
    {
        $user = Auth::user();
        $kycDocuments = $user->kycDocuments;
        
        return view('kyc.upload', compact('kycDocuments'));
    }

    /**
     * Upload a new KYC document.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function upload(Request $request)
    {
        $request->validate([
            'document_type' => 'required|in:id_card,passport,driving_license,utility_bill',
            'document' => 'required|file|mimes:jpeg,png,pdf|max:5120', // 5MB max
        ]);

        $user = Auth::user();
        
        // Store the document
        $path = $request->file('document')->store('kyc_documents/' . $user->id, 'public');
        
        // Create a new KYC document record
        $kycDocument = new KycDocument([
            'user_id' => $user->id,
            'document_type' => $request->document_type,
            'document_path' => $path,
            'status' => 'pending',
        ]);
        
        $kycDocument->save();
        
        // Update user's KYC status if it's not already verified
        if ($user->kyc_status !== 'verified') {
            $user->kyc_status = 'pending';
            $user->save();
        }
        
        return back()->with('success', 'Your document has been uploaded successfully and is pending verification.');
    }

    /**
     * Show the KYC status page.
     *
     * @return \Illuminate\View\View
     */
    public function status()
    {
        $user = Auth::user();
        $kycDocuments = $user->kycDocuments;
        
        return view('kyc.status', compact('user', 'kycDocuments'));
    }

    /**
     * Delete a KYC document.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $document = KycDocument::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        // Only allow deletion if the document is not verified
        if ($document->status === 'verified') {
            return back()->withErrors([
                'document' => 'Cannot delete a verified document.',
            ]);
        }
        
        // Delete the file
        if (Storage::disk('public')->exists($document->document_path)) {
            Storage::disk('public')->delete($document->document_path);
        }
        
        // Delete the record
        $document->delete();
        
        return back()->with('success', 'Document deleted successfully.');
    }
}
