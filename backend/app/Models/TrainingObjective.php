<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingObjective extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id','player_id','created_by','title','weakness','statistic_scope',
        'metric_key','observed_value','target_value','source_context','status','target_date','notes',
    ];

    protected function casts(): array
    {
        return [
            'observed_value'=>'float','target_value'=>'float',
            'source_context'=>'array','target_date'=>'date',
        ];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function player(): BelongsTo { return $this->belongsTo(Player::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class,'created_by'); }
}
