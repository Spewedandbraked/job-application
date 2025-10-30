<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Building;
use App\Models\Organization;
use App\Models\OrganizationPhone;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $buildings = Building::factory()->count(50)->create();

        $rootActivities = Activity::factory()
            ->count(10)
            ->create();

        $organizations = Organization::factory()
            ->count(100)
            ->create();

        $organizations->each(function ($organization) {
            OrganizationPhone::factory()
                ->count(random_int(1, 3))
                ->create(['organization_id' => $organization->id]);
        });

        $activities = Activity::all();


        $organizations->each(function ($organization) use ($activities) {
            $selectedActivities = $activities->random(random_int(1, 5));

            $organizationActivities = $selectedActivities->map(function ($activity) use ($organization) {
                return [
                    'organization_id' => $organization->id,
                    'activity_id' => $activity->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            \App\Models\OrganizationActivity::insert($organizationActivities);
        });
    }
}
