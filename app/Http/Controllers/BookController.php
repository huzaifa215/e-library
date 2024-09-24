<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Smalot\PdfParser\Parser;
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

        // book::create([
        //     'name' => $request->name,
        //     'category_id' => $request->category_id,
        //     'auther_id' => $request->auther_id,
        //     'publisher_id' => $request->publisher_id,
        //     'status' => 'Y',
        //     'pdf_path' => $path
        // ]);
        $fullPath = storage_path('app/public/' . $path);

        // Initialize the PDF parser
        $parser = new Parser();

        // Parse the PDF file and extract text
        try {
            $pdf = $parser->parseFile($fullPath);
            $text = $pdf->getText();
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to extract text from the PDF');
        }

        // Generate a summary of the extracted text
        // Option 1: Basic summarization (first 500 characters)
        $summary = substr($text, 0, 500) . '...';
       // $summary = $this->summarizeText($text);
        info('mymessage: ' . $summary);
          book::create([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'auther_id' => $request->auther_id,
            'publisher_id' => $request->publisher_id,
            'status' => 'Y',
            'pdf_path' => $path,
            'summary' => $summary 
        ]);
        return redirect()->route('books');
    }

    // generate the summary from the open AI
    public function summarizeText($text)
    {
        $client = new Client();
    
      try {
            // Retrieve the OpenAI API key from the environment file
            $apiKey = env('OPENAI_API_KEY');
    
            // Make an API call to OpenAI to summarize the text using the updated model
            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey, // Use the API key from .env
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo', // Use the latest model (or 'gpt-4' if you have access)
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a summarizer. Summarize the following text.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $text, // The text to summarize
                        ],
                    ],
                    'max_tokens' => 100,  // Adjust the token size as needed
                    'temperature' => 0.5, // Control randomness
                ],
                'verify' => false // Disable SSL certificate verification (if necessary for local dev)
            ]);
    
            // Decode the response body to get the summary
            $responseBody = json_decode($response->getBody()->getContents(), true);
            $summary = $responseBody['choices'][0]['message']['content'] ?? 'Summary not available';
    
            return $summary;
        } catch (RequestException $e) {
            // Handle any API request exceptions
            return 'Failed to summarize: ' . $e->getMessage();
        }
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
