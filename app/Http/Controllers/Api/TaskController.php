<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function index()
    {
        try {
            $tasks = Task::all();
            return response()->json(['tasks' => $tasks], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch tasks.'], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_title' => 'required',
            'task_description' => 'required',
            'task_status' => 'required|integer',
            'task_startdate' => 'nullable|date',
            'task_enddate' => 'nullable|date',
        ]);
        $user_id = auth()->user()->id;
        $request->merge(['created_by' => $user_id]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error.', 'errors' => $validator->errors()], 400);
        }

        try {
            $task = Task::create($request->all());
            return response()->json(['task' => $task], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create task.'], 500);
        }
    }

    public function show($id)
    {
        try {
            $task = Task::findOrFail($id);
            return response()->json(['task' => $task], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Task not found.'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'task_title' => 'required',
            'task_description' => 'required',
            'task_status' => 'required|integer',
            'task_startdate' => 'nullable|date',
            'task_enddate' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error.', 'errors' => $validator->errors()], 400);
        }

        try {
            $user_id = auth()->user()->id;
            $request->merge(['updated_by' => $user_id]);
            $task = Task::findOrFail($id);
            $task->update($request->all());
            return response()->json(['task' => $task], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update task.'], 500);
        }
    }

    public function destroy($id)
    {
        try {

            $task = Task::findOrFail($id);
            $task->deleted_by = auth()->user()->id;
            $task->task_status = 0;
            $task->save(); //As we are using soft delete
            $task->delete();
            return response()->json(['message' => 'Task deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete task.'], 500);
        }
    }
}
