<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_observations', function (Blueprint $table) {
            $table->decimal('available_quantity', 14, 3)->nullable()->after('quality');
            $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_note')->nullable()->after('reviewed_at');
        });

        Schema::table('contact_requests', function (Blueprint $table) {
            $table->decimal('requested_quantity', 14, 3)->nullable()->after('requested_volume');
            $table->string('requested_unit_snapshot')->nullable()->after('requested_quantity');
            $table->timestamp('reminder_sent_at')->nullable()->after('transaction_declared_at');
        });

        Schema::create('price_alert_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('target_price_xof')->nullable();
            $table->string('phone', 32);
            $table->boolean('notification_consent')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['product_id', 'market_id', 'is_active']);
        });

        Schema::table('notification_deliveries', function (Blueprint $table) {
            $table->foreignId('contact_request_id')->nullable()->change();
            $table->foreignId('price_alert_subscription_id')->nullable()->after('contact_request_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notification_deliveries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('price_alert_subscription_id');
            $table->foreignId('contact_request_id')->nullable(false)->change();
        });

        Schema::dropIfExists('price_alert_subscriptions');

        Schema::table('contact_requests', function (Blueprint $table) {
            $table->dropColumn(['requested_quantity', 'requested_unit_snapshot', 'reminder_sent_at']);
        });

        Schema::table('price_observations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['available_quantity', 'reviewed_at', 'review_note']);
        });
    }
};
