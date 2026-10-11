<?php

declare(strict_types=1);

use App\Models\Farm;
use App\Models\User;
use App\Models\VoiceAssistanceRequest;

test('a farmer records manual and voice-led entries and sees profitability', function () {
    $farmer = User::factory()->create();
    $farm = Farm::factory()->for($farmer, 'owner')->create();
    $expenseVoice = VoiceAssistanceRequest::factory()->for($farmer)->forFarm($farm)->create([
        'purpose' => 'ledger',
        'transcript' => 'I bought two bags of fertiliser for ninety thousand naira.',
    ]);
    $harvestVoice = VoiceAssistanceRequest::factory()->for($farmer)->forFarm($farm)->create([
        'purpose' => 'ledger',
        'transcript' => 'We harvested twenty bags of maize.',
    ]);

    foreach ([
        ['entry_type' => 'expense', 'category' => 'inputs', 'description' => 'Two bags of fertiliser', 'amount' => 90000, 'source' => 'voice', 'voice_request_id' => $expenseVoice->uuid],
        ['entry_type' => 'expense', 'category' => 'labour', 'description' => 'Harvest labour', 'amount' => 30000, 'source' => 'manual'],
        ['entry_type' => 'income', 'category' => 'produce_sale', 'description' => 'Maize sale', 'amount' => 200000, 'source' => 'manual'],
        ['entry_type' => 'harvest', 'category' => 'maize', 'description' => 'Maize harvested', 'amount' => 0, 'quantity' => 20, 'unit' => '100kg bag', 'source' => 'voice', 'voice_request_id' => $harvestVoice->uuid],
    ] as $entry) {
        $this->actingAs($farmer)
            ->postJson('/api/v1/farms/'.$farm->uuid.'/ledger-entries', $entry + ['occurred_on' => today()->toDateString()])
            ->assertCreated();
    }

    $this->actingAs($farmer)
        ->getJson('/api/v1/farms/'.$farm->uuid.'/profitability')
        ->assertSuccessful()
        ->assertJsonPath('data.income', 200000)
        ->assertJsonPath('data.expenses', 120000)
        ->assertJsonPath('data.gross_profit', 80000)
        ->assertJsonPath('data.gross_margin_percent', 40)
        ->assertJsonPath('data.harvest_quantity', 20)
        ->assertJsonPath('data.is_complete', true);
});

test('farm ledger and profitability remain private to the farm owner', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create();

    $this->actingAs($otherUser)->getJson('/api/v1/farms/'.$farm->uuid.'/ledger-entries')->assertForbidden();
    $this->actingAs($otherUser)->getJson('/api/v1/farms/'.$farm->uuid.'/profitability')->assertNotFound();
});

test('voice-led entries require a completed ledger voice note from the same farm', function () {
    $farmer = User::factory()->create();
    $farm = Farm::factory()->for($farmer, 'owner')->create();
    $agronomyVoice = VoiceAssistanceRequest::factory()->for($farmer)->forFarm($farm)->create([
        'purpose' => 'agronomy',
    ]);
    $payload = [
        'entry_type' => 'expense',
        'category' => 'labour',
        'description' => 'Harvest labour',
        'amount' => 30000,
        'occurred_on' => today()->toDateString(),
        'source' => 'voice',
    ];

    $this->actingAs($farmer)
        ->postJson('/api/v1/farms/'.$farm->uuid.'/ledger-entries', $payload)
        ->assertUnprocessable()
        ->assertInvalid('voice_request_id');

    $this->actingAs($farmer)
        ->postJson('/api/v1/farms/'.$farm->uuid.'/ledger-entries', $payload + ['voice_request_id' => $agronomyVoice->uuid])
        ->assertUnprocessable()
        ->assertInvalid('voice_request_id');
});
