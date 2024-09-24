@extends('layouts.app')
@section('content')
    <div id="admin-content">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <h2 class="admin-heading">All Books</h2>
                </div>
                <div class="offset-md-7 col-md-2">
                    @can('admin')
                        <a class="add-new" href="{{ route('book.create') }}">Add Book</a>
                    @endcan
                </div>
            </div>
            <h4>Seacrh Book: Author , Publisher , Category and Book Name</h4>
            <div class="form-group">
                <form action="{{ route('books') }}">
                    <div class="d-flex col-md-8 ">
                        <input type="text" name="search" id="search" class="form-control mr-4 "
                            placeholder="Enter book name or keyword" value="{{ request('search') }}">
                        <button class="btn btn-primary">Search</button>
                    </div>
                </form>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="message"></div>
                    <table class="content-table">
                        <thead>
                            <th>S.No</th>
                            <th>Book Name</th>
                            <th>Category</th>
                            <th>Author</th>
                            <th>Publisher</th>
                            <th>Summary</th>
                            <th>Status</th>
                            <th>View</th>
                            @can('admin')
                                <th>Edit</th>
                                <th>Delete</th>
                            @endcan
                        </thead>
                        <tbody>
                            @forelse ($books as $book)
                                <tr>
                                    <td class="id">{{ $book->id }}</td>
                                    <td>{{ $book->name }}</td>
                                    <td>{{ $book->category->name }}</td>
                                    <td>{{ $book->auther->name }}</td>
                                    <td>{{ $book->publisher->name }}</td>
                                    <td>{{ $book->summary }}</td>
                                    <td>
                                        @if ($book->status == 'Y')
                                            <span class='badge badge-success'>Available</span>
                                        @else
                                            <span class='badge badge-danger'>Issued</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a target="_blank" href="{{ Storage::url($book->pdf_path) }}"
                                            class="btn btn-info">View</a>
                                    </td>
                                    @can('admin')
                                        <td class="edit">
                                            <a href="{{ route('book.edit', $book) }}" class="btn btn-success">Edit</a>
                                        </td>
                                        <td class="delete">
                                            <form action="{{ route('book.destroy', $book) }}" method="post"
                                                class="form-hidden">
                                                <button class="btn btn-danger delete-book">Delete</button>
                                                @csrf
                                            </form>
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9">No Books Found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $books->links('vendor/pagination/bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
@endsection
