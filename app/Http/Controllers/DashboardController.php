<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PrimaryRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/** One /dashboard URL; what each role sees is decided here, on the server. */
class DashboardController
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $role = PrimaryRole::of($user);

        return Inertia::render('dashboard', [
            'role' => $role,
            'summary' => match ($role) {
                'student' => $this->student($user),
                'donor' => $this->donor($user),
                'admin' => $this->admin(),
                'super_admin' => $this->superAdmin(),
                default => [],
            },
        ]);
    }

    /** @return array<string, mixed> */
    private function student(User $user): array
    {
        return [
            'enrolment_status' => DB::table('student_profiles')
                ->where('user_id', $user->id)
                ->value('enrolment_status') ?? 'pending',
            // A draft is work in progress, not an application: only submitted ones are counted.
            'applications' => DB::table('applications')
                ->whereIn('student_id', DB::table('student_profiles')->where('user_id', $user->id)->select('id'))
                ->where('status', '!=', 'draft')
                ->count(),
            'application_status' => DB::table('applications')
                ->whereIn('student_id', DB::table('student_profiles')->where('user_id', $user->id)->select('id'))
                ->latest('id')
                ->value('status'),
        ];
    }

    /** @return array<string, mixed> */
    private function donor(User $user): array
    {
        return [
            'approval_status' => DB::table('donor_profiles')
                ->where('user_id', $user->id)
                ->value('approval_status') ?? 'pending',
            'review_reason' => DB::table('donor_profiles')
                ->where('user_id', $user->id)
                ->value('review_reason'),
            'funds' => DB::table('funds')
                ->whereIn('donor_id', DB::table('donor_profiles')->where('user_id', $user->id)->select('id'))
                ->count(),
        ];
    }

    /** @return array<string, mixed> */
    private function admin(): array
    {
        return [
            'pending_donors' => DB::table('donor_profiles')->where('approval_status', 'pending')->count(),
            'awaiting_review' => DB::table('applications')->where('status', 'submitted')->count(),
        ];
    }

    /** @return array<string, mixed> */
    private function superAdmin(): array
    {
        return [
            'admins' => User::role('admin')->count(),
            'students' => User::role('student')->count(),
            'donors' => User::role('donor')->count(),
        ];
    }
}
