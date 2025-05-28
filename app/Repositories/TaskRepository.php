<?php
namespace App\Repositories;

use App\Interfaces\TaskInterface;

class TaskRepository implements TaskInterface{

    public function getAllTasks(){
        return "from repository";
    }

    public function create(){

    }

    public function find(){

    }

    public function update(){

    }

    public function delete(){

    }
}