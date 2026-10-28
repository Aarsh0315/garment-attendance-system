<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_shift_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('shift_id')->constrained()->restrictOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('assigned_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'effective_from'], 'employee_shift_start_unique');
            $table->unique(['employee_id', 'id'], 'employee_shift_employee_id_unique');
            $table->index(['employee_id', 'effective_from', 'effective_to'], 'employee_shift_period_index');
            $table->index(['shift_id', 'effective_from'], 'shift_effective_from_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_shift_assignments');
    }
};
