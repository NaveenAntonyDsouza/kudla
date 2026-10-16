<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messages to and from the office WhatsApp number through Meta's Cloud API
 * (Oct 2026). Incoming photos are attached to the sender's profile for
 * approval; everything is kept here for the admin inbox.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_messages')) {
            return;
        }

        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('direction', 3)->index();             // in / out
            $table->string('wa_message_id', 191)->nullable()->unique(); // Meta's id; dedupes retried webhooks
            $table->string('wa_id', 20)->index();                // the member's number, international digits
            $table->string('contact_name', 150)->nullable();     // their WhatsApp profile name
            $table->unsignedBigInteger('profile_id')->nullable()->index();
            $table->string('type', 20);                          // text, image, document, ...
            $table->text('body')->nullable();                    // text, or a media caption
            $table->string('media_id', 191)->nullable();
            $table->string('media_mime', 100)->nullable();
            $table->unsignedBigInteger('profile_photo_id')->nullable(); // set when a photo was attached
            $table->string('status', 20)->nullable();            // received / attached / sent / delivered / read / failed
            $table->text('note')->nullable();                    // what happened, for staff
            $table->unsignedBigInteger('sent_by')->nullable();   // staff user for outgoing messages
            $table->timestamp('read_at')->nullable();            // staff opened it in the inbox
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
