<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Cross-DB reference: employee lives in points_core, so no FK constraint here.
            $table->unsignedBigInteger('employee_id');
            $table->unsignedInteger('total_points')->default(0);
            $table->enum('status', ['pending', 'paid', 'shipped', 'completed', 'cancelled'])
                ->default('pending');
            $table->timestamps();

            $table->index('employee_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
