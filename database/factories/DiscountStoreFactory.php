<?php

namespace Database\Factories;

use App\Enums\DiscountStoreStatus;
use App\Enums\DiscountStoreType;
use App\Models\DiscountStore;
use App\Models\DiscountStoreCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;

/**
 * @extends Factory<DiscountStore>
 */
class DiscountStoreFactory extends Factory
{
    protected $model = DiscountStore::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $taiwanRegionsData = File::json(resource_path('data/taiwan-regions.json'));
        $randomCityData = fake()->randomElement($taiwanRegionsData);

        return [
            'name' => fake()->company(),
            'status' => DiscountStoreStatus::Pending,
            'type' => $type = fake()->randomElement(DiscountStoreType::cases()),
            'category_id' => DiscountStoreCategory::factory(),
            'city' => $type === DiscountStoreType::Local ? $randomCityData['name'] : '',
            'district' => $type === DiscountStoreType::Local ? fake()->randomElement($randomCityData['districts'])['name'] : '',
            'address' => $type === DiscountStoreType::Local ? fake()->address() : '',
            'latitude' => fake()->optional()->latitude(),
            'longitude' => fake()->optional()->longitude(),
            'verification_method' => '學生證',
            'discount_details' => fake()->sentence(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function online(): static
    {
        return $this->state(fn (): array => ['status' => DiscountStoreStatus::Online]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => ['status' => DiscountStoreStatus::Pending]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['status' => DiscountStoreStatus::Expired]);
    }

    public function ofTypeOnline(): static
    {
        $taiwanRegionsData = File::json(resource_path('data/taiwan-regions.json'));
        $randomCityData = fake()->randomElement($taiwanRegionsData);

        return $this->state(fn (): array => [
            'type' => DiscountStoreType::Online,
            'city' => $randomCityData['name'],
            'district' => fake()->randomElement($randomCityData['districts'])['name'],
            'address' => fake()->url(),
        ]);
    }

    public function ofTypeLocal(): static
    {
        return $this->state(fn (): array => [
            'type' => DiscountStoreType::Local,
        ]);
    }
}
