@php
    $voiceRecordingPath = trim((string) ($answer->voice_recording_path ?? ''));
    $voiceRecordingUrl = $voiceRecordingPath !== '' ? route('interview.answer.voiceRecording', $answer) : null;
    $voiceSize = (int) ($answer->voice_recording_byte_size ?? 0);
    $voiceDuration = (int) ($answer->voice_duration ?? 0);
    $voiceMeta = array_values(array_filter([
        $voiceDuration > 0 ? $voiceDuration.'s' : null,
        $voiceSize >= 1048576 ? number_format($voiceSize / 1048576, 1).' MB' : ($voiceSize > 0 ? max(1, (int) ceil($voiceSize / 1024)).' KB' : null),
    ]));
    $hasTranscriptText = trim((string) ($answer->answer_text ?? '')) !== '';
@endphp

@if($voiceRecordingUrl)
    <div class="mb-4 p-4" style="background:rgba(14,165,233,.06);border:1px solid rgba(14,165,233,.22);border-radius:12px;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h6 style="color:#0284c7;font-weight:800;margin:0;"><i class="fa-solid fa-wave-square me-2"></i>Voice Answer</h6>
            @if(!empty($voiceMeta))
                <span class="retry-chip">{{ implode(' / ', $voiceMeta) }}</span>
            @endif
        </div>
        <audio controls preload="metadata" src="{{ $voiceRecordingUrl }}" style="width:100%;display:block;"></audio>
        @if(!$hasTranscriptText)
            <p style="color:var(--tx3);font-size:.84rem;line-height:1.45;margin:10px 0 0;">Transcript unavailable. Your voice recording was still saved for playback and review.</p>
        @endif
    </div>
@endif
