<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('match_players', function (Blueprint $table) {
            if (! Schema::hasColumn('match_players', 'batting_position')) {
                $table->unsignedTinyInteger('batting_position')
                    ->nullable()
                    ->after('role');

                $table->index(
                    ['match_id', 'team_id', 'batting_position'],
                    'match_players_batting_position_idx'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('match_players', function (Blueprint $table) {
            if (Schema::hasColumn('match_players', 'batting_position')) {
                $table->dropIndex('match_players_batting_position_idx');
                $table->dropColumn('batting_position');
            }
        });
    }
};
