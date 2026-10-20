<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GrowyaKycSubmission extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['reviewed_at' => 'datetime', 'reverify_by' => 'date']; }
    public function business() { return $this->belongsTo(Business::class); }
    public function documents() { return $this->hasMany(GrowyaKycDocument::class, 'submission_id'); }
    public function events() { return $this->hasMany(GrowyaKycEvent::class, 'submission_id'); }
    public function submitter() { return $this->belongsTo(User::class, 'submitted_by'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
}
