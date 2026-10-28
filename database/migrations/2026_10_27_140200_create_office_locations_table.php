<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('environment', 16);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('radius_meters');
            $table->boolean('is_active')->default(true);
            $table->string('active_environment', 16)
                ->virtualAs('CASE WHEN is_active = 1 THEN environment ELSE NULL END');
            $table->timestamps();

            $table->index(['environment', 'is_active'], 'office_environment_active_index');
            $table->unique('active_environment', 'office_active_environment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_locations');
    }
};
