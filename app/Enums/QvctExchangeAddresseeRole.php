<?php

namespace App\Enums;

/**
 * Role the requester wants to talk to. Spec: CDC §M3 line "Secure
 * exchange request tool with manager or référent RH". `Manager` covers
 * coordinateur + dirigeant; `RH` is the référent RH role.
 */
enum QvctExchangeAddresseeRole: string
{
    case Rh = 'rh';
    case Manager = 'manager';

    public function label(): string
    {
        return match ($this) {
            self::Rh => 'Référent RH',
            self::Manager => 'Coordinateur / responsable',
        };
    }

    /**
     * Permission an addressee user must hold to see incoming requests
     * of this kind. Dedicated triage perms (granted only to the right
     * role) keep the two queues unambiguously separated — qvct.alert.receive
     * and qvct.view.team_aggregates are too widely shared to use here.
     */
    public function addresseePermission(): string
    {
        return match ($this) {
            self::Rh => 'qvct.exchange.rh_triage',
            self::Manager => 'qvct.exchange.manager_triage',
        };
    }
}
