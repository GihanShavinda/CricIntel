<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->index(['innings_id', 'batter_id'], 'deliveries_innings_batter_idx');
            $table->index(['innings_id', 'bowler_id'], 'deliveries_innings_bowler_idx');
            $table->index(['batter_id', 'is_legal'], 'deliveries_batter_legal_idx');
            $table->index(['bowler_id', 'is_legal'], 'deliveries_bowler_legal_idx');
            $table->index(['over_id', 'is_legal'], 'deliveries_over_legal_idx');
            $table->index(['extra_type'], 'deliveries_extra_type_idx');
            $table->index(['delivery_timestamp'], 'deliveries_timestamp_idx');

            if (! Schema::hasColumn('deliveries', 'fielding_event_type')) {
                $table->string('fielding_event_type', 30)->nullable();
                $table->foreignId('fielding_player_id')
                    ->nullable()
                    ->constrained('players')
                    ->nullOnDelete();
                $table->index(
                    ['fielding_player_id', 'fielding_event_type'],
                    'deliveries_fielding_event_idx'
                );
            }
        });

        Schema::table('wickets', function (Blueprint $table) {
            $table->index(['dismissed_player_id', 'wicket_type'], 'wickets_dismissal_idx');
            $table->index(['bowler_id', 'wicket_type'], 'wickets_bowler_type_idx');
            $table->index(['fielder_id', 'wicket_type'], 'wickets_fielder_type_idx');
        });

        Schema::table('innings', function (Blueprint $table) {
            $table->index(['match_id', 'batting_team_id'], 'innings_match_batting_idx');
            $table->index(['batting_team_id', 'status'], 'innings_team_status_idx');
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->index(['organization_id', 'status'], 'matches_org_status_idx');
            $table->index(['winner_team_id', 'status'], 'matches_winner_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropIndex('deliveries_innings_batter_idx');
            $table->dropIndex('deliveries_innings_bowler_idx');
            $table->dropIndex('deliveries_batter_legal_idx');
            $table->dropIndex('deliveries_bowler_legal_idx');
            $table->dropIndex('deliveries_over_legal_idx');
            $table->dropIndex('deliveries_extra_type_idx');
            $table->dropIndex('deliveries_timestamp_idx');

            if (Schema::hasColumn('deliveries', 'fielding_player_id')) {
                $table->dropIndex('deliveries_fielding_event_idx');
                $table->dropConstrainedForeignId('fielding_player_id');
                $table->dropColumn('fielding_event_type');
            }
        });

        Schema::table('wickets', function (Blueprint $table) {
            $table->dropIndex('wickets_dismissal_idx');
            $table->dropIndex('wickets_bowler_type_idx');
            $table->dropIndex('wickets_fielder_type_idx');
        });

        Schema::table('innings', function (Blueprint $table) {
            $table->dropIndex('innings_match_batting_idx');
            $table->dropIndex('innings_team_status_idx');
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex('matches_org_status_idx');
            $table->dropIndex('matches_winner_status_idx');
        });
    }
};
