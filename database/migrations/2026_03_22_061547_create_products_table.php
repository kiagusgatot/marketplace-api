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
            $table->id();
            // Foreign keys
            $table->foreignId('category_id')->constrained('product_categories')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            
            // Kolom data
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 12, 2);
            $table->string('thumbnail')->nullable();
            $table->string('file_path')->nullable();
            
            // Kolom sistem (Diberi nilai default agar tidak error saat user post data)
            $table->decimal('rating', 3, 1)->default(0);
            $table->integer('download_count')->default(0);
            $table->string('status')->default('pending');
            
            $table->timestamps();
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
