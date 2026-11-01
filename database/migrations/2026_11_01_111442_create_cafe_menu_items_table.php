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
                ['name' => '美式咖啡', 'emoji' => '☕', 'cup_style' => 'mug', 'description' => '無糖咖啡。'],
                ['name' => '拿鐵', 'emoji' => '🥛', 'cup_style' => 'glass', 'description' => '加了一點牛奶的咖啡。'],
                ['name' => '抹茶拿鐵', 'emoji' => '🍵', 'cup_style' => 'teacup', 'description' => '不喝咖啡也可以考慮抹茶。'],
                ['name' => '熱可可', 'emoji' => '🍫', 'cup_style' => 'mug', 'description' => '喜歡甜的也可以考慮熱可可。'],
                ['name' => '焙茶', 'emoji' => '🫖', 'cup_style' => 'teacup', 'description' => '喜歡純茶的同學也可考慮焙茶。'],
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
