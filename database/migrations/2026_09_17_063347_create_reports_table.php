<?php

use App\Models\Category;
use App\Models\Facility;
use App\Models\User;
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
            $table->uuid('identifier')->unique();
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
            $table->foreignIdFor(Category::class, 'categories_id')->constrained()->nullable()->default(null);
            $table->foreignIdFor(User::class, 'users_id')->constrained();
            $table->foreignIdFor(Facility::class, 'facilities_id')->constrained();
            $table->string('title', 255);
            $table->string('location_description', 255);
            $table->text('description')->nullable();
            $table->date('incident_date')->useCurrent();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->integer('edit_count', unsigned: true)->default(0);
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
