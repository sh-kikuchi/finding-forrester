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
        Schema::table('books', function (Blueprint $table) {
            // 価格(imageカラムの次にカラムを追加。正の整数でデフォルト値は0)
            $table->unsignedInteger('price')->default(0)->after('image');
            // 販売可否(priceカラムの次にカラムを追加。真偽値でデフォルト値はtrue)
            $table->boolean('is_for_sale')->default(true)->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // カラム削除
            $table->dropColumn(['price', 'is_for_sale']);
        });
    }
};
