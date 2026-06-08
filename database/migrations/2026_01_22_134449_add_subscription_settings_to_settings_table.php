<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    
        
        DB::table('settings')->insert([
            [
                'key' => 'subscription_expires_at',
                'value' => now()->addYear()->toDateTimeString(),
                'description' => 'The date and time when the system subscription expires.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'subscription_status',
                'value' => 'active',
                'description' => 'The current status of the subscription (active/expired).',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['subscription_expires_at', 'subscription_status'])->delete();
    }
};
