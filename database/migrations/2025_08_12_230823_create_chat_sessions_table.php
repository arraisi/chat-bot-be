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
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique(); // Frontend-generated session ID
            $table->string('title')->default('New Chat');
            $table->enum('authority', ['ALL', 'SDM', 'HUKUM', 'ADMIN'])->default('SDM');
            $table->string('user_id')->nullable(); // For future user authentication
            $table->integer('message_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('session_id');
            $table->index('user_id');
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
