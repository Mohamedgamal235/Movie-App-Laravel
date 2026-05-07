<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function index()
    {
        $data = Movie::latest()->paginate(10);

        return view('index',compact('data'))->with('i',(request()->input('page',1)-1)*10);
    }

    public function create(){
        return view('create');
    }

    public function store(Request $request){
        $request->validate([
            'title' => 'required|unique:movies,title',
            'genre' => 'required',
            'rating' => 'required|numeric|between:0,10',
            'status' => 'required|in:Watching,Watched,Plan to Watch',
            'notes' => 'nullable|string',
            'poster_path' => 'nullable|string'
        ]);

        Movie::create([
            'title' => $request->title,
            'genre' => $request->genre,
            'rating' => $request->rating,
            'notes' => $request->notes,
            'status' => $request->status,
            'poster_path' => $request->poster_path
        ]);

        return redirect()->route('movie.index')
            ->with('success', 'Movie added successfully.');
    }

    public function show(Movie $movie){
        return view('show', compact('movie'));
    }

    public function edit(Movie $movie){
        return view('edit', compact('movie'));
    }

    public function update(Request $request, Movie $movie){
        $request->validate([
            'title' => 'required|unique:movies,title,' . $movie->id,
            'genre' => 'required',
            'rating' => 'required|numeric|between:0,10',
            'status' => 'required|in:Watching,Watched,Plan to Watch',
            'notes' => 'nullable|string',
            'poster_path' => 'nullable|string'
        ]);

        $movie->update([
            'title' => $request->title,
            'genre' => $request->genre,
            'rating' => $request->rating,
            'notes' => $request->notes,
            'status' => $request->status,
            'poster_path' => $request->poster_path
        ]);

        return redirect()->route('movie.index')->with('success', 'Movie data has been updated successfully.');
    }
    
    public function destroy(Movie $movie){
        $movie->delete();

        return redirect()->route('movie.index')->with('success', 'Movie data deleted successfully.');
    }

}
