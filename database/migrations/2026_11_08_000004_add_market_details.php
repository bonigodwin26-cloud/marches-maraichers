<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('markets', function (Blueprint $table): void {
            $table->decimal('latitude', 10, 7)->nullable()->after('department');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('opening_hours')->nullable()->after('longitude');
            $table->string('contact_phone', 32)->nullable()->after('opening_hours');
            $table->string('image_path')->nullable()->after('contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('markets', function (Blueprint $table): void {
            $table->dropColumn(['latitude', 'longitude', 'opening_hours', 'contact_phone', 'image_path']);
        });
    }
};
