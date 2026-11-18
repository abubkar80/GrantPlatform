<?php

namespace App\Http\Controllers\Admin;

use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/** Admin reviews donor registrations: only approved donors may create funds or pay (SRS donor approval). */
class DonorApprovalController
{
    private const FILTERS = ['pending', 'approved', 'declined', 'all'];

    public function index(Request $request): Response
    {
        $filter = in_array($request->query('status'), self::FILTERS, true) ? $request->query('status') : 'pending';

        $donors = DB::table('donor_profiles as d')
            ->join('users as u', 'u.id', '=', 'd.user_id')
            ->when($filter !== 'all', fn ($query) => $query->where('d.approval_status', $filter))
            ->orderByDesc('d.created_at')
            ->limit(200)
            ->get([
                'd.id', 'd.display_name', 'd.approval_status', 'd.review_reason',
                'd.reviewed_at', 'd.created_at', 'u.name', 'u.email',
            ]);

        return Inertia::render('admin/donors', [
            'donors' => $donors,
            'filter' => $filter,
            'counts' => [
                'pending' => DB::table('donor_profiles')->where('approval_status', 'pending')->count(),
                'approved' => DB::table('donor_profiles')->where('approval_status', 'approved')->count(),
                'declined' => DB::table('donor_profiles')->where('approval_status', 'declined')->count(),
            ],
        ]);
    }

    public function approve(Request $request, int $profile): RedirectResponse
    {
        return $this->decide($request, $profile, 'approved', null);
    }

    public function decline(Request $request, int $profile): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        return $this->decide($request, $profile, 'declined', $data['reason']);
    }

    private function decide(Request $request, int $profile, string $decision, ?string $reason): RedirectResponse
    {
        $admin = $request->user();

        $donor = DB::transaction(function () use ($profile, $decision, $reason, $admin) {
            $now = now();

            // One conditional UPDATE: only a still-pending registration can be decided, so two Admins
            // clicking at once cannot both win and a decision can never be silently overwritten.
            $changed = DB::table('donor_profiles')
                ->where('id', $profile)
                ->where('approval_status', 'pending')
                ->update([
                    'approval_status' => $decision,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => $now,
                    'review_reason' => $reason,
                    'updated_at' => $now,
                ]);

            if ($changed === 0) {
                return null;
            }

            $donor = DB::table('donor_profiles')->where('id', $profile)->first();

            Audit::record(
                action: "donor.{$decision}",
                subjectType: 'donor_profile',
                subjectId: $profile,
                actorId: $admin->id,
                reason: $reason,
                changes: ['approval_status' => ['from' => 'pending', 'to' => $decision]],
            );

            DB::table('notifications')->insert([
                'user_id' => $donor->user_id,
                'event_key' => "donor.registration.{$decision}:{$profile}",
                'kind' => "donor.registration.{$decision}",
                'payload' => json_encode(['decision' => $decision, 'reason' => $reason], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $donor;
        });

        if ($donor === null) {
            return back()->with('error', 'That registration was already reviewed, or no longer exists.');
        }

        return back()->with('status', "{$donor->display_name} was {$decision}.");
    }
}
