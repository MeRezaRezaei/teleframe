<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * NF5 mirror — messages domain: MessageEntity union vector child.
 *
 * tf_messages_entities is the positioned 1:N child of tf_messages for the
 * message.entities payload (Vector<MessageEntity>, 25 ctors). Constructor is
 * the discriminator; offset/length are required by every ctor; every other
 * field is ctor-specific and type-defaulted so partial legacy/union writes
 * place (decomposer childRow writes only present columns). bytes (bank-card
 * crypto etc.) are not part of this union — no hex needed beyond defaults.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_messages_entities', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->integer('position');
            $table->text('constructor');
            $table->integer('offset');
            $table->integer('length');
            $table->text('language')->default(DB::raw("('')"));
            $table->text('url')->default(DB::raw("('')"));
            $table->bigInteger('user_id')->default(0);
            $table->bigInteger('document_id')->default(0);
            $table->boolean('collapsed')->default(false);
            $table->text('old_text')->default(DB::raw("('')"));
            $table->integer('date')->default(0);
            $table->boolean('relative')->default(false);
            $table->boolean('short_time')->default(false);
            $table->boolean('long_time')->default(false);
            $table->boolean('short_date')->default(false);
            $table->boolean('long_date')->default(false);
            $table->boolean('day_of_week')->default(false);

            $table->primary(['account_id', 'id', 'position']);
            $table->index(['account_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_messages_entities');
    }
};
