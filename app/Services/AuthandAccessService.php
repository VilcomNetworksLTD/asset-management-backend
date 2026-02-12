<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail; // Added Mail facade
use Carbon\Carbon;
use App\Mail\OtpMail;

class AuthandAccessService
{
    /**
     * Register a new user and send verification OTP
     */
    public function registerUser(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'staff',
            'is_verified' => false,
        ]);

        // Generate verification OTP
        $this->generateAndSendOtp($user, 'verification');

        return $user;
    }

    /**
     * Verify user's email OTP
     */
    public function verifyOtp(string $email, string $otpCode)
    {
        $user = User::where('email', $email)
            ->where('otp_code', $otpCode)
            ->where('otp_expires_at', '>', Carbon::now())
            ->first();

        if (!$user) {
            return null;
        }

        $user->update([
            'is_verified' => true,
            'otp_code' => null,
            'otp_expires_at' => null,
            'email_verified_at' => Carbon::now()
        ]);

        return $user;
    }

    /**
     * Resend verification OTP
     */
    public function processResendOtp(string $email)
    {
        $user = User::where('email', $email)->first();
        if (!$user) return false;

        $this->generateAndSendOtp($user, 'verification');
        return true;
    }

    /**
     * Forgot password: generate password reset OTP
     */
    public function forgotPassword(string $email)
    {
        $user = User::where('email', $email)->first();
        if (!$user) return false;

        $this->generateAndSendOtp($user, 'password_reset');
        return true;
    }

    /**
     * Reset user password using reset OTP
     */
    public function resetUserPassword(string $email, string $otpCode, string $newPassword)
    {
        $user = User::where('email', $email)
            ->where('reset_otp', $otpCode)
            ->where('reset_otp_expires_at', '>', Carbon::now())
            ->first();

    
        $user->update([
            'password' => Hash::make($newPassword),
            'reset_otp' => null,
            'reset_otp_expires_at' => null,
        ]);

        return true;
    }

    /**
     * Generate OTP and store it, then send via Email
     */
    public function generateAndSendOtp(User $user, string $type = 'verification')
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $subject = ($type === 'verification') ? 'Verify Your Account' : 'Password Reset Code';

        if ($type === 'verification') {
            $user->update([
                'otp_code' => $otp,
                'otp_expires_at' => Carbon::now()->addMinutes(15),
            ]);
        } elseif ($type === 'password_reset') {
            $user->update([
                'reset_otp' => $otp,
                'reset_otp_expires_at' => Carbon::now()->addMinutes(15),
            ]);
        }

        // 1. Log OTP for development backup
        Log::info("SECURITY: {$type} OTP for {$user->email} is {$otp}");

       // 2. ACTUALLY SEND THE EMAIL
       // 2. ACTUALLY SEND THE EMAIL
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)
                ->send(new \App\Mail\OtpMail($otp, $type));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Mail failure for {$user->email}: " . $e->getMessage());
        } 
    } 
} 