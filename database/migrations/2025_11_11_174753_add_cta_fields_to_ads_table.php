<?php

// ==================================================================
// STEP 1: CREATE MIGRATION
// Run: php artisan make:migration add_cta_fields_to_ads_table
// ==================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->string('cta_button_text')->nullable()->after('value'); // e.g., "Shop Now", "Learn More"
            $table->string('cta_button_url')->nullable()->after('cta_button_text'); // Where button links to
            $table->text('description')->nullable()->after('title'); // Ad description for preview page
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn(['cta_button_text', 'cta_button_url', 'description']);
        });
    }
};