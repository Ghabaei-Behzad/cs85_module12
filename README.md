Behzad Ghabaei <br>
CS 85 PHP programming <br>
Module 12 Assignment12A <br>
Integrating OpenAI <br>
Instructor Seno <br>
7/28/2026 <br>
 
 ## Set Up Instructions
 ### 1. ( Windows )
 Start Laravel Herd application > in command prompt terminal navigate to Herd folder and enter the command "\Herd>laravel new blog-ai" <br>
 Update now? no <br>
 Starter Kit? None <br>
 Testing framework? Pest <br>
 Laravel Boost AI? No <br>
 Installing...
 Which database? SQLite <br>
 run npm install? No <br>
 cd blog-ai > Open VS code with code . > Navigate to http://blog-ai.test/ai-form > We want to create 1 new folder and 3 new files. 
 1. create a folder under app called "Services" with mkdir Services, because your making (app/Services/AiContentService.php) <br>
 2. create AiContentController.php inside of app/Http/Controllers, ( app/Http/Controllers/AiContentController.php ) <br>
 3. create ai.form.blade in views ( resources/views/ai_form.blade.php ) <br>
 
 4. Add the OpenAI API Key to the .env file (version control)
```
OPENAI_API_KEY=sk-proj-xxxx-your_openai_api_key_is_pasted_here-xxxx
OPENAI_API_URL=https://api.openai.com/v1
OPENAI_MODEL=gpt-4o-mini
```
5. Update config/services.php with this associative array appended to the existing code (just like this)
```
'openai' => [
    'key'   => env('OPENAI_API_KEY'),
    'url'   => env('OPENAI_API_URL', 'https://api.openai.com/v1'),
    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
],
```
6. Make sure the variables from .env and services.php are matching
   OPENAI_API_KEY, OPENAI_API_URL, OPENAI_MODEL (the sk-proj-key will not be shown in services.php)
