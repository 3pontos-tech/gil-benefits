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
        // Appointments table - frequently queried fields
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['status', 'appointment_at'], 'idx_appointments_status_date');
            $table->index(['user_id', 'status'], 'idx_appointments_user_status');
            $table->index(['consultant_id', 'appointment_at'], 'idx_appointments_consultant_date');
            $table->index(['company_id', 'status'], 'idx_appointments_company_status');
            $table->index(['created_at'], 'idx_appointments_created_at');
            $table->index(['external_opportunity_id'], 'idx_appointments_external_opportunity');
        });

        // Users table - additional performance indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index(['created_at'], 'idx_users_created_at');
            $table->index(['deleted_at'], 'idx_users_deleted_at');
            $table->index(['external_id'], 'idx_users_external_id');
        });

        // Companies table - additional performance indexes
        Schema::table('companies', function (Blueprint $table) {
            $table->index(['user_id', 'deleted_at'], 'idx_companies_user_deleted');
            $table->index(['created_at'], 'idx_companies_created_at');
            $table->index(['deleted_at'], 'idx_companies_deleted_at');
        });

        // Company employees table - performance indexes
        Schema::table('company_employees', function (Blueprint $table) {
            $table->index(['company_id', 'user_id'], 'idx_company_employees_company_user');
            $table->index(['user_id', 'active'], 'idx_company_employees_user_active');
            $table->index(['role', 'active'], 'idx_company_employees_role_active');
            $table->index(['deleted_at'], 'idx_company_employees_deleted_at');
        });

        // User details table - additional performance indexes
        Schema::table('user_details', function (Blueprint $table) {
            $table->index(['user_id', 'company_id'], 'idx_user_details_user_company');
            $table->index(['phone_number'], 'idx_user_details_phone');
            $table->index(['integration_id'], 'idx_user_details_integration');
            $table->index(['deleted_at'], 'idx_user_details_deleted_at');
        });

        // Consultants table - performance indexes
        Schema::table('consultants', function (Blueprint $table) {
            $table->index(['slug'], 'idx_consultants_slug');
            $table->index(['email'], 'idx_consultants_email');
            $table->index(['external_id'], 'idx_consultants_external_id');
            $table->index(['deleted_at'], 'idx_consultants_deleted_at');
            $table->index(['created_at'], 'idx_consultants_created_at');
        });

        // Billing subscriptions - additional performance indexes
        Schema::table('billing_subscriptions', function (Blueprint $table) {
            $table->index(['subscriptionable_type', 'subscriptionable_id', 'stripe_status'], 'idx_billing_subs_poly_status');
            $table->index(['trial_ends_at'], 'idx_billing_subs_trial_ends');
            $table->index(['ends_at'], 'idx_billing_subs_ends_at');
            $table->index(['created_at'], 'idx_billing_subs_created_at');
        });

        // Billing plans - performance indexes
        Schema::table('billing_plans', function (Blueprint $table) {
            $table->index(['active', 'type'], 'idx_billing_plans_active_type');
            $table->index(['provider', 'active'], 'idx_billing_plans_provider_active');
            $table->index(['slug'], 'idx_billing_plans_slug');
            $table->index(['deleted_at'], 'idx_billing_plans_deleted_at');
        });

        // Billing plan prices - performance indexes
        Schema::table('billing_plan_prices', function (Blueprint $table) {
            $table->index(['billing_plan_id', 'active'], 'idx_billing_prices_plan_active');
            $table->index(['active', 'default'], 'idx_billing_prices_active_default');
            $table->index(['type', 'active'], 'idx_billing_prices_type_active');
            $table->index(['deleted_at'], 'idx_billing_prices_deleted_at');
        });

        // Plans table - performance indexes
        Schema::table('plans', function (Blueprint $table) {
            $table->index(['company_id', 'deleted_at'], 'idx_plans_company_deleted');
            $table->index(['deleted_at'], 'idx_plans_deleted_at');
        });

        // Plan items table - performance indexes
        Schema::table('plan_items', function (Blueprint $table) {
            $table->index(['plan_id', 'type'], 'idx_plan_items_plan_type');
            $table->index(['deleted_at'], 'idx_plan_items_deleted_at');
        });

        // Media table - additional performance indexes
        Schema::table('media', function (Blueprint $table) {
            $table->index(['model_type', 'model_id', 'collection_name'], 'idx_media_model_collection');
            $table->index(['created_at'], 'idx_media_created_at');
        });

        // Tags and taggables - performance indexes
        Schema::table('tags', function (Blueprint $table) {
            $table->index(['type', 'slug'], 'idx_tags_type_slug');
            $table->index(['order_column'], 'idx_tags_order');
        });

        // Sessions table - additional performance indexes
        Schema::table('sessions', function (Blueprint $table) {
            $table->index(['user_id', 'last_activity'], 'idx_sessions_user_activity');
        });

        // Permissions and roles - performance indexes
        Schema::table('permissions', function (Blueprint $table) {
            $table->index(['guard_name'], 'idx_permissions_guard');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->index(['guard_name'], 'idx_roles_guard');
        });

        // Model has permissions - performance indexes
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->index(['model_type', 'permission_id'], 'idx_model_perms_type_perm');
        });

        // Model has roles - performance indexes
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->index(['model_type', 'role_id'], 'idx_model_roles_type_role');
        });

        // Jobs table - additional performance indexes
        Schema::table('jobs', function (Blueprint $table) {
            $table->index(['available_at', 'reserved_at'], 'idx_jobs_available_reserved');
        });

        // Failed jobs table - performance indexes
        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->index(['failed_at'], 'idx_failed_jobs_failed_at');
        });

        // Mail related tables - performance indexes (only if tables exist)
        if (Schema::hasTable('mails')) {
            Schema::table('mails', function (Blueprint $table) {
                $table->index(['sent_at'], 'idx_mails_sent_at');
                $table->index(['delivered_at'], 'idx_mails_delivered_at');
                $table->index(['last_opened_at'], 'idx_mails_last_opened');
                $table->index(['created_at'], 'idx_mails_created_at');
            });
        }

        if (Schema::hasTable('mail_events')) {
            Schema::table('mail_events', function (Blueprint $table) {
                $table->index(['mail_id', 'type'], 'idx_mail_events_mail_type');
                $table->index(['occurred_at'], 'idx_mail_events_occurred');
            });
        }

        // Inbound webhooks - performance indexes (only if table exists)
        if (Schema::hasTable('inbound_webhooks')) {
            Schema::table('inbound_webhooks', function (Blueprint $table) {
                $table->index(['source', 'event'], 'idx_webhooks_source_event');
                $table->index(['created_at'], 'idx_webhooks_created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Appointments table
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_status_date');
            $table->dropIndex('idx_appointments_user_status');
            $table->dropIndex('idx_appointments_consultant_date');
            $table->dropIndex('idx_appointments_company_status');
            $table->dropIndex('idx_appointments_created_at');
            $table->dropIndex('idx_appointments_external_opportunity');
        });

        // Users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_created_at');
            $table->dropIndex('idx_users_deleted_at');
            $table->dropIndex('idx_users_external_id');
        });

        // Companies table
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex('idx_companies_user_deleted');
            $table->dropIndex('idx_companies_created_at');
            $table->dropIndex('idx_companies_deleted_at');
        });

        // Company employees table
        Schema::table('company_employees', function (Blueprint $table) {
            $table->dropIndex('idx_company_employees_company_user');
            $table->dropIndex('idx_company_employees_user_active');
            $table->dropIndex('idx_company_employees_role_active');
            $table->dropIndex('idx_company_employees_deleted_at');
        });

        // User details table
        Schema::table('user_details', function (Blueprint $table) {
            $table->dropIndex('idx_user_details_user_company');
            $table->dropIndex('idx_user_details_phone');
            $table->dropIndex('idx_user_details_integration');
            $table->dropIndex('idx_user_details_deleted_at');
        });

        // Consultants table
        Schema::table('consultants', function (Blueprint $table) {
            $table->dropIndex('idx_consultants_slug');
            $table->dropIndex('idx_consultants_email');
            $table->dropIndex('idx_consultants_external_id');
            $table->dropIndex('idx_consultants_deleted_at');
            $table->dropIndex('idx_consultants_created_at');
        });

        // Billing subscriptions
        Schema::table('billing_subscriptions', function (Blueprint $table) {
            $table->dropIndex('idx_billing_subs_poly_status');
            $table->dropIndex('idx_billing_subs_trial_ends');
            $table->dropIndex('idx_billing_subs_ends_at');
            $table->dropIndex('idx_billing_subs_created_at');
        });

        // Billing plans
        Schema::table('billing_plans', function (Blueprint $table) {
            $table->dropIndex('idx_billing_plans_active_type');
            $table->dropIndex('idx_billing_plans_provider_active');
            $table->dropIndex('idx_billing_plans_slug');
            $table->dropIndex('idx_billing_plans_deleted_at');
        });

        // Billing plan prices
        Schema::table('billing_plan_prices', function (Blueprint $table) {
            $table->dropIndex('idx_billing_prices_plan_active');
            $table->dropIndex('idx_billing_prices_active_default');
            $table->dropIndex('idx_billing_prices_type_active');
            $table->dropIndex('idx_billing_prices_deleted_at');
        });

        // Plans table
        Schema::table('plans', function (Blueprint $table) {
            $table->dropIndex('idx_plans_company_deleted');
            $table->dropIndex('idx_plans_deleted_at');
        });

        // Plan items table
        Schema::table('plan_items', function (Blueprint $table) {
            $table->dropIndex('idx_plan_items_plan_type');
            $table->dropIndex('idx_plan_items_deleted_at');
        });

        // Media table
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex('idx_media_model_collection');
            $table->dropIndex('idx_media_created_at');
        });

        // Tags
        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex('idx_tags_type_slug');
            $table->dropIndex('idx_tags_order');
        });

        // Sessions table
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex('idx_sessions_user_activity');
        });

        // Permissions and roles
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex('idx_permissions_guard');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex('idx_roles_guard');
        });

        // Model has permissions
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropIndex('idx_model_perms_type_perm');
        });

        // Model has roles
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropIndex('idx_model_roles_type_role');
        });

        // Jobs table
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('idx_jobs_available_reserved');
        });

        // Failed jobs table
        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->dropIndex('idx_failed_jobs_failed_at');
        });

        // Mail related tables (only if tables exist)
        if (Schema::hasTable('mails')) {
            Schema::table('mails', function (Blueprint $table) {
                $table->dropIndex('idx_mails_sent_at');
                $table->dropIndex('idx_mails_delivered_at');
                $table->dropIndex('idx_mails_last_opened');
                $table->dropIndex('idx_mails_created_at');
            });
        }

        if (Schema::hasTable('mail_events')) {
            Schema::table('mail_events', function (Blueprint $table) {
                $table->dropIndex('idx_mail_events_mail_type');
                $table->dropIndex('idx_mail_events_occurred');
            });
        }

        // Inbound webhooks (only if table exists)
        if (Schema::hasTable('inbound_webhooks')) {
            Schema::table('inbound_webhooks', function (Blueprint $table) {
                $table->dropIndex('idx_webhooks_source_event');
                $table->dropIndex('idx_webhooks_created_at');
            });
        }
    }
};
