<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Order matters: plans before tenants (subscriptions will reference plans later),
     * and TenantSeeder does the heavy lifting of actually creating + migrating each
     * tenant's real database — see docs/CONTEXT.md and docs/CHECKLIST.md Phase 3.
     */
    public function run(): void
    {
        // Central platform admin — NOT a tenant user. Separate from App\Models\Tenants\User.
        User::factory()->create([
            'name' => 'Md. Nayemur Rahman',
            'email' => 'admin@plusicinvoice.test',
        ]);

        $this->call([
            PlanSeeder::class,
            TenantSeeder::class,
        ]);
    }
}
