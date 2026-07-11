<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitors', function (Blueprint $table): void {
            $table->id();
            $table->ulid('token')->unique(); // used for manage/unsubscribe links
            $table->string('url');
            $table->string('host')->index();
            $table->string('email');
            $table->unsignedSmallInteger('frequency_hours')->default(24);
            $table->boolean('active')->default(true);
            $table->string('last_grade_letter')->nullable();
            $table->unsignedTinyInteger('last_grade_score')->nullable();
            $table->unsignedBigInteger('last_scan_id')->nullable();
            $table->timestamp('last_scanned_at')->nullable();
            $table->timestamps();

            $table->unique(['host', 'email']); // one monitor per site per recipient
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};
