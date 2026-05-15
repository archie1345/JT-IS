<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'employee_role')) {
            DB::statement('ALTER TABLE users ADD COLUMN employee_role VARCHAR(50) NULL AFTER user_type');
        }

        DB::statement("UPDATE users SET user_type = 'employee' WHERE user_type = 'jte'");
        DB::statement("ALTER TABLE users MODIFY user_type ENUM('client','employee','admin') NOT NULL DEFAULT 'client'");
    }

    public function down(): void
    {
        DB::statement("UPDATE users SET user_type = 'jte' WHERE user_type = 'employee'");
        DB::statement("ALTER TABLE users MODIFY user_type ENUM('jte','employee','client','admin') NOT NULL DEFAULT 'jte'");

        if (Schema::hasColumn('users', 'employee_role')) {
            DB::statement('ALTER TABLE users DROP COLUMN employee_role');
        }
    }
};