7. Define the Routes, routes in routes/web.php
```
use App\Http\Controllers\AiContentController;

Route::get('/ai-form', [AiContentController::class, 'showForm'])->name('ai.form');
Route::post('/ai-generate', [AiContentController::class, 'generate'])->name('ai.generate');
```
8. Add the controller as "app/Http/Controllers/AiContentController.php" 
```
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
            'type'  => 'required|in:blog post,meta description,email subject line',
            'tone'  => 'required|in:professional,casual,humorous',
        ]);

        try {
            $output = $ai->generateDraft(
                $validated['title'],
                $validated['type'],
                $validated['tone'],
            );

            return view('ai_form', [
                'output' => $output,
                'title'  => $validated['title'],
            ]);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'AI request failed: ' . $e->getMessage()]);
        }
    }
}
``` 
9. Create the blade file as resources/views/ai_form.blade.php
```
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Content Generator</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto mt-6 max-w-2xl px-4">
    <h1 class="text-2xl font-bold mb-4">AI Content Generator</h1>

    <form method="POST" action="{{ route('ai.generate') }}">
        @csrf

        <label for="title" class="block font-medium">Title or topic:</label>
        <input type="text" name="title" id="title"
               value="{{ old('title', $title ?? '') }}"
               class="border w-full p-2 mt-1" required>
        @error('title') <div class="text-red-600 mt-1">{{ $message }}</div> @enderror

        <label for="type" class="block font-medium mt-3">Content type:</label>
        <select name="type" id="type" class="border w-full p-2 mt-1">
            <option value="blog post" @selected(old('type') === 'blog post')>Blog Post</option>
            <option value="meta description" @selected(old('type') === 'meta description')>Meta Description</option>
            <option value="email subject line" @selected(old('type') === 'email subject line')>Email Subject Line</option>
        </select>

        <label for="tone" class="block font-medium mt-3">Tone:</label>
        <select name="tone" id="tone" class="border w-full p-2 mt-1">
            <option value="professional" @selected(old('tone') === 'professional')>Professional</option>
            <option value="casual" @selected(old('tone') === 'casual')>Casual</option>
            <option value="humorous" @selected(old('tone') === 'humorous')>Humorous</option>
        </select>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 mt-4 rounded">Generate</button>
    </form>

    @error('error') <div class="text-red-600 mt-4">{{ $message }}</div> @enderror

    @isset($output)
        <div class="mt-6">
            <h2 class="text-xl font-semibold mb-2">Generated draft (edit as needed):</h2>
            <textarea class="border w-full p-3 h-64 whitespace-pre-wrap">{{ $output }}</textarea>
        </div>
    @endisset
</div>
</body>
</html>
```
10. Create the app/Services/AiContentService.php with generateDraft() which uses Http::post() to call OpenAI and returns the content, and buildPrompt() which adapts the request to the chosen content type and tone.
```
<?php
/*
Behzad Ghabaei
CS 85 PHP
Module 12 Assignment 12A
Instructor Seno
7/27/2026
*/

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiContentService
{
    /**
     * Generate a draft from a title, content type, and tone.
     */
    public function generateDraft(
        string $title,
        string $type = 'blog post',
        string $tone = 'professional'
    ): string {

        // Set the AI role based on tone
        $role = match ($tone) {
            'casual' => 'You are a friendly and conversational writer.',
            'humorous' => 'You are a witty copywriter with a sense of humor.',
            default => 'You are a professional tech blogger who writes clearly and accurately.',
        };

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.key'),
            'Content-Type'  => 'application/json',
        ])->post(config('services.openai.url') . '/chat/completions', [
            'model' => config('services.openai.model'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $role,
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildPrompt($title, $type, $tone),
                ],
            ],
            'temperature' => 0.7,
            'max_tokens' => 500,
        ]);

        if (! $response->successful()) {
            Log::error('OpenAI API call failed.', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            //throw new Exception('The AI request failed.');
            throw new Exception(
    "Status: {$response->status()} Body: {$response->body()}"
);
        }

        return $response['choices'][0]['message']['content']
            ?? 'No output received';
    }

    /**
     * Build the prompt sent to the AI.
     */
    private function buildPrompt(string $title, string $type, string $tone): string
    {
        $task = match ($type) {
            'blog post' =>
                "Write a blog post titled \"$title\". Include an engaging introduction, several body paragraphs, and a short conclusion. Aim for about 400-500 words.",

            'meta description' =>
                "Write a single SEO-friendly meta description for \"$title\". Keep it around 155 characters.",

            'email subject line' =>
                "Write one short, attention-grabbing email subject line for \"$title\". Keep it under 10 words.",

            default =>
                "Write content about \"$title\".",
        };

        $toneInstruction = match ($tone) {
            'casual' =>
                'Use a casual, friendly, conversational tone.',

            'humorous' =>
                'Use a humorous and playful tone while remaining appropriate.',

            default =>
                'Use a professional, informative tone.',
        };

        return $task . " " . $toneInstruction;
    }
}

```
11. Finally as a bonus, we'll write a feature test that replaces the real service with a fake (a mock), so the test never makes a real API call. This proves your controller and dependency injection work without spending API credits. Create tests/Feature/DraftGenerationTest.php: > php artisan make:test DraftGenerationTest
```
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
```
> Herd\blog-ai>php artisan test > <br>
this will return: <br>
PASS  Tests\Feature\DraftGenerationTest <br>
generate returns the services output   <br>                                                                title must be at least five characters  <br>
> also we could check to see which key is used, with command,                                              \Herd\blog-ai>php artisan tinker
> config('services.openai.key');
= "sk-proj-xxx_tells_your_key_xxx
> exit
INFO  Goodbye.




 
 ## How to obtain an OpenAI key
 1. Navigate to get an api key from https://platform.openai.com
 2. OpenAI requires a credit card payment (loadable visa/american express cards and gift-cards are not accepted)
 3. First sign-up or log-in with email, click continue if already logged in.  Your name will be accessed with email address. Click continue.  Next question. how old are you? Click, finish creating an account. Then "Welcome to OpenAI Platform."  Give Organization name, and project name, click continue, Choose your plan.  You could click free, but it's going to cost at least $5 to get the key to function, click continue. "Make your first API call" create your API key name, click generate API key. an sk-proj-xxxxxxxxxxxxx key will be ready for you to copy. This will be the only time you can see this key. Or you will need to delete this key and generate another. Never share this key. ( you could also copy a curl command that goes into a terminal, not needed for windows, except the http url tells a bit about the API_URL.)  At this point you don't know if this is a demo key or the real key? But you will be able to delete any confusion and create and name the key that you are using. Click continue when you copied the sk-proj-xxxxxxxxxxxxxx key. Next you click another Key icon to create a key, again.  Always save the sk-proj keys and key name together. On the left dashboard there are options such as "API Keys" which allows deletion and re-creating a new one. Be sure to click on "billing" when you are ready, or "3. add credits" Since there is $0.00 credit remaining, your key will not function. You must add payment details: credit card number, expiration, security code, etc., then make sure this is appended to the security key later. When you see that the key is at least $5.00 you are ready to place the sk-proj key into your .env file.
 
