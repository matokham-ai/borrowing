<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organisations', function (Blueprint $table) {
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('sms_notifications_enabled')->default(true);
            $table->boolean('email_notifications_enabled')->default(true);
            $table->boolean('in_app_notifications_enabled')->default(true);
            $table->string('notification_sender_name')->nullable();
            $table->string('notification_reply_to_email')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('organisations', function (Blueprint $table) {
            $table->dropColumn([
                'notifications_enabled',
                'sms_notifications_enabled',
                'email_notifications_enabled',
                'in_app_notifications_enabled',
                'notification_sender_name',
                'notification_reply_to_email',
            ]);
        });
    }
};
