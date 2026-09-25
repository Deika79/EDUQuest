<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->enum('role', array_column(UserRole::cases(), 'value'))
                ->default(UserRole::Student->value)
                ->after('password');
            $table->boolean('active')->default(true)->after('role');
            $table->boolean('must_change_password')->default(false)->after('active');
            $table->foreignId('created_by')->nullable()->after('must_change_password')
                ->constrained('users')->nullOnDelete();
        });

        DB::table('users')->orderBy('id')->eachById(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update([
                'username' => 'user-'.$user->id,
                'role' => UserRole::Teacher->value,
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->whereNull('email')->orderBy('id')->eachById(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update([
                'email' => 'rollback-user-'.$user->id.'@invalid.local',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->dropConstrainedForeignId('created_by');
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'role', 'active', 'must_change_password']);
        });
    }
};
