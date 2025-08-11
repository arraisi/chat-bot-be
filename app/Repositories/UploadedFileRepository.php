<?php

namespace App\Repositories;

use App\Models\UploadedFile;
use Illuminate\Database\Eloquent\Builder;

class UploadedFileRepository
{
    public function query(): Builder
    {
        return UploadedFile::query();
    }

    public function countAll(): int
    {
        return UploadedFile::count();
    }
}
