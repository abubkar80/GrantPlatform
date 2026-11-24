<?php

namespace App\Http\Controllers\Student;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** A student's own profile (SRS: matric number, institution, programme, level, origin LGA). */
class ProfileController
{
    public function show(Request $request): Response
    {
        $row = DB::table('student_profiles')->where('user_id', $request->user()->id)->first();
        $profile = $row ? (array) $row : [];

        return Inertia::render('student/profile', [
            'profile' => [
                'institution_id' => $profile['institution_id'] ?? null,
                'programme_id' => $profile['programme_id'] ?? null,
                'matric_number' => $profile['matric_number'] ?? null,
                'level' => $profile['level'] ?? null,
                'origin_lga_id' => $profile['origin_lga_id'] ?? null,
                'phone' => $profile['phone'] ?? null,
                'enrolment_status' => $profile['enrolment_status'] ?? 'pending',
            ],
            'institutions' => DB::table('institutions')->where('active', true)->orderBy('name')->get(['id', 'name']),
            'programmes' => DB::table('programmes')->orderBy('name')->get(['id', 'institution_id', 'name']),
            'lgas' => DB::table('local_governments')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $profileId = DB::table('student_profiles')->where('user_id', $request->user()->id)->value('id');
        $institutionId = $request->integer('institution_id');

        $data = $request->validate([
            'institution_id' => ['required', 'integer', Rule::exists('institutions', 'id')->where('active', true)],
            // The programme must belong to the chosen institution.
            'programme_id' => ['required', 'integer', Rule::exists('programmes', 'id')->where('institution_id', $institutionId)],
            'matric_number' => [
                'required', 'string', 'max:80',
                Rule::unique('student_profiles', 'matric_number')
                    ->where('institution_id', $institutionId)
                    ->ignore($profileId),
            ],
            'level' => ['required', 'integer', 'between:100,800'],
            'origin_lga_id' => ['required', 'integer', 'exists:local_governments,id'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+\s\-()]{7,32}$/'],
        ]);

        DB::table('student_profiles')->where('user_id', $request->user()->id)->update([
            'institution_id' => $data['institution_id'],
            'programme_id' => $data['programme_id'],
            'matric_number' => trim($data['matric_number']),
            'level' => $data['level'],
            'origin_lga_id' => $data['origin_lga_id'],
            'phone' => $data['phone'],
            'row_version' => DB::raw('row_version + 1'),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Profile saved.');
    }
}
