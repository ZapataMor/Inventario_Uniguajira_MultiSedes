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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('SET NULL'); // actor (may be null for system)
            $table->string('action', 20); // INSERT, UPDATE, DELETE, LOGIN, etc.
            $table->string('table_name', 100);
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('details', 255);
            $table->timestamp('created_at')->useCurrent();
            // no updated_at (logs are immutable)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
