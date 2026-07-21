<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BorrowRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LibrarianController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['librarian', 'super-admin'])) {
            abort(403);
        }

        $books = collect();
        $borrowedBooks = collect();
        $overdueBooks = collect();
        $fines = collect();
        $recentReturns = collect();
        $popularBooks = collect();
        $announcements = collect();
        
        if ($user->hasRole('librarian')) {
            $books = $school->books()->get();
            $borrowedBooks = $school->borrowRecords()->where('returned_at', null)->with(['book', 'student'])->get();
            $overdueBooks = $borrowedBooks->where('due_date', '<', now());
            
            // Sample fines
            $fines = collect([
                (object)['student' => 'John Doe', 'book' => 'Mathematics Textbook', 'amount' => 5.00, 'reason' => 'Late return', 'date' => now()->subDays(2)],
                (object)['student' => 'Jane Smith', 'book' => 'Science Guide', 'amount' => 3.50, 'reason' => 'Damaged book', 'date' => now()->subDays(5)],
            ]);
            
            // Sample recent returns
            $recentReturns = collect([
                (object)['book' => 'History Book', 'student' => 'Alice Johnson', 'returned_date' => now()->subHours(3), 'condition' => 'Good'],
                (object)['book' => 'English Novel', 'student' => 'Bob Wilson', 'returned_date' => now()->subHours(6), 'condition' => 'Fair'],
                (object)['book' => 'Physics Manual', 'student' => 'Carol Davis', 'returned_date' => now()->subDays(1), 'condition' => 'Good'],
            ]);
            
            // Sample popular books
            $popularBooks = collect([
                (object)['title' => 'Mathematics Textbook', 'author' => 'John Smith', 'borrow_count' => 15, 'available' => 3],
                (object)['title' => 'Science Guide', 'author' => 'Jane Doe', 'borrow_count' => 12, 'available' => 5],
                (object)['title' => 'History Book', 'author' => 'Bob Johnson', 'borrow_count' => 10, 'available' => 2],
            ]);
            
            // Sample announcements
            $announcements = collect([
                (object)['title' => 'Library Hours Change', 'message' => 'Library will close early on Friday at 4 PM', 'date' => now()->subDays(1), 'priority' => 'high'],
                (object)['title' => 'New Books Arrived', 'message' => '50 new books added to the science section', 'date' => now()->subDays(3), 'priority' => 'medium'],
            ]);
        } else {
            // For super admin, show sample data
            $books = collect([
                (object)['title' => 'Sample Book', 'author' => 'Sample Author', 'isbn' => '1234567890', 'status' => 'available'],
            ]);
            
            $borrowedBooks = collect([
                (object)['book' => (object)['title' => 'Sample Book'], 'student' => (object)['first_name' => 'Sample', 'last_name' => 'Student'], 'due_date' => now()->addDays(7)],
            ]);
            
            $overdueBooks = collect();
            $fines = collect([
                (object)['student' => 'Sample Student', 'book' => 'Sample Book', 'amount' => 5.00, 'reason' => 'Sample Fine', 'date' => now()],
            ]);
            
            $recentReturns = collect([
                (object)['book' => 'Sample Book', 'student' => 'Sample Student', 'returned_date' => now(), 'condition' => 'Good'],
            ]);
            
            $popularBooks = collect([
                (object)['title' => 'Popular Book', 'author' => 'Popular Author', 'borrow_count' => 10, 'available' => 5],
            ]);
            
            $announcements = collect([
                (object)['title' => 'Sample Announcement', 'message' => 'This is a sample announcement for testing', 'date' => now(), 'priority' => 'medium'],
            ]);
        }

        return view('portal.librarian.dashboard', [
            'school' => $school,
            'user' => $user,
            'books' => $books,
            'borrowedBooks' => $borrowedBooks,
            'overdueBooks' => $overdueBooks,
            'fines' => $fines,
            'recentReturns' => $recentReturns,
            'popularBooks' => $popularBooks,
            'announcements' => $announcements,
        ]);
    }

    public function profile()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['librarian', 'super-admin'])) {
            abort(403);
        }

        return view('portal.librarian.profile', [
            'school' => $school,
            'user' => $user,
        ]);
    }

    // Book Management Methods
    public function booksIndex()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['librarian', 'super-admin'])) {
            abort(403);
        }

        $books = $school->books()->with(['borrowRecords' => function($query) {
            $query->whereNull('returned_at');
        }])->paginate(15);

        return view('portal.librarian.books.index', [
            'school' => $school,
            'user' => $user,
            'books' => $books,
        ]);
    }

    public function booksCreate()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['librarian', 'super-admin'])) {
            abort(403);
        }

        return view('portal.librarian.books.create', [
            'school' => $school,
            'user' => $user,
        ]);
    }

    public function booksStore(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['librarian', 'super-admin'])) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'required|string|max:20|unique:books,isbn',
            'category' => 'required|string|max:100',
            'publisher' => 'nullable|string|max:255',
            'publication_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'total_copies' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $validated['school_id'] = $school->id;
        $validated['available_copies'] = $validated['total_copies'];

        $book = Book::create($validated);

        return redirect()->route('librarian.books.index')
            ->with('success', 'Book added successfully!');
    }

    public function booksShow(Book $book)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['librarian', 'super-admin']) || $book->school_id !== $school->id) {
            abort(403);
        }

        $book->load(['borrowRecords.student', 'borrowRecords' => function($query) {
            $query->orderBy('borrowed_at', 'desc');
        }]);

        return view('portal.librarian.books.show', [
            'school' => $school,
            'user' => $user,
            'book' => $book,
        ]);
    }

    public function booksEdit(Book $book)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['librarian', 'super-admin']) || $book->school_id !== $school->id) {
            abort(403);
        }

        return view('portal.librarian.books.edit', [
            'school' => $school,
            'user' => $user,
            'book' => $book,
        ]);
    }

    public function booksUpdate(Request $request, Book $book)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['librarian', 'super-admin']) || $book->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'required|string|max:20|unique:books,isbn,' . $book->id,
            'category' => 'required|string|max:100',
            'publisher' => 'nullable|string|max:255',
            'publication_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'total_copies' => 'required|integer|min:' . ($book->total_copies - $book->available_copies),
            'description' => 'nullable|string',
        ]);

        $borrowedCopies = $book->total_copies - $book->available_copies;
        $validated['available_copies'] = $validated['total_copies'] - $borrowedCopies;

        $book->update($validated);

        return redirect()->route('librarian.books.index')
            ->with('success', 'Book updated successfully!');
    }

    public function booksDestroy(Book $book)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['librarian', 'super-admin']) || $book->school_id !== $school->id) {
            abort(403);
        }

        if ($book->available_copies < $book->total_copies) {
            return redirect()->route('librarian.books.index')
                ->with('error', 'Cannot delete book with borrowed copies!');
        }

        $book->delete();

        return redirect()->route('librarian.books.index')
            ->with('success', 'Book deleted successfully!');
    }

    // Borrow Management Methods
    public function borrowIndex()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['librarian', 'super-admin'])) {
            abort(403);
        }

        $students = $school->students()->where('status', 'enrolled')->get();
        $availableBooks = $school->books()->where('available_copies', '>', 0)->get();
        $currentBorrows = $school->borrowRecords()->with(['student', 'book'])
            ->whereNull('returned_at')
            ->orderBy('due_date', 'asc')
            ->get();

        return view('portal.librarian.borrow.index', [
            'school' => $school,
            'user' => $user,
            'students' => $students,
            'availableBooks' => $availableBooks,
            'currentBorrows' => $currentBorrows,
        ]);
    }

    public function borrowStore(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['librarian', 'super-admin'])) {
            abort(403);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'book_id' => 'required|exists:books,id',
            'due_date' => 'required|date|after:today',
        ]);

        $student = $school->students()->findOrFail($validated['student_id']);
        $book = $school->books()->findOrFail($validated['book_id']);

        if ($book->available_copies <= 0) {
            return redirect()->back()
                ->with('error', 'Book is not available for borrowing!');
        }

        // Check if student already has this book
        $existingBorrow = $school->borrowRecords()
            ->where('student_id', $student->id)
            ->where('book_id', $book->id)
            ->whereNull('returned_at')
            ->first();

        if ($existingBorrow) {
            return redirect()->back()
                ->with('error', 'Student already has this book!');
        }

        $borrowRecord = BorrowRecord::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'book_id' => $book->id,
            'borrowed_at' => now(),
            'due_date' => $validated['due_date'],
        ]);

        $book->decrement('available_copies');

        return redirect()->route('librarian.borrow.index')
            ->with('success', 'Book issued successfully!');
    }

    public function returnBook(BorrowRecord $borrowRecord)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['librarian', 'super-admin']) || $borrowRecord->school_id !== $school->id) {
            abort(403);
        }

        if ($borrowRecord->returned_at) {
            return redirect()->back()
                ->with('error', 'Book has already been returned!');
        }

        $borrowRecord->update([
            'returned_at' => now(),
        ]);

        $borrowRecord->book->increment('available_copies');

        return redirect()->route('librarian.borrow.index')
            ->with('success', 'Book returned successfully!');
    }

    // Report Methods
    public function popularBooksReport()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['librarian', 'super-admin'])) {
            abort(403);
        }

        $popularBooks = $school->books()
            ->withCount(['borrowRecords' => function($query) {
                $query->whereNotNull('returned_at');
            }])
            ->orderBy('borrow_records_count', 'desc')
            ->limit(20)
            ->get();

        return view('portal.librarian.reports.popular', [
            'school' => $school,
            'user' => $user,
            'popularBooks' => $popularBooks,
        ]);
    }

    public function overdueBooksReport()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['librarian', 'super-admin'])) {
            abort(403);
        }

        $overdueBooks = $school->borrowRecords()
            ->with(['student', 'book'])
            ->whereNull('returned_at')
            ->where('due_date', '<', now())
            ->orderBy('due_date', 'asc')
            ->get();

        return view('portal.librarian.reports.overdue', [
            'school' => $school,
            'user' => $user,
            'overdueBooks' => $overdueBooks,
        ]);
    }
}
