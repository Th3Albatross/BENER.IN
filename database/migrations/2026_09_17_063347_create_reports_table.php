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
            $table->foreignIdFor(User::class, 'users_id')->constrained()->default(0);
            $table->foreignIdFor(Facility::class, 'facilities_id')->constrained()->default(0);
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->date('created_at')->useCurrent();
            $table->date('updated_at')->useCurrent()->useCurrentOnUpdate();
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
