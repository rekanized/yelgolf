<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holes', function (Blueprint $table): void {
            $table->string('layout_caddie_book_url')->nullable()->after('layout_name');
        });
    }

    public function down(): void
    {
        Schema::table('holes', function (Blueprint $table): void {
            $table->dropColumn('layout_caddie_book_url');
        });
    }
};