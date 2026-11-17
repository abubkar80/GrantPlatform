<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Reference lists used by profiles, applications and fund criteria.
 * Institutions, programmes and grading scales are demo values; safe to run repeatedly.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $institutions = [
            ['code' => 'GSU', 'name' => 'Gombe State University', 'scale' => 5.00, 'programmes' => [
                ['Computer Science', 'Computing'],
                ['Nursing Science', 'Health Sciences'],
                ['Accounting', 'Management Sciences'],
            ]],
            ['code' => 'FUK', 'name' => 'Federal University of Kashere', 'scale' => 5.00, 'programmes' => [
                ['Microbiology', 'Life Sciences'],
                ['Economics', 'Social Sciences'],
            ]],
            ['code' => 'FPK', 'name' => 'Federal Polytechnic Kaltungo', 'scale' => 4.00, 'programmes' => [
                ['Electrical Engineering Technology', 'Engineering'],
                ['Business Administration', 'Management'],
            ]],
        ];

        foreach ($institutions as $institution) {
            DB::table('institutions')->updateOrInsert(
                ['code' => $institution['code']],
                [
                    'name' => $institution['name'],
                    'state_name' => 'Gombe',
                    'default_scale' => $institution['scale'],
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            $institutionId = DB::table('institutions')->where('code', $institution['code'])->value('id');

            foreach ($institution['programmes'] as [$name, $department]) {
                DB::table('programmes')->updateOrInsert(
                    ['institution_id' => $institutionId, 'name' => $name],
                    ['department' => $department, 'created_at' => $now, 'updated_at' => $now],
                );
            }

            DB::table('academic_periods')->updateOrInsert(
                ['institution_id' => $institutionId, 'session_label' => '2026/2027', 'semester' => 1],
                ['results_due_at' => '2027-03-31 23:59:59', 'created_at' => $now, 'updated_at' => $now],
            );
        }

        foreach ([
            'Akko', 'Balanga', 'Billiri', 'Dukku', 'Funakaye', 'Gombe',
            'Kaltungo', 'Kwami', 'Nafada', 'Shongom', 'Yamaltu/Deba',
        ] as $lga) {
            DB::table('local_governments')->updateOrInsert(
                ['state_name' => 'Gombe', 'name' => $lga],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }
    }
}
