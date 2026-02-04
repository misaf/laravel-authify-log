<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        $this->createAuthifyLogsTable();
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('authify_logs');
        Schema::enableForeignKeyConstraints();
    }

    private function createAuthifyLogsTable(): void
    {
        Schema::create('authify_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->tinyInteger('action');
            $table->ipAddress('ip_address');
            $table->char('ip_country', 2)
                ->default('XX');
            $table->mediumText('user_agent');
            $table->timestampsTz();

            $table->index(['user_id']);
            $table->index(['action']);
            $table->index(['ip_address']);
        });
    }
};
