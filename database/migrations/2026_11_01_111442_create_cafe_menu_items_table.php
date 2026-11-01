<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cafe_menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 32);
            $table->string('emoji', 16);
            // How the cup is drawn: mug, glass or teacup.
            $table->string('cup_style', 16)->default('mug');
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('price')->default(3);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        $now = Date::now();

        DB::table('cafe_menu_items')->insert(array_map(
            fn (array $item, int $index): array => [...$item, 'price' => 3, 'is_active' => true, 'sort' => $index, 'created_at' => $now, 'updated_at' => $now],
            [
                ['name' => '美式咖啡', 'emoji' => '☕', 'cup_style' => 'mug', 'description' => '清爽不加糖，提神首選。'],
                ['name' => '拿鐵', 'emoji' => '🥛', 'cup_style' => 'glass', 'description' => '牛奶比咖啡多一點的溫柔。'],
                ['name' => '抹茶拿鐵', 'emoji' => '🍵', 'cup_style' => 'teacup', 'description' => '不想喝咖啡的日子也有選擇。'],
                ['name' => '熱可可', 'emoji' => '🍫', 'cup_style' => 'mug', 'description' => '讀書讀累了，來點甜的。'],
                ['name' => '焙茶', 'emoji' => '🫖', 'cup_style' => 'teacup', 'description' => '淡淡的烘焙香，適合夜讀。'],
            ],
            range(0, 4),
        ));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cafe_menu_items');
    }
};
