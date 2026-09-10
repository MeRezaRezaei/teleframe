<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tl_web_document_web_document', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->integer('tl_size')->nullable();
            $table->text('mime_type')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3f5e9c00363c122e19c08567');
            $table->index('account_id', 'ix_3b1b1a99415fcf5127c5c60d');
        });
        Schema::create('tl_web_document_web_document__attributes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_web_document_web_document', 'id', 'fk_4a7efadd691287a640721fcd')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c14c4791227afbcb2654');
            $table->index('account_id', 'ix_d809a7a6d1dcf77db78840dc');
        });
        Schema::create('tl_web_document_web_document_no_proxy', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->integer('tl_size')->nullable();
            $table->text('mime_type')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_da09843e77a2e8319a789a2c');
            $table->index('account_id', 'ix_e9268571035f3efe2680f02a');
        });
        Schema::create('tl_web_document_web_document_no_proxy__attributes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_web_document_web_document_no_proxy', 'id', 'fk_4a93058622d99b4ab8e3efb5')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_deed4e5e6451f3930dca');
            $table->index('account_id', 'ix_11fbba23b1aef4382248d8ac');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_web_document_web_document_no_proxy__attributes');
        Schema::dropIfExists('tl_web_document_web_document_no_proxy');
        Schema::dropIfExists('tl_web_document_web_document__attributes');
        Schema::dropIfExists('tl_web_document_web_document');
    }
};
