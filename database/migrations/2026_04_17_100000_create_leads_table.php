<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            // Basic info
            $table->string('full_name', 150);
            $table->string('phone', 20);
            $table->string('email', 150)->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->unsignedTinyInteger('age')->nullable();

            // Lead tracking
            $table->string('source', 50);                       // walk_in, phone, website, referral, etc.
            $table->string('status', 30)->default('new');       // new, contacted, interested, not_interested, registered, lost

            // Assignment
            $table->foreignId('assigned_to_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_staff_id')->nullable()->constrained('users')->nullOnDelete();

            // Follow-up & notes
            $table->text('notes')->nullable();
            $table->date('follow_up_date')->nullable();

            // Conversion tracking
            $table->foreignId('profile_id')->nullable()->constrained('profiles')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('converted_by_staff_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'assigned_to_staff_id'], 'leads_status_assigned_index');
            $table->index('follow_up_date');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
