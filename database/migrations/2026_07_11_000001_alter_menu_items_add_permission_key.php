<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Corrective migration: the original 2026_06_23_000005_create_menu_items_table migration is
// already merged history and is not edited here (no production data exists yet, but this keeps
// the migration log truthful and mirrors the discipline required once real data exists).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->renameColumn('title', 'label');
            $table->renameColumn('url', 'path');
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->string('permission_key', 100)->nullable()->after('icon');
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn('required_role');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->string('required_role', 50)->nullable();
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn('permission_key');
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->renameColumn('label', 'title');
            $table->renameColumn('path', 'url');
        });
    }
};
