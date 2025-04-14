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
        Schema::create('product_images', function (Blueprint $table) {
            $table->id(); // BIGINT, PRIMARY KEY, AUTO_INCREMENT
            $table->foreignId('product_id')->constrained()->onDelete('cascade'); // BIGINT, FOREIGN KEY, NOT NULL
            $table->string('image_path'); // VARCHAR(255), NOT NULL
            $table->boolean('is_primary')->default(false); // TINYINT(1), DEFAULT 0
            $table->timestamps(); // created_at, updated_at TIMESTAMP, NULL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
