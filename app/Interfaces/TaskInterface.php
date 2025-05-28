<?php
namespace App\Interfaces;

interface TaskInterface{
    public function getAllTasks();

    public function create();

    public function find();

    public function update();

    public function delete();
}