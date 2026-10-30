<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('commune')->nullable();
            $table->string('department')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['name', 'commune']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('producers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('organization')->nullable();
            $table->string('phone', 32);
            $table->foreignId('market_id')->nullable()->constrained()->nullOnDelete();
            $table->string('commune')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('notification_consent')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('price_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('market_id')->constrained()->restrictOnDelete();
            $table->foreignId('producer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('collected_by')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('price_xof');
            $table->string('unit_snapshot');
            $table->string('quality')->nullable();
            $table->dateTime('observed_at');
            $table->string('status')->default('published');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'observed_at']);
        });

        Schema::create('contact_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('market_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('price_observation_id')->nullable();
            $table->foreignId('producer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('organization')->nullable();
            $table->string('phone', 32);
            $table->string('requested_volume')->nullable();
            $table->text('message')->nullable();
            $table->boolean('notification_consent')->default(false);
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->boolean('transaction_declared')->nullable();
            $table->boolean('transaction_happened')->nullable();
            $table->timestamp('transaction_declared_at')->nullable();
            $table->timestamps();
            $table->foreign('price_observation_id')->references('id')->on('price_observations')->nullOnDelete();
            $table->index(['status', 'created_at']);
        });

        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_request_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone', 32);
            $table->string('channel', 24);
            $table->text('body');
            $table->string('status')->default('pending');
            $table->string('provider_message_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('contact_requests');
        Schema::dropIfExists('price_observations');
        Schema::dropIfExists('producers');
        Schema::dropIfExists('products');
        Schema::dropIfExists('markets');
    }
};
