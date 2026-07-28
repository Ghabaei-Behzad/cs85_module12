<?php

namespace Tests\Feature;

use App\Services\AiContentService;
use Tests\TestCase;

class DraftGenerationTest extends TestCase
{
    public function test_generate_returns_the_services_output(): void
    {
        // Replace the real service with a fake so no API call is made.
        $this->mock(AiContentService::class, function ($mock) {
            $mock->shouldReceive('generateDraft')
                ->once()
                ->andReturn('A generated draft.');
        });

        $this->post(route('ai.generate'), [
            'title' => 'A meaningful test title',
            'type'  => 'blog post',
            'tone'  => 'professional',
        ])
        ->assertOk()->assertSee('A generated draft.');
    }

    public function test_title_must_be_at_least_five_characters(): void
    {
        $this->post(route('ai.generate'), [
            'title' => 'AI',
            'type' => 'blog post',
            'tone' => 'professional',
        ])
        ->assertSessionHasErrors('title');
    }
}