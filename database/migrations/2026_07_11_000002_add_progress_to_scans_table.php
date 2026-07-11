<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table): void {
            $table->unsignedTinyInteger('checks_total')->nullable()->after('grade_score');
            $table->unsignedTinyInteger('checks_done')->default(0)->after('checks_total');
            $table->string('current_check')->nullable()->after('checks_done');
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table): void {
            $table->dropColumn(['checks_total', 'checks_done', 'current_check']);
        });
    }
};
