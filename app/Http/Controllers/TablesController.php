<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Seat;
use App\Events\TableStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
                'occupiedSeats' => $table->occupiedSeats->count(),
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
                    'occupiedSeats' => $table->occupiedSeats->count(),
                    'status' => $table->status,
                    'created' => $table->created_at->diffForHumans(),
                ];
            });
        
        // Get tables where the user is a participant (has a seat)
        $joinedTableIds =  Seat::whereHas('player', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })
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

    /**
     * Show the seats for a specific table.
     */
    #TODO load the current user player model too - to retrieve cards after reloading - also retrieve rounds from hand?
    public function show(Table $table) {
        // Ensure we load the host and seats relationships
        $table->load('host', 'seats');
        
        // Transform the table data for the frontend
        $tableData = [
            'id' => $table->id,
            'name' => $table->name,
            'gameType' => $table['game-type'],
            'maxSeats' => $table->max_seats,
            'status' => $table->status,
            'hostName' => $table->host->name,
            'hostId' => $table->host_id,
            'created' => $table->created_at->diffForHumans(),
        ];
        
        // Get all seats with their players (if occupied)
        $seats = $table->seats->sortBy('position')->map(function ($seat) {
            if ($seat->player) {
                $isUser = !!$seat->player->user_id;
                $isGuest = !!$seat->player->guest_session;
            } else {
                $isUser = false;
                $isGuest = false;
            }
    
            return [
                'id' => $seat->id,
                'position' => $seat->position,
                'isOccupied' => !!$seat->player,
                'userName' => $isUser ? optional($seat->player->user)->name : optional($seat->player)->guest_name,
                'userType' => $isUser ? 'user' : ($isGuest ? 'guest' : null),
            ];
        })->values();
        
        // Check if the current user already has a seat at this table
        $currentUserSeat = null;
        if (Auth::check()) {
            $userSeat = $table->seats()
                ->whereHas('player', function($q) 
                {$q->where('user_id', Auth::id());})->first();
            if ($userSeat) {
                $currentUserSeat = [
                    'id' => $userSeat->id,
                    'position' => $userSeat->position
                ];
            }
        } else {
            $guestSession = session()->getID();
            if ($guestSession) {
                $guestSeat = $table->seats()
                    ->whereHas('player', function($q) use ($guestSession) // PHP closures don't see outer scope by default
                    {$q->where('guest_session', $guestSession);})->first();
                if ($guestSeat) {
                    $currentUserSeat = [
                        'id' => $guestSeat->id,
                        'position' => $guestSeat->position,
                    ];
                }
            }
        }
        
        return Inertia::render('Tables/Show', [
            'table' => $tableData,
            'seats' => $seats,
            'currentUserSeat' => $currentUserSeat,
            'isHost' => Auth::check() && Auth::id() === $table->host_id,
        ]);
    }

    /**
     * Toggle the status of a table.
     */
    public function toggleStatus(Table $table)
    {
        // Ensure the user is the host of this table
        if (Auth::id() !== $table->host_id) {
            return back()->with('error', 'You are not authorized to modify this table.');
        }
        
        DB::transaction(function () use ($table) {
            $table->status = $table->status === 'open' ? 'closed' : 'open';
            $table->save();
        
            broadcast(new TableStatusUpdated($table->id, $table->status));
        });

        return response()->json([
            'status' => 'ok',
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
        if (Auth::id() !== $table->host_id) {
            return back()->with('error', 'You are not authorized to delete this table.');
        }
        
        // Delete the table (seats will cascade delete due to foreign key constraint)
        $table->delete();
        
        return back()->with('success', 'Table deleted successfully!');
    }
}
