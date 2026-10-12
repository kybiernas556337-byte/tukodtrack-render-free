<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Supplier extends Model { protected $guarded = []; protected $casts = ['is_active' => 'boolean', 'is_sample' => 'boolean']; }
