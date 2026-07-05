<?php

declare(strict_types=1);

/**
 * Incident-related configuration. Currently centralises the ARS
 * (Agence Régionale de Santé) notification endpoint used by
 * NotifyARSJob for grave/critique incidents per CDC §6.3.
 *
 * In production the ARS email is the per-region reporting inbox
 * provided by signaux-sanitaires.fr; we keep it env-driven so the
 * same image can ship to different regional pilots.
 */
return [

    'ars' => [
        /*
         * Master switch. When false the NotifyARSJob still runs but
         * skips the outbound email and only records the audit log
         * line — useful in local dev and CI where we never want a
         * real ARS email to leave the box.
         */
        'enabled' => (bool) env('INCIDENTS_ARS_ENABLED', false),

        /*
         * Recipient address. When null/empty the job behaves as if
         * disabled — this guards against shipping to prod without
         * the env being set.
         */
        'email' => env('INCIDENTS_ARS_EMAIL'),

        /*
         * Optional CC for internal traceability of every ARS
         * submission (typically the DPO mailbox).
         */
        'cc' => env('INCIDENTS_ARS_CC'),
    ],

];
