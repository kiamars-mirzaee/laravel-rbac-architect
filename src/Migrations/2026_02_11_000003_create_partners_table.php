<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('partners')->onDelete('cascade');
            $table->string('type')->nullable(); // 'company', 'department', 'team', etc.
            $table->text('description')->nullable();
            $table->boolean('is_business')->default(false);
            $table->timestamps();

            $table->index('parent_id');
        });

        // Pivot table for partner employees
        Schema::create('partner_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('position')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['partner_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_employees');
        Schema::dropIfExists('partners');
    }
};