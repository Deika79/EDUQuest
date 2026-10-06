<?php

use App\Enums\MissionMapTheme;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table): void {
            $table->enum('map_theme', array_column(MissionMapTheme::cases(), 'value'))
                ->default(MissionMapTheme::Fantasy->value)
                ->after('level');
        });
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table): void {
            $table->dropColumn('map_theme');
        });
    }
};
