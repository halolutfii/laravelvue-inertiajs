<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TodoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Inertia::render('Home', [
            'todos' => Todo::all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate(['title' => 'required']);
        Todo::create($request->all());
        return redirect()->route('todos.index');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Todo  $todo
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Todo $todo)
    {
        $request->validate(['title' => 'required']);
        $todo->update(['title' => $request->title]);
        return redirect()->route('todos.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Todo  $todo
     * @return \Illuminate\Http\Response
     */
    public function destroy(Todo $todo)
    {
        $todo->delete();
        return redirect()->route('todos.index');
    }

    public function batchUpdateDelete(Request $request)
    {
        // Batch Update
        if (!empty($request->updates)) {
            foreach ($request->updates as $update) {
                Todo::where('id', $update['id'])->update(['title' => $update['title']]);
            }
        }

        // Batch Delete
        if (!empty($request->deletes)) {
            Todo::whereIn('id', $request->deletes)->delete();
        }

        return redirect()->route('todos.index');
    }
}
