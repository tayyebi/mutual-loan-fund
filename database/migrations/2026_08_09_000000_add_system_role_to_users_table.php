<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A system administrator operates the platform itself: users and funds,
        // not any fund's financial data. Distinct from group_memberships.role,
        // which only ever grants authority within one fund.
        Schema::table('users', function (Blueprint $table) {
            $table->string('system_role')->default('user')->after('status');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_system_role_check CHECK (system_role IN ('user','system_admin'))");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('system_role');
        });
    }
};
