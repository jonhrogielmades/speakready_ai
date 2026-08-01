# SpeakReady reliable question bank

Normalized, question-only practice data for SpeakReady AI.

Each JSONL row contains:

- `id`: stable question identifier.
- `dataset_key`: logical source pack.
- `category`: app-facing practice category.
- `country`: target locale when applicable.
- `question_text`: practice question shown to the learner.
- `type`: Personal, Behavioral, Situational, Technical, or Candidate Question.
- `difficulty`: Easy, Medium, or Hard.
- `expected_guide`: short scoring/coaching expectation.
- `mapped_skills`: skills assessed by the question.
- `source_keys`: downloaded source snapshots or metadata sources used for provenance.
- `provenance`: `adapted_practice_prompt` means the wording is normalized for practice and should not be represented as a verbatim source quote.
