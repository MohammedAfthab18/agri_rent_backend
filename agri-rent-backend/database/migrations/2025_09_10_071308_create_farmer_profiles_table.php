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
        Schema::create('farmer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Farm Information
            $table->string('farm_name')->nullable();
            $table->string('farm_location');
            $table->decimal('farm_size', 10, 2)->comment('Farm size in acres');
            $table->enum('farm_type', ['crop', 'livestock', 'mixed', 'organic', 'other']);
            
            // Farmer Details
            $table->integer('years_of_experience');
            $table->json('crop_types')->nullable()->comment('Array of crops grown');
            $table->json('livestock_types')->nullable()->comment('Array of livestock types');
            
            // Address Details
            $table->string('village');
            $table->string('taluk');
            $table->string('district');
            $table->string('state')->default('Tamil Nadu');
            $table->string('pincode', 6);
            
            // Additional Information
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->text('additional_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->unique('user_id');
            $table->index('district');
            $table->index('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farmer_profiles');
    }
};