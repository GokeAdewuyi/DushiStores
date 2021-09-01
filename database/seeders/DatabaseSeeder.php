<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
         Category::factory(3)
             ->has(SubCategory::factory(5), 'subCategories')
             ->create();
//         SubCategory::factory(10)->create();
         Product::factory(100)
             ->has(Media::factory(3), 'media')
             ->create();
//         Media::factory(250)->create();

        $cat = Category::all();
        $sub = SubCategory::all();

        Product::all()->each(function ($product) use ($cat, $sub) {
            $product->categories()->attach(
                $cat->random(rand(1, 3))->pluck('id')->toArray()
            );
            $product->subCategories()->attach(
                $sub->random(rand(1, 3))->pluck('id')->toArray()
            );
        });
    }
}
