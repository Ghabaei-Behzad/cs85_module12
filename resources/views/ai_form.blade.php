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