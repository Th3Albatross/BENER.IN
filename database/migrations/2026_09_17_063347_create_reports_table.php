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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->enum('status', [
                'Resolved',
                'Rejected',
                'Pending'
            ])->default('Pending');
            $table->enum('severity', [
                'Low',
                'Medium',
                'High',
                'Critical'
            ]);
            $table->foreignId('users_id');
            $table->foreignId('facility_id');
            $table->foreignId('responses_id');
            $table->string('title', 255);
            $table->text('description');
            $table->date('created_at')->useCurrent();
            $table->date('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->integer('edit_count', unsigned: true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
