<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Public registration (SRS FR-01/02): Student and Donor accounts only.
 * Admin and Super Admin are never created here, and the role comes from a validated
 * allow-list, never from a client-supplied role field.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public const PUBLIC_ROLES = ['student', 'donor'];

    public const CONSENT_NOTICE_VERSION = 'v1';

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'account_type' => ['required', 'string', Rule::in(self::PUBLIC_ROLES)],
            'consent' => ['accepted'],
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => Str::lower($input['email']),
                'password' => $input['password'],
            ]);

            $user->assignRole($input['account_type']);

            $now = now();

            if ($input['account_type'] === 'student') {
                // Profile details (institution, matric number...) are completed later, in "My profile".
                DB::table('student_profiles')->insert([
                    'user_id' => $user->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                // Donors stay inactive until an Admin approves them (approval_status defaults to 'pending').
                DB::table('donor_profiles')->insert([
                    'user_id' => $user->id,
                    'display_name' => $user->name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('consent_events')->insert([
                'user_id' => $user->id,
                'notice_version' => self::CONSENT_NOTICE_VERSION,
                'action' => 'granted',
                'scope' => 'data_use',
                'occurred_at' => $now,
                'created_at' => $now,
            ]);

            return $user;
        });
    }
}
