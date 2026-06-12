<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'name_user')) {
                $table->string('name_user', 50)->after('name');
            }
            if (!Schema::hasColumn('users', 'nivel_acesso')) {
                $table->integer('nivel_acesso')->default(2)->after('password');
            }
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['name_user', 'nivel_acesso']);
            $table->string('email')->nullable(false)->change();
        });
    }
};