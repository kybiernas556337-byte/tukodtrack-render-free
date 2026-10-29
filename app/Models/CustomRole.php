<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CustomRole extends Model {
    protected $fillable=['slug','name','base_role'];
}
