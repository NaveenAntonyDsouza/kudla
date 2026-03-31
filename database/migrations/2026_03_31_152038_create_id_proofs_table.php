<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('id_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->enum('document_type', ['aadhaar', 'passport', 'voter_id', 'driving_license']);
            $table->string('document_url', 500);
            $table->string('cloudinary_public_id', 255)->nullable();
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('rejection_reason', 500)->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('profile_id');
            $table->index('verification_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('id_proofs');
    }
};
