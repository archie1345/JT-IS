<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY user_type ENUM('jte','employee','client','admin') NOT NULL DEFAULT 'jte'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY user_type ENUM('jte','client') NOT NULL DEFAULT 'jte'");
    }
};
