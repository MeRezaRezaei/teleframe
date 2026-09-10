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
        Schema::create('tl_contacts_imported_contacts_imported_contacts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_98317f72fb1b310f637306fb');
            $table->index('account_id', 'ix_e540c830e02efb540b68cb94');
        });
        Schema::create('tl_contacts_imported_contacts_imported_contacts__imported', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_0a00a921990a1b68bb0ab909')->references('id')->on('tl_contacts_imported_contacts_imported_contacts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9a72d6b9667af2c4d9eb');
            $table->index('account_id', 'ix_550fa8d5bcc6f456a023a5e2');
        });
        Schema::create('tl_contacts_imported_contacts_imported_contac_05412ff68ebc', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_a20e6096a5308001262d62c4')->references('id')->on('tl_contacts_imported_contacts_imported_contacts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_2507964dabd9044aeee0');
            $table->index('account_id', 'ix_4c6ad35c1e76071213d1c391');
        });
        Schema::create('tl_contacts_imported_contacts_imported_contac_7e82948d3852', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_ee8ff3011c6c181f1e71fd6d')->references('id')->on('tl_contacts_imported_contacts_imported_contacts')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_7e926d326e0690eba859');
            $table->index('account_id', 'ix_22a9f1ca9cbe056a21520e3d');
        });
        Schema::create('tl_contacts_imported_contacts_imported_contacts__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_4c4ba1b9dfa4fb90739d0b32')->references('id')->on('tl_contacts_imported_contacts_imported_contacts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d9cf3f763c145e887d16');
            $table->index('account_id', 'ix_4b0856eb52bb1aa6e4f6fe58');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_contacts_imported_contacts_imported_contacts__users');
        Schema::dropIfExists('tl_contacts_imported_contacts_imported_contac_7e82948d3852');
        Schema::dropIfExists('tl_contacts_imported_contacts_imported_contac_05412ff68ebc');
        Schema::dropIfExists('tl_contacts_imported_contacts_imported_contacts__imported');
        Schema::dropIfExists('tl_contacts_imported_contacts_imported_contacts');
    }
};
