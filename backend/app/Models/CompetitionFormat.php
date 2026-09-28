<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class CompetitionFormat extends Model {
    use HasFactory;
    protected $fillable=['name','code','description','default_overs','is_custom','status'];
    protected function casts(): array { return ['default_overs'=>'integer','is_custom'=>'boolean']; }
}
