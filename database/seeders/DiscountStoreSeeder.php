<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DiscountStore;
use App\Models\DiscountStoreCategory;
use App\Models\DiscountStoreComment;
use Illuminate\Database\Seeder;

final class DiscountStoreSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => '飲食', 'icon' => 'heroicon-o-sparkles', 'sort_order' => 10],
            ['name' => '文具', 'icon' => 'heroicon-o-pencil-square', 'sort_order' => 20],
            ['name' => '數位服務', 'icon' => 'heroicon-o-computer-desktop', 'sort_order' => 30],
            ['name' => '生活用品', 'icon' => 'heroicon-o-shopping-bag', 'sort_order' => 40],
        ];

        foreach ($categories as $categoryData) {
            $category = DiscountStoreCategory::query()
                ->firstOrCreate([
                    'name' => $categoryData['name'],
                ], [
                    'icon' => $categoryData['icon'],
                    'sort_order' => $categoryData['sort_order'],
                ]);

            DiscountStore::factory()->for($category, 'category')->online()->ofTypeLocal()->count(1)->create();
            DiscountStore::factory()->for($category, 'category')->online()->ofTypeOnline()->count(1)->create();
            DiscountStore::factory()->for($category, 'category')->pending()->ofTypeLocal()->count(1)->create();
            DiscountStore::factory()->for($category, 'category')->expired()->ofTypeOnline()->count(1)->create();
        }

        DiscountStore::all()->each(function (DiscountStore $store): void {
            DiscountStoreComment::factory()->for($store, 'store')->approved()->count(2)->create();
            DiscountStoreComment::factory()->for($store, 'store')->count(1)->create();
        });
    }
}
