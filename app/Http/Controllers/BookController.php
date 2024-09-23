<?php

namespace App\Http\Controllers;

use App\Models\book;
use App\Http\Requests\StorebookRequest;
use App\Http\Requests\UpdatebookRequest;
use App\Models\auther;
use App\Models\category;
use App\Models\publisher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $searchQuery = '%' . request()->query('search') . '%';

        $books = book::where('name', 'like', $searchQuery)
            ->orWhereHas('auther', function (Builder $query) use ($searchQuery) {
                $query->where('name', 'like', $searchQuery);
            })
            ->orWhereHas('category', function (Builder $query) use ($searchQuery) {
                $query->where('name', 'like', $searchQuery);
            })
            ->orWhereHas('publisher', function (Builder $query) use ($searchQuery) {
                $query->where('name', 'like', $searchQuery);
            })
            ->paginate(5)->withQueryString();

        return view('book.index', [
            'books' => $books,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('book.create', [
            'authors' => auther::latest()->get(),
            'publishers' => publisher::latest()->get(),
            'categories' => category::latest()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StorebookRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorebookRequest $request)
    {
        $path = $request->file('book_pdf')->store('books', 'public');

        book::create([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'auther_id' => $request->auther_id,
            'publisher_id' => $request->publisher_id,
            'status' => 'Y',
            'pdf_path' => $path
        ]);

        return redirect()->route('books');
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\book  $book
     * @return \Illuminate\Http\Response
     */
    public function edit(book $book)
    {
        return view('book.edit', [
            'authors' => auther::latest()->get(),
            'publishers' => publisher::latest()->get(),
            'categories' => category::latest()->get(),
            'book' => $book
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatebookRequest  $request
     * @param  \App\Models\book  $book
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatebookRequest $request, $id)
    {
        $book = book::find($id);
        $book->name = $request->name;
        $book->auther_id = $request->author_id;
        $book->category_id = $request->category_id;
        $book->publisher_id = $request->publisher_id;

        if ($request->hasFile('book_pdf')) {
            Storage::disk('public')->delete($book->pdf_path);
            $path = $request->file('book_pdf')->store('books', 'public');
            $book->pdf_path = $path;
        }

        $book->save();
        return redirect()->route('books');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\book  $book
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        $book = book::findOrFail($id);
        Storage::disk('public')->delete($book->pdf_path);
        $book->delete();
        return redirect()->route('books');
    }
}
