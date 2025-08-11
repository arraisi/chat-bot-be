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

    public function create(array $data)
    {
        return UploadedFile::create($data);
    }

    public function findById($id)
    {
        return UploadedFile::find($id);
    }

    public function update($id, array $data)
    {
        $uploadedFile = UploadedFile::find($id);
        if ($uploadedFile) {
            $uploadedFile->update($data);
            return $uploadedFile->fresh();
        }
        return null;
    }

    public function delete($id)
    {
        return UploadedFile::destroy($id);
    }
}
