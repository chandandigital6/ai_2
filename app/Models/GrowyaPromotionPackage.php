<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GrowyaPromotionPackage extends Model {protected $guarded=['id'];protected function casts():array{return ['is_active'=>'boolean','price'=>'decimal:2'];}}
