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
        Schema::create('owner_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Business Information
            $table->string('business_name')->nullable();
            $table->enum('business_type', ['individual', 'company', 'partnership']);
            $table->string('gst_number')->nullable();
            
            // Owner Details
            $table->integer('years_in_business');
            $table->integer('total_equipment_count')->default(0);
            $table->json('equipment_types')->nullable()->comment('Types of equipment owned');
            
            // Service Area
            $table->json('service_districts')->comment('Districts where services are provided');
            $table->decimal('max_delivery_distance', 5, 2)->comment('Maximum delivery distance in km');
            
            // Address Details
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('district');
            $table->string('state')->default('Tamil Nadu');
            $table->string('pincode', 6);
            
            // Bank Details (for receiving payments)
            $table->string('bank_name')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('ifsc_code')->nullable();
            
            // Additional Information
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->boolean('provides_operator')->default(false);
            $table->boolean('provides_delivery')->default(true);
            $table->text('terms_and_conditions')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->unique('user_id');
            $table->index('district');
            $table->index('is_verified');
            $table->index('business_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_profiles');
    }
};