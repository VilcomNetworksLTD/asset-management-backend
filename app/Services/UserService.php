<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Fetch all users from the database.
     */
    public function getAllUsers()
    {
        return User::all();
    }

    /**
     * Update basic profile info
     */
    public function updateProfile(User $user, array $data)
    {
        $user->update($data);
        return $user;
    }

    /**
     * Securely update password
     */
    public function updatePassword(User $user, array $data)
    {
        // Verify current password before allowing change
        if (!Hash::check($data['current_password'], $user->password)) {
            return false;
        }

        return $user->update([
            'password' => Hash::make($data['new_password'])
        ]);
    }

    /**
     * Get users available for asset transfer (excludes current user)
     */
    public function getTransferableUsers($currentUserId)
    {
        return User::where('id', '!=', $currentUserId)
            ->select('id', 'name', 'email')
            ->get();
    }
}