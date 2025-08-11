<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadedFile extends Model
{
    protected $table = 'uploaded_files';

    protected $fillable = [
        'filename',
        'path',
        'size',
        'authority',
        'category',
    ];
}
