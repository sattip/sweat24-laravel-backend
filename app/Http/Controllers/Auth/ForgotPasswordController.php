<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    /**
     * Send a reset link to the given user.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'Δεν βρέθηκε λογαριασμός με αυτό το email.'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Δεν βρέθηκε λογαριασμός με αυτό το email.'
            ], 404);
        }

        // Check if user account is active
        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Ο λογαριασμός σας δεν είναι ενεργός. Επικοινωνήστε με το γυμναστήριο.'
            ], 403);
        }

        // Generate password reset token
        $token = Str::random(64);

        // Store token in password_resets table
        DB::table('password_resets')->updateOrInsert(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Hash::make($token),
                'created_at' => Carbon::now()
            ]
        );

        // Send reset password notification
        $user->notify(new ResetPasswordNotification($user, $token));

        return response()->json([
            'success' => true,
            'message' => 'Σας στείλαμε ένα email με οδηγίες για την επαναφορά του κωδικού σας.'
        ]);
    }

    /**
     * Reset the given user's password.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.exists' => 'Δεν βρέθηκε λογαριασμός με αυτό το email.',
            'password.min' => 'Ο κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες.',
            'password.confirmed' => 'Οι κωδικοί δεν ταιριάζουν.'
        ]);

        // Check if token exists and is valid
        $passwordReset = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$passwordReset || !Hash::check($request->token, $passwordReset->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Μη έγκυρο ή ληγμένο token επαναφοράς.'
            ], 422);
        }

        // Check if token has expired (24 hours)
        if (Carbon::parse($passwordReset->created_at)->addHours(24)->isPast()) {
            DB::table('password_resets')->where('email', $request->email)->delete();
            
            return response()->json([
                'success' => false,
                'message' => 'Το token επαναφοράς έχει λήξει. Ζητήστε νέο.'
            ], 422);
        }

        // Find user and update password
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Δεν βρέθηκε χρήστης.'
            ], 404);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Delete the token
        DB::table('password_resets')->where('email', $request->email)->delete();

        // Revoke all existing tokens for security
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ο κωδικός σας άλλαξε επιτυχώς. Μπορείτε τώρα να συνδεθείτε με τον νέο κωδικό.'
        ]);
    }

    /**
     * Validate password reset token
     */
    public function validateToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
        ]);

        $passwordReset = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$passwordReset || !Hash::check($request->token, $passwordReset->token)) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Μη έγκυρο token επαναφοράς.'
            ], 422);
        }

        // Check if token has expired (24 hours)
        if (Carbon::parse($passwordReset->created_at)->addHours(24)->isPast()) {
            DB::table('password_resets')->where('email', $request->email)->delete();
            
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Το token επαναφοράς έχει λήξει.'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'valid' => true,
            'message' => 'Έγκυρο token επαναφοράς.'
        ]);
    }
}