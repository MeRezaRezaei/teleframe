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
        Schema::create('tl_story_reaction_story_reaction', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer_id')->nullable();
            $table->index('peer_id', 'ix_25443aa4e04dc59066243984');
            $table->integer('date')->nullable();
            $table->bigInteger('reaction')->nullable();
            $table->index('reaction', 'ix_f915d27636296772e08e611f');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fefa57816cabf665b36d50f4');
            $table->index('account_id', 'ix_dd3cc63c035de2b13746e523');
        });
        Schema::create('tl_story_reaction_story_reaction_public_forward', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('message')->nullable();
            $table->index('message', 'ix_238a8d24fbfd4ec0d7c97140');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ab549460ec558afa593ed122');
            $table->index('account_id', 'ix_d4a886711017381f561039a0');
        });
        Schema::create('tl_story_reaction_story_reaction_public_repost', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer_id')->nullable();
            $table->index('peer_id', 'ix_2f9bd28698f6853ec58c4534');
            $table->bigInteger('story')->nullable();
            $table->index('story', 'ix_e5e199888e540b47b5df0849');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e5ec87f1bd10a9a155c9ef93');
            $table->index('account_id', 'ix_bc15ad8bf83f7582bb9600ca');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_story_reaction_story_reaction_public_repost');
        Schema::dropIfExists('tl_story_reaction_story_reaction_public_forward');
        Schema::dropIfExists('tl_story_reaction_story_reaction');
    }
};
