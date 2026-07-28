<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AiContentService;

class AiContentController extends Controller
{
    public function showForm()
    {
        return view('ai_form');
    }

    public function generate(Request $request, AiContentService $ai)
    {
        $validated = $request->validate([
            'title' => 'required|string|min:5|max:255',
            'type' => 'required|in:blog post,meta description,email subject line',
            'tone' => 'required|in:professional,casual,humorous',
        ]);

        try {
            $output = $ai->generateDraft(
                $validated['title'],
                $validated['type'],
                $validated['tone']
            );

            return view('ai_form', [
                'output' => $output,
                'title' => $validated['title'],
            ]);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'AI request failed: ' . $e->getMessage()]);
        }
    }
}
