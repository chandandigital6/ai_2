<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GrowyaKycDocument extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['expires_on' => 'date']; }
    public function submission() { return $this->belongsTo(GrowyaKycSubmission::class, 'submission_id'); }
}
