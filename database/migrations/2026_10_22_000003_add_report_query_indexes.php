<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->index(['users_id', 'status', 'created_at'], 'reports_user_status_created_idx');
            $table->index(['status', 'severity', 'created_at'], 'reports_status_severity_created_idx');
            $table->index(['facilities_id', 'created_at'], 'reports_facility_created_idx');
            $table->index(['categories_id', 'created_at'], 'reports_category_created_idx');
        });

        Schema::table('responses', function (Blueprint $table): void {
            $table->index(['reports_id', 'created_at'], 'responses_report_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table): void {
            $table->dropIndex('responses_report_created_idx');
        });

        Schema::table('reports', function (Blueprint $table): void {
            $table->dropIndex('reports_user_status_created_idx');
            $table->dropIndex('reports_status_severity_created_idx');
            $table->dropIndex('reports_facility_created_idx');
            $table->dropIndex('reports_category_created_idx');
        });
    }
};
