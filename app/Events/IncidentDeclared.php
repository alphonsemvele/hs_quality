<?php

namespace App\Events;

use App\Models\Incident;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentDeclared implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Incident $incident) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("structure.{$this->incident->structure_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'incident_id' => $this->incident->id,
            'categorie' => $this->incident->categorie->value,
            'gravite' => $this->incident->gravite->value,
            'occurred_at' => $this->incident->occurred_at?->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'incident.declared';
    }
}
