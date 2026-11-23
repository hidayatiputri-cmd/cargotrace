<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->string('type', 60)->index(); // 'customs_hold', 'weather_delay', 'damage', 'route_deviation', etc.
            $table->string('severity', 30)->default('medium')->index(); // 'low', 'medium', 'high', 'critical'
            $table->string('status', 30)->default('open')->index(); // 'open', 'under_review', 'resolved', 'closed'
            $table->text('description');
            $table->dateTime('detected_at')->index();
            $table->dateTime('resolved_at')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shipment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_exceptions');
    }
};
