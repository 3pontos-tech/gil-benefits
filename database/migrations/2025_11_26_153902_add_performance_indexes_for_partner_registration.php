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
        Schema::table('user_details', function (Blueprint $table) {
            // Add index for tax_id (CPF) for faster lookups during validation
            // Note: unique constraint already exists, but adding explicit index for performance
            $table->index('tax_id', 'idx_user_details_tax_id');

            // Add index for document_id (RG) for faster lookups
            $table->index('document_id', 'idx_user_details_document_id');
        });

        Schema::table('companies', function (Blueprint $table) {
            // Add index for partner_code for faster lookups during validation
            // Note: unique constraint already exists, but adding explicit index for performance
            $table->index('partner_code', 'idx_companies_partner_code');
        });

        Schema::table('users', function (Blueprint $table) {
            // Add index for email for faster lookups during validation
            // Note: unique constraint already exists, but adding explicit index for performance
            $table->index('email', 'idx_users_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->dropIndex('idx_user_details_tax_id');
            $table->dropIndex('idx_user_details_document_id');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex('idx_companies_partner_code');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email');
        });
    }
};
