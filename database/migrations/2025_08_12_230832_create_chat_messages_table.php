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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique(); // Frontend-generated message ID
            $table->unsignedBigInteger('chat_session_id');
            $table->enum('role', ['user', 'assistant']);
            $table->text('content');
            $table->string('category')->nullable();
            $table->enum('authority', ['ALL', 'SDM', 'HUKUM', 'ADMIN'])->nullable();
            $table->json('metadata')->nullable(); // Store additional data like API response info
            $table->boolean('is_typing')->default(false);
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('chat_session_id')->references('id')->on('chat_sessions')->onDelete('cascade');
            
            // Indexes for better performance
            $table->index('message_id');
            $table->index('chat_session_id');
            $table->index('role');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
