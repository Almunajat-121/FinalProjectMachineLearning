<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessNlpJob;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    // Mahasiswa submit keluhan
    public function store(Request $request)
    {
        $request->validate([
            'text'      => 'required|string|min:5|max:1000',
            'lang_hint' => 'sometimes|in:id,auto',
        ]);

        // Simpan tiket ke DB — cepat, tidak tunggu NLP
        $ticket = Ticket::create([
            'raw_text'  => $request->text,
            'lang_hint' => $request->input('lang_hint', 'id'),
            'status'    => 'PENDING_NLP',
        ]);

        // Lempar ke queue — tidak blocking
        ProcessNlpJob::dispatch($ticket);

        // Langsung return 202 ke browser
        return response()->json([
            'ticket_id' => $ticket->id,
            'status'    => 'PENDING_NLP',
            'message'   => 'Laporan diterima. Sistem sedang menganalisis...',
        ], 202);
    }

    // Frontend polling status tiket
    public function status(string $id)
    {
        $ticket = Ticket::select([
            'id',
            'status',
            'category',
            'urgency',
            'category_score',
            'urgency_score',
            'keywords',
            'created_at',
        ])->findOrFail($id);

        return response()->json($ticket);
    }

    // Admin — lazy load daftar tiket
    public function index(Request $request)
    {
        $tickets = Ticket::orderByDesc('cursor_id')
            ->when($request->cursor, fn($q) =>
                $q->where('cursor_id', '<', $request->cursor)
            )
            ->when($request->status, fn($q) =>
                $q->where('status', $request->status)
            )
            ->when($request->urgency, fn($q) =>
                $q->where('urgency', $request->urgency)
            )
            ->when($request->category, fn($q) =>
                $q->where('category', $request->category)
            )
            ->limit(20)
            ->get();

        return response()->json([
            'data'        => $tickets,
            'next_cursor' => $tickets->last()?->cursor_id,
            'has_more'    => $tickets->count() === 20,
        ]);
        
        }
        // Admin — update status tiket
    public function update(Request $request, string $id)
    {
        $request->validate([
            'status'     => 'sometimes|in:OPEN,IN_PROGRESS,RESOLVED,CLOSED',
            'admin_note' => 'sometimes|string|max:1000',
        ]);

        $ticket = Ticket::findOrFail($id);

        $data = $request->only(['status', 'admin_note']);

        if (isset($data['status']) && $data['status'] === 'RESOLVED') {
            $data['resolved_at'] = now();
        }

        $ticket->update($data);

        return response()->json($ticket);
    }
}