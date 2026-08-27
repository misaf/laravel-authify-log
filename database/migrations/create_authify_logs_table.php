<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // The migration is both auto-loaded and publishable, so it may be seen
        // twice by the migrator; creating the table is therefore idempotent.
        if (Schema::hasTable('authify_logs')) {
            return;
        }

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
            $table->unsignedBigInteger('user_id')
                ->nullable();
            $table->tinyInteger('action');
            $table->ipAddress('ip_address');
            $table->char('ip_country', 2)
                ->default('XX');
            $table->mediumText('user_agent')
                ->nullable();
            $table->timestampsTz();

            $table->index(['user_id']);
            $table->index(['action']);
            $table->index(['ip_address']);
            $table->index(['created_at']);
        });
    }
};
