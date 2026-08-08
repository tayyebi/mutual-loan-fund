<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Files live on the private disk; storage_path is never web-reachable.
        Schema::create('transaction_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->string('storage_path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('sha256', 64);
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['group_id', 'transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_receipts');
    }
};
