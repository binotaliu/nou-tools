<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('push_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('subscribable_type');
            $table->string('subscribable_id');
            $table->string('endpoint', 1024)->charset('ascii');
            $table->boolean('success');
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subscribable_type', 'subscribable_id'], 'push_notification_deliveries_subscribable_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_notification_deliveries');
    }
};
