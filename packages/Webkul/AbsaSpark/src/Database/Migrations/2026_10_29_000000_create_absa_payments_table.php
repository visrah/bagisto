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
        Schema::create('absa_payments', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->integer('order_id')->unsigned();
            $table->integer('cart_id')->unsigned()->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3);
            $table->string('checkout_id')->nullable()->unique();
            $table->uuid('client_request_id')->nullable();
            $table->text('redirection_url')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->string('error_message')->nullable();
            $table->json('raw_request')->nullable();
            $table->json('raw_response')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absa_payments');
    }
};
