<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('employee_shift_assignment_id');
            $table->date('work_date');
            // Schedule snapshots are Asia/Kolkata local wall-clock values; actual punch instants are UTC.
            $table->dateTime('scheduled_start_at');
            $table->dateTime('scheduled_end_at');
            $table->dateTime('late_after_at');
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'work_date'], 'attendance_employee_work_date_unique');
            $table->unique(['employee_id', 'id'], 'attendance_employee_id_unique');
            $table->index(['work_date', 'employee_id'], 'attendance_work_date_employee_index');
            $table->foreign(['employee_id', 'employee_shift_assignment_id'], 'attendance_employee_shift_assignment_foreign')
                ->references(['employee_id', 'id'])
                ->on('employee_shift_assignments')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
