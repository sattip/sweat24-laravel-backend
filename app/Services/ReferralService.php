<?php

namespace App\Services;

use App\Models\User;

class ReferralService
{
    /**
     * Find a referrer by name or email
     * 
     * @param string $searchTerm
     * @return User|null
     */
    public static function findReferrer(string $searchTerm): ?User
    {
        // First try exact email match
        $referrer = User::where('email', $searchTerm)
            ->where('status', 'active')
            ->where('role', '!=', 'admin')
            ->first();
        
        if ($referrer) {
            return $referrer;
        }
        
        // Then try exact name match (case-insensitive)
        $referrer = User::whereRaw('LOWER(name) = ?', [strtolower($searchTerm)])
            ->where('status', 'active')
            ->where('role', '!=', 'admin')
            ->first();
        
        if ($referrer) {
            return $referrer;
        }
        
        // If no exact match, try to find by referral code (if it looks like a code)
        if (preg_match('/^REF\d{6}$/', strtoupper($searchTerm))) {
            $userId = intval(substr($searchTerm, 3));
            return User::where('id', $userId)
                ->where('status', 'active')
                ->where('role', '!=', 'admin')
                ->first();
        }
        
        // As a last resort, if there are multiple words, try to match full name
        $words = explode(' ', trim($searchTerm));
        if (count($words) >= 2) {
            // Try to match the full name exactly
            $referrer = User::whereRaw('LOWER(name) = ?', [strtolower($searchTerm)])
                ->where('status', 'active')
                ->where('role', '!=', 'admin')
                ->first();
            
            if ($referrer) {
                return $referrer;
            }
        }
        
        return null;
    }
    
    /**
     * Find multiple potential referrers for disambiguation
     * 
     * @param string $searchTerm
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public static function findPotentialReferrers(string $searchTerm, int $limit = 5)
    {
        return User::where(function ($query) use ($searchTerm) {
                $query->where('name', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('email', 'LIKE', '%' . $searchTerm . '%');
            })
            ->where('status', 'active')
            ->where('role', '!=', 'admin')
            ->limit($limit)
            ->get(['id', 'name', 'email']);
    }
    
    /**
     * Generate a referral code for a user
     * 
     * @param int $userId
     * @return string
     */
    public static function generateReferralCode(int $userId): string
    {
        return 'REF' . str_pad($userId, 6, '0', STR_PAD_LEFT);
    }
}