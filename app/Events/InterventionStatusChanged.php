<?php

namespace App\Events;

use App\Enums\InterventionStatus;
use App\Models\Intervention;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InterventionStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Intervention $intervention,
        public readonly InterventionStatus $newStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("structure.{$this->intervention->structure_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'intervention_id' => $this->intervention->id,
            'status' => $this->newStatus->value,
            'updated_at' => $this->intervention->updated_at?->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'intervention.status.changed';
    }
}
