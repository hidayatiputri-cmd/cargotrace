<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_number', 50)->unique()->index();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('carrier_id')->constrained('carriers')->restrictOnDelete();
            $table->foreignId('origin_location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('destination_location_id')->constrained('locations')->restrictOnDelete();
            $table->string('cargo_type');
            $table->decimal('weight', 12, 2)->default(0);
            $table->decimal('volume', 12, 2)->default(0);
            $table->dateTime('expected_departure');
            $table->dateTime('expected_arrival');
            $table->dateTime('estimated_arrival')->nullable();
            $table->string('current_status', 50)->default('booked')->index();
            $table->boolean('is_delayed')->default(false)->index();
            $table->integer('delay_hours')->default(0);
            $table->string('delay_severity', 30)->default('none')->index();
            $table->timestamps();

            $table->index(['customer_id', 'current_status']);
            $table->index(['carrier_id', 'current_status']);
            $table->index(['origin_location_id', 'destination_location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
