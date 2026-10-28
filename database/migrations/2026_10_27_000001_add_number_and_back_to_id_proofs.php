<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The ID proof form saves a document number and the back of the card, but
 * these columns were never created: every upload failed on every site (kudla
 * log, Jul–Aug 2026). The number keeps only its last 4 characters
 * (App\Support\IdProofFiles::maskNumber).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('id_proofs', function (Blueprint $table) {
            if (! Schema::hasColumn('id_proofs', 'document_number')) {
                $table->string('document_number', 30)->nullable()->after('document_type');
            }
            if (! Schema::hasColumn('id_proofs', 'document_back_url')) {
                $table->string('document_back_url')->nullable()->after('document_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('id_proofs', function (Blueprint $table) {
            foreach (['document_number', 'document_back_url'] as $col) {
                if (Schema::hasColumn('id_proofs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
