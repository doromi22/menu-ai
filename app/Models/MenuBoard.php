<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuBoard extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'template_type',
        'paper_size',
        'layout_data',
        'pdf_path',
    ];

    protected $casts = [
        'layout_data' => 'array',
    ];
}