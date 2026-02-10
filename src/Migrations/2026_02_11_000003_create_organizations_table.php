<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('organizations')->onDelete('cascade');
            $table->string('type')->nullable(); // 'company', 'department', 'team', etc.
            $table->text('description')->nullable();
            $table->boolean('is_business')->default(false);
            $table->timestamps();

            $table->index('parent_id');
        });

        // Pivot table for organization employees
        Schema::create('organization_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('position')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_employees');
        Schema::dropIfExists('organizations');
    }
};