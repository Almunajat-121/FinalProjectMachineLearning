<?php

namespace App\Jobs;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessNlpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;
    public int $tries = 2;

    public function __construct(public Ticket $ticket) {}

    public function handle(): void
    {
        try {
            $response = Http::timeout(25)->post(
                config('services.nlp.url') . '/predict',
                [
                    'ticket_id' => $this->ticket->id,
                    'text'      => $this->ticket->raw_text,
                    'lang_hint' => $this->ticket->lang_hint,
                ]
            );

            if ($response->successful()) {
                $data = $response->json();

                $this->ticket->update([
                    'category'       => $data['category'],
                    'urgency'        => $data['urgency'],
                    'category_score' => $data['confidence']['category_score'],
                    'urgency_score'  => $data['confidence']['urgency_score'],
                    'keywords'       => $data['keywords_extracted'] ?? [],
                    'status'         => 'OPEN',
                ]);

            } else {
                Log::warning('NLP response error', [
                    'ticket_id' => $this->ticket->id,
                    'status'    => $response->status(),
                ]);

                $this->ticket->update(['status' => 'OPEN']);
            }

        } catch (\Exception $e) {
            Log::error('NLP Job exception', [
                'ticket_id' => $this->ticket->id,
                'message'   => $e->getMessage(),
            ]);

            $this->ticket->update(['status' => 'OPEN']);
        }
    }
}