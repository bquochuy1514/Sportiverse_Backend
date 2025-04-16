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
        Schema::create('products', function (Blueprint $table) {
            $table->id(); // BIGINT, PRIMARY KEY, AUTO_INCREMENT
            $table->foreignId('sport_id')->constrained()->onDelete('cascade'); // BIGINT, FOREIGN KEY, NOT NULL
            $table->foreignId('category_id')->constrained()->onDelete('cascade'); // BIGINT, FOREIGN KEY, NOT NULL
            $table->string('name'); // VARCHAR(255), NOT NULL
            $table->string('slug')->unique(); // VARCHAR(255), UNIQUE, NOT NULL
            $table->text('description')->nullable(); // TEXT, NULL
            $table->decimal('price', 12, 2); // DECIMAL(12,2), NOT NULL
            $table->decimal('sale_price', 12, 2)->nullable(); // DECIMAL(12,2), NULL
            $table->integer('stock_quantity')->default(0); // INT, DEFAULT 0
            $table->boolean('is_featured')->default(false); // TINYINT(1), DEFAULT 0
            $table->boolean('is_active')->default(true); // TINYINT(1), DEFAULT 1
            $table->timestamps(); // created_at, updated_at TIMESTAMP, NULL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
