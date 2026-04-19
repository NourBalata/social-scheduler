<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
        ]);

        $plan = \App\Models\Plan::where('slug', 'free')->first();

        \App\Models\User::updateOrCreate(
            ['email' => 'nour@admin.com'], 
            [
                'name'     => 'Admin Nour',
                'password' => Hash::make('123456789'), 
                'is_admin' => true, 
                'plan_id'  => $plan->id,
            ]
        );
    }
}