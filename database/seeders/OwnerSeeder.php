<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OwnerSeeder extends Seeder
{
    public function run(): void
    {
        $ownerRole = Role::where('name', 'owner')->first();

        $owner = User::firstOrCreate(
            ['email' => 'dev.cossi001@gmail.com'],
            [
                'name' => 'System Owner',
                'password' => Hash::make('22360010s'),
                'phone' => '0725830546',
                'is_active' => true,
            ]
        );

        if ($ownerRole) {
            $owner->roles()->syncWithoutDetaching([$ownerRole->id]);
        }
    }
}
