@extends('layouts.app')

@section('title', 'Attempt - ' . $quiz->title)

@section('content')
<div class="container py-4">
    <h2 class="mb-1">{{ $quiz->title }}</h2>
    <p class="text-muted">Attempt #{{ $attempt->attempt_number }}
        @if($quiz->duration_minutes && $attempt->started_at)
            · Started {{ $attempt->started_at->format('H:i') }}
            · Time limit {{ $quiz->duration_minutes }} min
        @endif
    </p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('sip.quizzes.submit', [$quiz, $attempt]) }}" id="quizForm">
        @csrf
        @foreach($quiz->questions as $question)
            @php $answer = $answersByQuestion->get($question->id); @endphp
            <div class="card mb-3">
                <div class="card-header">
                    <strong>Q{{ $loop->iteration }}.</strong>
                    <span class="badge bg-light text-dark">{{ $question->typeLabel() }}</span>
                    <span class="float-end">{{ number_format($question->marks, 1) }} marks</span>
                </div>
                <div class="card-body">
                    <p>{{ $question->question_text }}</p>

                    @if($question->type === 'single_choice' || $question->type === 'true_false')
                        @foreach($question->options as $opt)
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                       name="answers[{{ $question->id }}][option]"
                                       id="q{{ $question->id }}_o{{ $opt->id }}"
                                       value="{{ $opt->id }}"
                                       {{ in_array($opt->id, $answer->selected_option_ids ?? [], false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="q{{ $question->id }}_o{{ $opt->id }}">{{ $opt->option_text }}</label>
                            </div>
                        @endforeach
                    @elseif($question->type === 'multiple_choice')
                        @foreach($question->options as $opt)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       name="answers[{{ $question->id }}][options][]"
                                       id="q{{ $question->id }}_o{{ $opt->id }}"
                                       value="{{ $opt->id }}"
                                       {{ in_array($opt->id, $answer->selected_option_ids ?? [], false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="q{{ $question->id }}_o{{ $opt->id }}">{{ $opt->option_text }}</label>
                            </div>
                        @endforeach
                    @elseif($question->type === 'fill_blank' || $question->type === 'short_answer')
                        <input type="text" class="form-control" name="answers[{{ $question->id }}][text]"
                               value="{{ $answer->answer_text ?? '' }}" placeholder="Your answer">
                    @elseif($question->type === 'essay')
                        <textarea class="form-control" name="answers[{{ $question->id }}][text]" rows="6"
                                  placeholder="Write your answer...">{{ $answer->answer_text ?? '' }}</textarea>
                    @elseif($question->type === 'code')
                        @if($question->code_language)
                            <div class="small text-muted mb-1">Language: {{ $question->code_language }}</div>
                        @endif
                        <textarea class="form-control font-monospace" name="answers[{{ $question->id }}][text]" rows="10"
                                  placeholder="Write your code...">{{ $answer->answer_text ?? '' }}</textarea>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="d-flex gap-2 flex-wrap">
            <button type="submit" formaction="{{ route('sip.quizzes.save', [$quiz, $attempt]) }}"
                    class="btn btn-outline-secondary" formnovalidate>
                Save Progress
            </button>
            <button type="submit" class="btn btn-primary"
                    onclick="return confirm('Submit this quiz? You will not be able to edit after submission.');">
                Submit Quiz
            </button>
            <a href="{{ route('sip.quizzes.show', $quiz) }}" class="btn btn-link">Exit</a>
        </div>
    </form>
</div>
@endsection
