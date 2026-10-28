<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_record_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 64);
            $table->text('reason');
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['attendance_record_id', 'created_at'], 'attendance_audit_record_time_index');
            $table->index(['actor_user_id', 'created_at'], 'attendance_audit_actor_time_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_audit_logs');
    }
};