### A description of the app,
 Your app includes a Blade form where users enter a title, choose a content type, and choose a tone. Those inputs pass to a service layer that you implement, which builds a structured prompt, sends it to OpenAI, and returns a draft that is displayed in an editable field. Here are screenshots of one entry.

### A screenshot or screencast of the working demo.
 <img width="1366" height="693" alt="Screenshot (1783)" src="https://github.com/user-attachments/assets/df393b60-5d8c-40bb-9258-c560b929c93e" />

 <img width="1366" height="683" alt="Screenshot (1784)" src="https://github.com/user-attachments/assets/9f9e9a2b-85d6-4ba2-9ba3-07bf0e32af93" />

 <img width="1366" height="693" alt="Screenshot (1785)" src="https://github.com/user-attachments/assets/6e6ece80-884f-4bb2-be2f-ea127fa4a544" />

 <img width="1366" height="689" alt="Screenshot (1786)" src="https://github.com/user-attachments/assets/b61b36c4-052a-4b02-b32e-aa92e1f34bd9" />
 
 ## Reflection Questions
### 1. How did the AI output change when you modified the tone or role in your prompt?
When the tone was switched from `professional` to `casual` or `humorous`, the underlying model (`gpt-4o-mini`) shifted its structural tone, vocabulary selections, and grammatical construction. 
* **Professional**: The output utilized passive-voice sentence constructions, high-level business vocabulary, and a formal structural cadence suited for industry documentation.
* **Casual**: The model adopted active-voice phrasings, contractions, a lower structural reading level, and a relaxed, conversational tone.
* **Humorous**: The AI injected situational wordplay, introductory hooks, and comedic pacing tailored to the title context.

### 2. How did your prompt differ across the three content types, and why?
The prompt structure altered length guidelines, formatting syntax constraints, and the intent architecture because each target asset functions inside distinct areas of digital distribution:
* **Blog Post**: The prompt emphasized structural clarity with multiple paragraphs and contextual descriptions designed to thoroughly analyze the user's specific title headline.
* **Meta Description**: The prompt enforced a strict architectural length constraint (roughly 155 characters) packed on a single, continuous line because truncation by search engines destroys SEO index clickability.
* **Email Subject Line**: The prompt omitted long descriptions to optimize email click-through rate, explicitly commanding the removal of external formatting elements (like wrapper quotes) to make the text ready to paste directly into a communications client.

### 3. What would you improve about the API integration for a production app?
To transition this basic integration architecture into an enterprise-ready system, I would implement three key architectural improvements:
* **Asynchronous Queue Jobs & Server-Sent Events (SSE)**: Production web platforms should never execute long-running third-party HTTP requests inside synchronous controller life cycles. I would process generations with isolated background workers (`artisan queue:work`) and stream the text chunks down to the frontend view via WebSockets or Livewire for an interactive typing effect.
* **Granular API Timeout Recovery**: The HTTP handler should incorporate definitive retry policies (`Http::timeout(10)->retry(3, 200)`) to survive regional OpenAI network outages without killing the server lifecycle.
* **Stateful Prompt Management & System Caching**: Moving raw prompt templates out of hardcoded match blocks and into dynamic database configuration tables ensures real-time optimization. Additionally, integrating a local cache layer (like Redis) with a title-hashing filter blocks redundant expensive generation calls for repeating parameters.




<!--
<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
-->
