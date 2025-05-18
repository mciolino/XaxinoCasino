<?php

namespace App\Http\Controllers;

use App\Models\KycDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Show the admin KYC verification page.
     *
     * @return \Illuminate\View\View
     */
    public function showKycVerification()
    {
        $pendingDocuments = KycDocument::where('status', 'pending')
            ->with('user')
            ->latest()
            ->paginate(10);
        
        return view('admin.kyc', compact('pendingDocuments'));
    }

    /**
     * Approve a KYC document.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approveKyc($id)
    {
        $document = KycDocument::findOrFail($id);
        $user = $document->user;
        
        $document->status = 'verified';
        $document->notes = 'Approved by admin';
        $document->save();
        
        $user->kyc_status = 'verified';
        $user->save();
        
        return back()->with('success', 'KYC document approved successfully.');
    }

    /**
     * Reject a KYC document.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rejectKyc(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);
        
        $document = KycDocument::findOrFail($id);
        
        $document->status = 'rejected';
        $document->notes = $request->rejection_reason;
        $document->save();
        
        // Check if the user has any other verified documents
        $hasVerified = KycDocument::where('user_id', $document->user_id)
            ->where('status', 'verified')
            ->exists();
        
        if (!$hasVerified) {
            $user = $document->user;
            $user->kyc_status = 'rejected';
            $user->save();
        }
        
        return back()->with('success', 'KYC document rejected.');
    }

    /**
     * Show the user management page.
     *
     * @return \Illuminate\View\View
     */
    public function showUserManagement()
    {
        $users = User::where('role', '!=', 'admin')
            ->latest()
            ->paginate(15);
        
        return view('admin.users', compact('users'));
    }

    /**
     * Ban a user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function banUser($id)
    {
        $user = User::findOrFail($id);
        
        if ($user->isAdmin()) {
            return back()->withErrors([
                'user' => 'Cannot ban an admin user.',
            ]);
        }
        
        $user->status = 'banned';
        $user->save();
        
        return back()->with('success', 'User banned successfully.');
    }

    /**
     * Unban a user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unbanUser($id)
    {
        $user = User::findOrFail($id);
        
        $user->status = 'active';
        $user->save();
        
        return back()->with('success', 'User unbanned successfully.');
    }

    /**
     * Show the admin dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'verified_users' => User::where('kyc_status', 'verified')->count(),
            'pending_kyc' => KycDocument::where('status', 'pending')->count(),
        ];
        
        return view('admin.dashboard', compact('stats'));
    }
}
