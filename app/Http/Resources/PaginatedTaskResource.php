<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PaginatedTaskResource extends ResourceCollection
{
    protected $message;
    protected $statusCode;
    protected $success;
    protected $error;

    public function __construct($resource, $message = 'Tasks fetched successfully.', $statusCode = 200, $success = true, $error = false)
    {
        parent::__construct($resource);
        $this->message = $message;
        $this->statusCode = $statusCode;
        $this->success = $success;
        $this->error = $error;
    }

    public function toArray($request)
    {
        return [
            'message' => $this->message,
            'success' => $this->success,
            'error' => $this->error,
            'statusCode' => $this->statusCode,

            // Default Laravel pagination structure
            'data' => TaskResource::collection($this->collection),
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $this->currentPage(),
                'from' => $this->firstItem(),
                'last_page' => $this->lastPage(),
                'links' => $this->linkCollection()->toArray(),
                'path' => $this->path(),
                'per_page' => $this->perPage(),
                'to' => $this->lastItem(),
                'total' => $this->total(),
            ],
        ];
    }
}
