<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Seat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TablesController extends Controller
{
    /**
     * Display a listing of open tables.
     */
    public function index()
    {
        $tables = Table::getOpenTables();
        
        // Transform the tables to include additional info
        $tables = $tables->map(function ($table) {
            return [
                'id' => $table->id,
                'name' => $table->name,
                'gameType' => $table['game-type'],
                'maxSeats' => $table->max_seats,
                'occupiedSeats' => $table->occupiedSeatsCount(),
                'hostName' => $table->host->name,
                'created' => $table->created_at->diffForHumans(),
                'isFull' => $table->isFull(),
            ];
        });
        
        return Inertia::render('Home', [
            'tables' => $tables
        ]);
    }
    
    /**
     * Store a newly created table in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'max_seats' => 'required|integer|min:2|max:12',
            'game-type' => 'required|in:TexasHoldem',
        ]);
        
        // Add the host user ID
        $validated['host_id'] = Auth::id();
        
        // Create the table
        $table = Table::create($validated);
        
        // Create the associated seats
        for ($i = 1; $i <= $validated['max_seats']; $i++) {
            Seat::create([
                'position' => $i,
                'table_id' => $table->id,
            ]);
        }
        
        return redirect()->route('dashboard')->with('success', 'Table created successfully!');
    }

    /**
     * Delete a table.
     */
    public function destroy(Table $table)
    {
        // Ensure the user is the host of this table
        if (Auth::id() !== $table->host) {
            return back()->with('error', 'You are not authorized to delete this table.');
        }
        
        // Delete the table (seats will cascade delete due to foreign key constraint)
        $table->delete();
        
        return Inertia::location(route('dashboard')); //->with('success', 'Table deleted successfully!');
    }
    
    /**
     * Toggle the status of a table.
     */
    public function toggleStatus(Table $table)
    {
        // Ensure the user is the host of this table
        if (Auth::id() !== $table->host) {
            return back()->with('error', 'You are not authorized to modify this table.');
        }
        
        $table->status = $table->status === 'open' ? 'closed' : 'open';
        $table->save();
        
        return back()->with('success', 'Table status updated successfully!');
    }
    
    /**
     * Display the user's dashboard.
     */
    public function dashboard()
    {
        $userId = Auth::id();
        
        // Get tables where the user is the host
        $myTables = Table::where('host_id', $userId)
            ->with('host', 'seats')
            ->get()
            ->map(function ($table) {
                return [
                    'id' => $table->id,
                    'name' => $table->name,
                    'gameType' => $table['game-type'],
                    'maxSeats' => $table->max_seats,
                    'occupiedSeats' => $table->occupiedSeatsCount(),
                    'status' => $table->status,
                    'created' => $table->created_at->diffForHumans(),
                ];
            });
        
        // Get tables where the user is a participant (has a seat)
        $joinedTableIds = Seat::where('user_id', $userId)
            ->pluck('table_id')
            ->unique()
            ->toArray();
        
        $joinedTables = Table::whereIn('id', $joinedTableIds)
            ->where('host_id', '!=', $userId) // Exclude tables where user is host
            ->with('host')
            ->get()
            ->map(function ($table) {
                return [
                    'id' => $table->id,
                    'name' => $table->name,
                    'gameType' => $table['game-type'],
                    'hostName' => $table->host->name,
                ];
            });
        
        return Inertia::render('Dashboard', [
            'myTables' => $myTables,
            'joinedTables' => $joinedTables,
        ]);
    }
}
