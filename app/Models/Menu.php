<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = [
        'user_id',
        'image_id',
        'name',
        'price',
        'description',
        'category',
    ];
}