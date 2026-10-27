<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_api_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained('organisations')->cascadeOnDelete();
            $table->unsignedBigInteger('loan_payment_id')->nullable();
            $table->uuid('callback_token')->unique();
            $table->string('product', 50);
            $table->string('environment', 20);
            $table->string('status', 30)->default('submitted');
            $table->text('request_payload')->nullable();
            $table->text('response_payload')->nullable();
            $table->text('callback_payload')->nullable();
            $table->string('provider_conversation_id', 150)->nullable();
            $table->string('provider_transaction_id', 150)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['organisation_id', 'product', 'created_at'], 'mpesa_api_ops_org_product_created_idx');
            $table->index(['organisation_id', 'status'], 'mpesa_api_ops_org_status_idx');
            $table->index(['organisation_id', 'loan_payment_id', 'status'], 'mpesa_api_ops_org_payment_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_api_operations');
    }
};
