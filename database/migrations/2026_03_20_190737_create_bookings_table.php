<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_token')->nullable()->index();
            $table->timestamp('session_token_expires_at')->nullable();
            $table->unsignedInteger('unit_id');
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedInteger('nights');
            $table->unsignedInteger('quantity');
            $table->json('guests');
            $table->decimal('price_per_unit', 10, 2);
            $table->decimal('base_price', 10, 2);
            $table->decimal('tax_amount', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->enum('status', ['draft', 'pending', 'confirmed', 'failed'])
                ->default('draft');
            $table->string('channel_manager_ref')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
