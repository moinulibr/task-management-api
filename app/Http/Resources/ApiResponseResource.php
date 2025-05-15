<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApiResponseResource extends JsonResource
{
    protected $message;
    protected $statusCode;
    protected $success;
    protected $error;

    public function __construct($resource, $message = '', $statusCode = 200, $success = true, $error = false)
    {
        parent::__construct($resource);
        $this->resource = $resource;
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
            'data' => $this->resource,
        ];
    }
}
