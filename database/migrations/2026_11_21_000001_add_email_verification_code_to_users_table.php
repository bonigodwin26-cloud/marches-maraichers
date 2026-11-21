<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('email_verification_code', 255)->nullable()->after('email_verified_at');
            $table->timestamp('email_verification_code_expires_at')->nullable()->after('email_verification_code');
            $table->unsignedTinyInteger('email_verification_attempts')->default(0)->after('email_verification_code_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'email_verification_code',
                'email_verification_code_expires_at',
                'email_verification_attempts',
            ]);
        });
    }
};
