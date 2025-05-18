<?php

namespace App\Http\Controllers;

use App\Models\KYCDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class KYCController extends Controller
{
    /**
     * Show KYC upload form
     */
    public function show()
    {
        $user = Auth::user();
        
        // Get the user's current KYC documents
        $documents = KYCDocument::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('kyc.upload', [
            'user' => $user,
            'documents' => $documents
        ]);
    }
    
    /**
     * Process document upload
     */
    public function upload(Request $request)
    {
        $request->validate([
            'document_type' => 'required|in:passport,drivers_license,id_card',
            'document_image' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // Max 5MB
            'document_number' => 'required|string|max:50'
        ]);
        
        $user = Auth::user();
        
        // If user is already verified, don't allow more uploads
        if ($user->kyc_status === 'verified') {
            return redirect()->route('kyc')->with('info', 'Your account is already verified.');
        }
        
        // Store the uploaded file
        $documentPath = $request->file('document_image')->store('kyc_documents', 'private');
        
        // Create new KYC document record
        $document = new KYCDocument();
        $document->user_id = $user->id;
        $document->document_type = $request->document_type;
        $document->document_path = $documentPath;
        $document->document_number = $request->document_number;
        $document->status = 'pending';
        $document->save();
        
        // Update user's KYC status to pending if it wasn't already
        if ($user->kyc_status !== 'pending') {
            $user->kyc_status = 'pending';
            $user->save();
        }
        
        return redirect()->route('kyc')->with('success', 'Document uploaded successfully. It will be reviewed soon.');
    }
}
