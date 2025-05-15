<?php

namespace App\Traits;

use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response;

trait HandlesExceptions
{
    public function handleWithTryCatch(Closure $callback)
    {
        try {
            return $callback();
        } catch (ModelNotFoundException $e) {
            return $this->error(null, 'Resource not found!', Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->error(null, 'Something went wrong!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
