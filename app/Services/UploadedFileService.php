<?php

namespace App\Services;

use App\Repositories\UploadedFileRepository;
use Illuminate\Http\Request;

class UploadedFileService
{
    protected $repo;

    protected $columns = [
        'id',
        'filename',
        'path',
        'size',
        'authority',
        'category',
        'created_at',
        'updated_at'
    ];

    public function __construct(UploadedFileRepository $repo)
    {
        $this->repo = $repo;
    }

    public function datatables(Request $request): array
    {
        $recordsTotal = $this->repo->countAll();

        // Start with a fresh query for filtering
        $query = $this->repo->query();

        // Filter by authority (case-insensitive)
        $authority = $request->input('authority');
        if ($authority) {
            $query->where('authority', 'ilike', $authority);
        }

        // Filter by category (case-insensitive)
        $category = $request->input('category');
        if ($category) {
            $query->where('category', 'ilike', $category);
        }

        // Search (case-insensitive)
        $searchValue = $request->input('search.value');
        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('filename', 'ilike', "%{$searchValue}%")
                  ->orWhere('path', 'ilike', "%{$searchValue}%")
                  ->orWhere('authority', 'ilike', "%{$searchValue}%")
                  ->orWhere('category', 'ilike', "%{$searchValue}%");
            });
        }

        // Get filtered count before adding pagination
        $recordsFiltered = $query->count();

        // Sorting - fix column index handling
        $orderColIndex = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');

        // Ensure column index is valid
        if (is_numeric($orderColIndex) && isset($this->columns[$orderColIndex])) {
            $orderColumn = $this->columns[$orderColIndex];
        } else {
            $orderColumn = 'id'; // Default fallback
        }

        $query->orderBy($orderColumn, $orderDir);

        // Pagination
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $items = $length === -1 ? $query->get() : $query->skip($start)->take($length)->get();

        return [
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $items,
        ];
    }

    public function create(array $data)
    {
        return $this->repo->create($data);
    }

    public function findById($id)
    {
        return $this->repo->findById($id);
    }

    public function update($id, array $data)
    {
        return $this->repo->update($id, $data);
    }

    public function delete($id)
    {
        return $this->repo->delete($id);
    }
}
