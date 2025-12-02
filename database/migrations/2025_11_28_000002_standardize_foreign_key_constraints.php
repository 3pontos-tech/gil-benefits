<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration standardizes all foreign key constraints across the application
     * with proper cascade rules for Laravel 12 compliance.
     */
    public function up(): void
    {
        $this->standardizeCompaniesConstraints();
        $this->standardizeUserDetailsConstraints();
        $this->standardizeAppointmentsConstraints();
        $this->standardizeCompanyEmployeesConstraints();
        $this->standardizeConsultantsConstraints();
        $this->standardizePlansConstraints();
        $this->standardizeAuditLogsConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->revertAuditLogsConstraints();
        $this->revertPlansConstraints();
        $this->revertConsultantsConstraints();
        $this->revertCompanyEmployeesConstraints();
        $this->revertAppointmentsConstraints();
        $this->revertUserDetailsConstraints();
        $this->revertCompaniesConstraints();
    }

    /**
     * Standardize companies table foreign key constraints
     */
    private function standardizeCompaniesConstraints(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            // Ensure user_id foreign key has proper cascade rule
            if ($this->foreignKeyExists('companies', 'companies_user_id_foreign')) {
                $table->dropForeign(['user_id']);
            }
            
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade')
                  ->name('companies_user_id_foreign');
        });
    }

    /**
     * Standardize user_details table foreign key constraints
     */
    private function standardizeUserDetailsConstraints(): void
    {
        Schema::table('user_details', function (Blueprint $table): void {
            // Update user_id foreign key
            if ($this->foreignKeyExists('user_details', 'user_details_user_id_foreign')) {
                $table->dropForeign(['user_id']);
            }
            
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade')
                  ->name('user_details_user_id_foreign');

            // Update company_id foreign key
            if ($this->foreignKeyExists('user_details', 'user_details_company_id_foreign')) {
                $table->dropForeign(['company_id']);
            }
            
            $table->foreign('company_id')
                  ->references('id')
                  ->on('companies')
                  ->onDelete('cascade')
                  ->onUpdate('cascade')
                  ->name('user_details_company_id_foreign');
        });
    }

    /**
     * Standardize appointments table foreign key constraints
     */
    private function standardizeAppointmentsConstraints(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            // Update consultant_id foreign key (nullable, set null on delete)
            if ($this->foreignKeyExists('appointments', 'appointments_consultant_id_foreign')) {
                $table->dropForeign(['consultant_id']);
            }
            
            $table->foreign('consultant_id')
                  ->references('id')
                  ->on('consultants')
                  ->onDelete('set null')
                  ->onUpdate('cascade')
                  ->name('appointments_consultant_id_foreign');

            // Update user_id foreign key
            if ($this->foreignKeyExists('appointments', 'appointments_user_id_foreign')) {
                $table->dropForeign(['user_id']);
            }
            
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade')
                  ->name('appointments_user_id_foreign');

            // Add company_id foreign key if it exists
            if (Schema::hasColumn('appointments', 'company_id')) {
                if ($this->foreignKeyExists('appointments', 'appointments_company_id_foreign')) {
                    $table->dropForeign(['company_id']);
                }
                
                $table->foreign('company_id')
                      ->references('id')
                      ->on('companies')
                      ->onDelete('cascade')
                      ->onUpdate('cascade')
                      ->name('appointments_company_id_foreign');
            }
        });
    }

    /**
     * Standardize company_employees table foreign key constraints
     */
    private function standardizeCompanyEmployeesConstraints(): void
    {
        Schema::table('company_employees', function (Blueprint $table): void {
            // Update company_id foreign key
            if ($this->foreignKeyExists('company_employees', 'company_employees_company_id_foreign')) {
                $table->dropForeign(['company_id']);
            }
            
            $table->foreign('company_id')
                  ->references('id')
                  ->on('companies')
                  ->onDelete('cascade')
                  ->onUpdate('cascade')
                  ->name('company_employees_company_id_foreign');

            // Update user_id foreign key
            if ($this->foreignKeyExists('company_employees', 'company_employees_user_id_foreign')) {
                $table->dropForeign(['user_id']);
            }
            
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade')
                  ->name('company_employees_user_id_foreign');
        });
    }

    /**
     * Standardize consultants table foreign key constraints
     */
    private function standardizeConsultantsConstraints(): void
    {
        if (Schema::hasTable('consultants')) {
            Schema::table('consultants', function (Blueprint $table): void {
                // Add any missing foreign key constraints for consultants
                // This table structure may vary based on implementation
            });
        }
    }

    /**
     * Standardize plans table foreign key constraints
     */
    private function standardizePlansConstraints(): void
    {
        if (Schema::hasTable('plans')) {
            Schema::table('plans', function (Blueprint $table): void {
                // Update company_id foreign key if it exists
                if (Schema::hasColumn('plans', 'company_id')) {
                    if ($this->foreignKeyExists('plans', 'plans_company_id_foreign')) {
                        $table->dropForeign(['company_id']);
                    }
                    
                    $table->foreign('company_id')
                          ->references('id')
                          ->on('companies')
                          ->onDelete('cascade')
                          ->onUpdate('cascade')
                          ->name('plans_company_id_foreign');
                }
            });
        }

        if (Schema::hasTable('plan_items')) {
            Schema::table('plan_items', function (Blueprint $table): void {
                // Update plan_id foreign key
                if (Schema::hasColumn('plan_items', 'plan_id')) {
                    if ($this->foreignKeyExists('plan_items', 'plan_items_plan_id_foreign')) {
                        $table->dropForeign(['plan_id']);
                    }
                    
                    $table->foreign('plan_id')
                          ->references('id')
                          ->on('plans')
                          ->onDelete('cascade')
                          ->onUpdate('cascade')
                          ->name('plan_items_plan_id_foreign');
                }
            });
        }
    }

    /**
     * Standardize audit_logs table foreign key constraints
     */
    private function standardizeAuditLogsConstraints(): void
    {
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table): void {
                // Add user_id foreign key with set null on delete (preserve audit trail)
                if (Schema::hasColumn('audit_logs', 'user_id')) {
                    if ($this->foreignKeyExists('audit_logs', 'audit_logs_user_id_foreign')) {
                        $table->dropForeign(['user_id']);
                    }
                    
                    $table->foreign('user_id')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null')
                          ->onUpdate('cascade')
                          ->name('audit_logs_user_id_foreign');
                }
            });
        }
    }

    /**
     * Check if a foreign key exists on a table
     */
    private function foreignKeyExists(string $table, string $foreignKeyName): bool
    {
        try {
            $connection = Schema::getConnection();
            
            if ($connection->getDriverName() === 'sqlite') {
                // SQLite specific check
                $result = $connection->select("PRAGMA foreign_key_list({$table})");
                foreach ($result as $fk) {
                    // SQLite doesn't store constraint names, so we'll check by table reference
                    if (str_contains($foreignKeyName, $fk->table)) {
                        return true;
                    }
                }
                return false;
            }
            
            // For other databases
            $result = $connection->select("
                SELECT COUNT(*) as count 
                FROM information_schema.key_column_usage 
                WHERE table_name = ? AND constraint_name = ?
            ", [$table, $foreignKeyName]);
            
            return $result[0]->count > 0;
        } catch (\Exception $e) {
            // If we can't check, assume it doesn't exist
            return false;
        }
    }

    /**
     * Revert companies constraints
     */
    private function revertCompaniesConstraints(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            if ($this->foreignKeyExists('companies', 'companies_user_id_foreign')) {
                $table->dropForeign('companies_user_id_foreign');
                // Restore original constraint
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
        });
    }

    /**
     * Revert user_details constraints
     */
    private function revertUserDetailsConstraints(): void
    {
        Schema::table('user_details', function (Blueprint $table): void {
            if ($this->foreignKeyExists('user_details', 'user_details_user_id_foreign')) {
                $table->dropForeign('user_details_user_id_foreign');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
            
            if ($this->foreignKeyExists('user_details', 'user_details_company_id_foreign')) {
                $table->dropForeign('user_details_company_id_foreign');
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }
        });
    }

    /**
     * Revert appointments constraints
     */
    private function revertAppointmentsConstraints(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            if ($this->foreignKeyExists('appointments', 'appointments_consultant_id_foreign')) {
                $table->dropForeign('appointments_consultant_id_foreign');
                $table->foreign('consultant_id')->references('id')->on('consultants');
            }
            
            if ($this->foreignKeyExists('appointments', 'appointments_user_id_foreign')) {
                $table->dropForeign('appointments_user_id_foreign');
                $table->foreign('user_id')->references('id')->on('users');
            }
            
            if (Schema::hasColumn('appointments', 'company_id') && 
                $this->foreignKeyExists('appointments', 'appointments_company_id_foreign')) {
                $table->dropForeign('appointments_company_id_foreign');
            }
        });
    }

    /**
     * Revert company_employees constraints
     */
    private function revertCompanyEmployeesConstraints(): void
    {
        // Note: Original constraints may not have explicit names
        // This is a limitation of reversing foreign key optimizations
    }

    /**
     * Revert consultants constraints
     */
    private function revertConsultantsConstraints(): void
    {
        // Implementation depends on consultants table structure
    }

    /**
     * Revert plans constraints
     */
    private function revertPlansConstraints(): void
    {
        // Implementation depends on plans table structure
    }

    /**
     * Revert audit_logs constraints
     */
    private function revertAuditLogsConstraints(): void
    {
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table): void {
                if ($this->foreignKeyExists('audit_logs', 'audit_logs_user_id_foreign')) {
                    $table->dropForeign('audit_logs_user_id_foreign');
                }
            });
        }
    }
};