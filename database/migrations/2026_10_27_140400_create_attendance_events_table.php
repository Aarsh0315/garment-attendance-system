<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('attendance_record_id')->nullable();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('office_location_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('event_type', 16);
            $table->string('outcome', 16);
            $table->string('source', 32);
            // The application must write UTC instants explicitly; DATETIME does not enforce a timezone.
            $table->dateTime('event_at');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->decimal('distance_meters', 10, 2)->nullable();
            $table->string('rejection_code', 64)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['employee_id', 'event_at'], 'attendance_event_employee_time_index');
            $table->index(['outcome', 'event_at'], 'attendance_event_outcome_time_index');
            $table->foreign(['employee_id', 'attendance_record_id'], 'attendance_event_employee_record_foreign')
                ->references(['employee_id', 'id'])
                ->on('attendance_records')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_events');
    }
};
