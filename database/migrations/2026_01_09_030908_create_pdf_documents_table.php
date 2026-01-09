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
        Schema::create('pdf_documents', function (Blueprint $table) {
            $table->id();
            $table->string('name');               // ชื่อเอกสาร
            $table->text('description')->nullable();
            $table->string('filename');           // input.pdf
            $table->integer('total_pages');        // จำนวนหน้า

            $table->json('markers')->nullable();       // รวม marker ทั้งหมด
            $table->json('page_markers')->nullable();  // แยกตามหน้า

            $table->integer('marker_counter')->default(0);

            $table->timestamp('saved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_documents');
    }
};
