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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 15)->unique();
            $table->string('password');
            $table->string('name');
            $table->enum('primary_role', ['farmer', 'owner'])->comment('Role selected during registration');
            $table->enum('active_role', ['farmer', 'owner'])->comment('Currently active role');
            $table->boolean('is_active')->default(true);
            $table->string('remember_token')->nullable();
            $table->timestamps();
            
            $table->index('phone');
            $table->index('primary_role');
            $table->index('active_role');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};