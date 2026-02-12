<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthandAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthandAccessController extends Controller
{
    protected $authService;

    public function __construct(AuthandAccessService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register user
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 
                        'email', 
                        'unique:users', 
                        'regex:/^[a-zA-Z0-9._%+-]+@vilcom\.co\.ke$/i'],
            'password' => 'required|min:8|confirmed',
        ]);

        $this->authService->registerUser($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful. Please verify your email using the OTP.'
        ], 201);
    }

    /**
     * Verify email OTP
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|string|size:6',
        ]);

        $user = $this->authService->verifyOtp($request->email, $request->otp_code);

        if (!$user) {
            return response()->json(['message' => 'Invalid or expired OTP'], 401);
        }
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Email verified successfully',
            'token' => $user->createToken('auth_token')->plainTextToken,
        ]);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

       if (!$user->is_verified) {
            $this->authService->processResendOtp($user->email);
            return response()->json([
                'message' => 'Account not verified. A new OTP has been sent to your email.',
                'needs_verification' => true // Helps Vue redirect to the OTP page
            ], 403);
        }

        return response()->json([
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user' => $user
        ]);
    }

    /**
     * Resend verification OTP
     */
    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        $success = $this->authService->processResendOtp($request->email);

        return $success 
            ? response()->json(['message' => 'Verification OTP resent successfully.'])
            : response()->json(['message' => 'User not found.'], 404);
    }

    /**
     * Forgot password: generate reset OTP
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $success = $this->authService->forgotPassword($request->email);

        return $success
            ? response()->json(['message' => 'Password reset OTP sent to email.'])
            : response()->json(['message' => 'Email not found.'], 404);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|string|size:6',
            'password' => 'required|min:8|confirmed',
        ]);

        $success = $this->authService->resetUserPassword(
            $request->email,
            $request->otp_code,
            $request->password
        );

        return $success
            ? response()->json(['message' => 'Password reset successfully.'])
            : response()->json(['message' => 'Invalid or expired OTP.'], 401);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
