<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Venue extends Model {
    use HasFactory;
    protected $fillable=['organization_id','name','city','country','capacity','pitch_type','boundary_dimensions','notes','status'];
    protected function casts(): array { return ['capacity'=>'integer','boundary_dimensions'=>'array']; }
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function fixtures(): HasMany { return $this->hasMany(Fixture::class); }
}
