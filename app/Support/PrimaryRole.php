<?php

namespace App\Support;

use App\Models\User;

/**
 * The single role that drives a user's dashboard and navigation.
 * Highest authority wins if an account ever holds more than one role.
 */
final class PrimaryRole
{
    public const ORDER = ['super_admin', 'admin', 'donor', 'student'];

    public static function of(?User $user): string
    {
        if ($user === null) {
            return 'none';
        }

        foreach (self::ORDER as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return 'none';
    }
}
