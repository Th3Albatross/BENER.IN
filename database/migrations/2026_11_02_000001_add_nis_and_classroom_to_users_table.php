<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Students identify themselves with a NIS (nomor induk siswa) and belong to
     * a classroom such as "XI-1". Email stays on the record as optional contact
     * information, so it becomes nullable. Petugas and admin keep using email
     * and leave both new columns empty.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('nis', 20)->nullable()->unique()->after('name');
            $table->string('classroom', 20)->nullable()->after('nis');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable(false)->change();
            $table->dropUnique(['nis']);
            $table->dropColumn(['nis', 'classroom']);
        });
    }
};
