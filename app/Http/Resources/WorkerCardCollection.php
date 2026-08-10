<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class WorkerCardCollection extends ResourceCollection
{
    /**
     * Resource class used for each item.
     */
    public $collects = WorkerCardResource::class;

    /**
     * Transform the paginated collection.
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,

            'workers' => $this->collection,

            'pagination' => [
                'current_page' =>
                    $this->resource->currentPage(),

                'last_page' =>
                    $this->resource->lastPage(),

                'per_page' =>
                    $this->resource->perPage(),

                'total' =>
                    $this->resource->total(),

                'from' =>
                    $this->resource->firstItem(),

                'to' =>
                    $this->resource->lastItem(),

                'has_more_pages' =>
                    $this->resource->hasMorePages(),
            ],
        ];
    }

    /**
     * Prevent Laravel from wrapping this response in "data".
     */
    public function with(Request $request): array
    {
        return [];
    }
}
