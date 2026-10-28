<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->string('employee_code', 64)->unique();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('designation');
            $table->date('joining_date');
            $table->string('phone', 32)->nullable();
            $table->timestamps();

            $table->index(['department_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
