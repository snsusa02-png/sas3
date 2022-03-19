<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Task;

class TaskController extends Controller
{
    public function store(Request $request)
    {

        $task = new Task();

        $task->srcsysobjid = $request->srcsysobjid;
        $task->srcobjid = $request->srcobjid;

        $task->descript = $request->text;
        $task->name = mb_substr($task->descript, 0, 160);

        $task->plnbegdt = $request->start_date;
        $task->day_duration = $request->duration;
        $plnenddt = date('Y-m-d H:i:s', strtotime($task->plnbegdt . ' +' . $task->day_duration . ' day'));
        $task->plnenddt = $plnenddt;

        $task->progress = $request->has("progress") ? $request->progress : 0;
        $task->parid = ($request->parent == 0) ? null : $request->parent;
        $task->sortorder = Task::max("sortorder") + 1;

        $task->save();

        return response()->json([
            "action" => "inserted",
            "tid" => $task->id
        ]);
    }

    public function update($id, Request $request)
    {
        $task = Task::find($id);

        if (isset($task)) {

            $task->descript = $request->text;
            $task->name = mb_substr($task->descript, 0, 160);
            $task->plnbegdt = $request->start_date;
            $task->day_duration = $request->duration;
            $plnenddt = date('Y-m-d H:i:s', strtotime($task->plnbegdt . ' +' . $task->day_duration . ' day'));
            $task->plnenddt = $plnenddt;

            $task->progress = $request->has("progress") ? $request->progress : 0;
            $task->priority = $request->has("priority") ? $request->priority : 2;
            $task->parid = ($request->parent == 0) ? null : $request->parent;

            $task->save();

            //when a user reorders tasks, task orders must be updated:
            if ($request->has("target")) {
                $this->updateOrder($id, $request->target);
            }

            return response()->json([
                "action" => "updated",
                //"report" => $task->plnbegdt . ' ' . $plnenddt,
            ]);
        }
        return response()->json([]);
    }

    public function destroy($id)
    {
        $task = Task::find($id);
        $task->delete();

        return response()->json([
            "action" => "deleted"
        ]);
    }

    private function updateOrder($taskId, $target)
    {
        $nextTask = false;
        $targetId = $target;

        if (strpos($target, "next:") === 0) {
            $targetId = substr($target, strlen("next:"));
            $nextTask = true;
        }

        if ($targetId == "null")
            return;

        $targetOrder = Task::find($targetId)->sortorder;
        if ($nextTask)
            $targetOrder++;

        Task::where("sortorder", ">=", $targetOrder)->increment("sortorder");

        $updatedTask = Task::find($taskId);
        $updatedTask->sortorder = $targetOrder;
        $updatedTask->save();
    }
}