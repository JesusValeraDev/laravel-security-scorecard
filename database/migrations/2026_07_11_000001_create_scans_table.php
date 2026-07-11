<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scans', function (Blueprint $table): void {
            $table->id();
            $table->ulid('token')->unique(); // public, shareable identifier
            $table->string('url');
            $table->string('host')->index();
            $table->string('status')->default('pending'); // pending|completed|failed
            $table->string('grade_letter')->nullable();
            $table->unsignedTinyInteger('grade_score')->nullable();
            $table->json('findings')->nullable();
            $table->json('passed')->nullable();
            $table->string('error')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
};
