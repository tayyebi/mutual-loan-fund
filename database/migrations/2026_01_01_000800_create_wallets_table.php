<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // External wallet addresses only. The application is non-custodial and
        // has no column anywhere for a private key, seed phrase or password.
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('group_memberships')->cascadeOnDelete();
            $table->string('currency');
            $table->string('network');
            $table->string('address');
            $table->string('label')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['group_id', 'network', 'address']);
            $table->index(['group_id', 'member_id']);
        });

        DB::statement("ALTER TABLE wallets ADD CONSTRAINT wallets_status_check CHECK (status IN ('active','inactive'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
