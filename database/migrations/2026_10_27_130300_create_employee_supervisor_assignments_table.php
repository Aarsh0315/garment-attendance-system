<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_supervisor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('supervisor_user_id')->constrained('users')->restrictOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'effective_from', 'effective_to'], 'employee_supervisor_period_index');
            $table->index(['supervisor_user_id', 'effective_from'], 'supervisor_effective_from_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_supervisor_assignments');
    }
};
