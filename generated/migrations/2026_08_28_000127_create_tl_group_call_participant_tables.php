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
        Schema::create('tl_group_call_participant_group_call_participant', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('muted')->default(false);
            $table->boolean('left')->default(false);
            $table->boolean('can_self_unmute')->default(false);
            $table->boolean('just_joined')->default(false);
            $table->boolean('versioned')->default(false);
            $table->boolean('min')->default(false);
            $table->boolean('muted_by_you')->default(false);
            $table->boolean('volume_by_admin')->default(false);
            $table->boolean('self')->default(false);
            $table->boolean('video_joined')->default(false);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_6a47b0177567090ba55c22e8');
            $table->integer('date')->nullable();
            $table->integer('active_date')->nullable();
            $table->integer('source')->nullable();
            $table->integer('volume')->nullable();
            $table->text('about')->nullable();
            $table->bigInteger('raise_hand_rating')->nullable();
            $table->bigInteger('video')->nullable();
            $table->index('video', 'ix_574446f2e211487e93698595');
            $table->bigInteger('presentation')->nullable();
            $table->index('presentation', 'ix_46fd2db71e17627078b96886');
            $table->bigInteger('paid_stars_total')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e019682c66b41a65aac467d4');
            $table->index('account_id', 'ix_e1fde4fc1971390020faa7c5');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_group_call_participant_group_call_participant');
    }
};
