<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessOwnershipTransfer extends Model
{
    protected $guarded = ['id'];

    public function business() { return $this->belongsTo(Business::class); }
    public function previousOwner() { return $this->belongsTo(User::class, 'previous_owner_id'); }
    public function newOwner() { return $this->belongsTo(User::class, 'new_owner_id'); }
    public function performedBy() { return $this->belongsTo(User::class, 'performed_by'); }
}
