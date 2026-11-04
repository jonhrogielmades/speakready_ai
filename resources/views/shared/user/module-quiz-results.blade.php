@if(is_array($quizResults) && !empty($quizResults['items']))
    <div class="alert {{ ($quizResults['passed'] ?? false) ? 'alert-success' : 'alert-warning' }} mt-3" role="status">
        <strong>Quiz result: {{ $quizResults['score'] ?? 0 }}% overall</strong>
        <ul class="mb-0 mt-2">
            @foreach($quizResults['items'] as $result)
                <li>{{ $result['title'] }}: {{ $result['score'] }}% · {{ ($result['passed'] ?? false) ? 'Passed' : 'Needs retry' }} ({{ $result['required'] }}% required)</li>
            @endforeach
        </ul>
        @unless($quizResults['passed'] ?? false)
            <p class="mb-0 mt-2">Retry the quiz review after studying the lessons. Every quiz must pass before completion.</p>
        @endunless
    </div>
@endif
