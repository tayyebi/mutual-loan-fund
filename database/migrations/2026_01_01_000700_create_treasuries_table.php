<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A treasury records WHERE an asset is held. Its balance is never stored
        // here: it is derived from the posted ledger lines of its account.
        Schema::create('treasuries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('currency');
            $table->string('network')->nullable();
            $table->string('external_identifier')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['group_id', 'status']);
            $table->unique(['group_id', 'name']);
        });

        DB::statement("ALTER TABLE treasuries ADD CONSTRAINT treasuries_type_check CHECK (type IN ('crypto','bank'))");
        DB::statement("ALTER TABLE treasuries ADD CONSTRAINT treasuries_status_check CHECK (status IN ('active','inactive'))");

        // Crypto treasuries must declare their network; bank treasuries must not.
        DB::statement("
            ALTER TABLE treasuries ADD CONSTRAINT treasuries_network_check
            CHECK ((type = 'crypto' AND network IS NOT NULL) OR (type = 'bank' AND network IS NULL))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('treasuries');
    }
};
