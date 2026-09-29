<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevelopmentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id','player_id','coach_id','title','weakness','objectives',
        'objective_ids','drill_ids','start_date','target_date','status','review_notes',
    ];

    protected function casts(): array
    {
        return [
            'objectives'=>'array','objective_ids'=>'array','drill_ids'=>'array',
            'start_date'=>'date','target_date'=>'date',
        ];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function player(): BelongsTo { return $this->belongsTo(Player::class); }
    public function coach(): BelongsTo { return $this->belongsTo(User::class,'coach_id'); }
}
