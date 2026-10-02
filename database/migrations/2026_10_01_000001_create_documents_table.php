<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->string('collection', 50);
            $table->string('document_id', 100);
            $table->json('payload');
            $table->primary(['collection', 'document_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('documents'); }
};
