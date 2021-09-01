<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name')->unique();
            $table->string('slug');
            $table->text('description');
            $table->decimal('price');
            $table->decimal('discount');
            $table->string('sku')->nullable();
            $table->boolean('in_stock');
            $table->integer('quantity')->nullable();
            $table->decimal('weight')->nullable();
            $table->integer('sold')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
