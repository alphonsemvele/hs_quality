<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_family_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('structure_id')->constrained('structures')->cascadeOnDelete();
            $table->foreignUuid('beneficiary_id')->constrained('beneficiaries')->cascadeOnDelete();

            // SHA-256 of the plaintext token. Plain token returned once at
            // issuance and never stored — same pattern as Laravel Sanctum.
            $table->string('token_hash', 64)->unique();

            // Array of FamilyTokenScope values; validated on every portal hit.
            $table->jsonb('scope');

            // Name of the family member this token was issued to (audit trail).
            $table->string('issued_to_name');

            // Who issued the token.
            $table->foreignId('issued_by_user_id')->constrained('users')->cascadeOnDelete();

            $table->timestamp('expires_at');
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->index(['structure_id', 'beneficiary_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_family_tokens');
    }
};
