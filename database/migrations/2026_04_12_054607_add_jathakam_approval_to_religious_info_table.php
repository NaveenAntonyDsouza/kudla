<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('religious_info', function (Blueprint $table) {
            $table->string('jathakam_approval_status', 20)->default('approved')->after('jathakam_upload_url');
            $table->string('jathakam_rejection_reason')->nullable()->after('jathakam_approval_status');
            $table->unsignedBigInteger('jathakam_approved_by')->nullable()->after('jathakam_rejection_reason');
            $table->timestamp('jathakam_approved_at')->nullable()->after('jathakam_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('religious_info', function (Blueprint $table) {
            $table->dropColumn(['jathakam_approval_status', 'jathakam_rejection_reason', 'jathakam_approved_by', 'jathakam_approved_at']);
        });
    }
};
