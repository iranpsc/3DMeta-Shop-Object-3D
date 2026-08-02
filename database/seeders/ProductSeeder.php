<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Tag;
use Ybazli\Faker\Facades\Faker;
use Database\Seeders\CategorySeeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(AttributeSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(TagSeeder::class);

        $products = Product::factory()->count(150)->create();

        foreach ($products as $product) {
            $product->tags()->attach(Tag::inRandomOrder()->limit(5)->pluck('id')->toArray());
            $product->attributes()->attach(1, ['value' => Faker::word()]);
            $product->attributes()->attach(2, ['value' => Faker::word()]);
            $product->attributes()->attach(3, ['value' => Faker::word()]);
            $product->attributes()->attach(4, ['value' => Faker::word()]);
            $product->attributes()->attach(5, ['value' => Faker::word()]);

            for($i = 1; $i <= 3; $i++) {
                $product->images()->create([
                    'path' => '/products/digital-chair.png',
                    // 'is_main' => $i === 1 ? true : false
                ]);
            }

            $product->files()->create([
                'name' => 'digital-chair.pdf',
                'path' => 'products/digital-chair.pdf',
                'type' => 'image/pdf',
                'size' => '1000',
            ]);
        }
    }
}
