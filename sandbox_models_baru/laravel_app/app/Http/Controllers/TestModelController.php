<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TestModelController extends Controller
{
    public function index()
    {
        return view('test');
    }

    public function analyze(Request $request)
    {
        $request->validate([
            'text' => 'required|string|min:15',
        ]);

        $ticket_id = 'TICKET-' . time();
        $text = $request->input('text');

        try {
            // Panggil NLP Service yang berjalan di port 8002
            $response = Http::timeout(10)->post('http://127.0.0.1:8002/analyze', [
                'ticket_id' => $ticket_id,
                'text' => $text,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return back()->with('result', $result)->withInput();
            } else {
                return back()->withErrors(['error' => 'Gagal terhubung ke NLP Service: ' . $response->body()])->withInput();
            }
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Koneksi ke NLP Service terputus. Pastikan service Python berjalan di port 8002. Detail: ' . $e->getMessage()])->withInput();
        }
    }
}
