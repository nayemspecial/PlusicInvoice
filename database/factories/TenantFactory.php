<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();
        $subdomain = Str::slug(Str::before($name, ' '));

        return [
            'name' => $name,
            'subdomain' => $subdomain,
            'database_name' => 'plusic_invoice_tenant_'.$subdomain,
            'provisioned_at' => null, // set true only after real DB creation — see TenantSeeder
        ];
    }
}
