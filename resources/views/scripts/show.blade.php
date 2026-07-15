@extends('layouts.app')

@section('title', $script->title)

@push('head')
<style>
    .toolbar { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 18px; }
    .toolbar .grow { flex: 1; }
    .line {
        display: flex; align-items: flex-start; gap: 12px;
        padding: 12px 14px; border-radius: 12px; margin-bottom: 8px;
        border: 1px solid transparent;
    }
    .line .speaker { width: 90px; flex-shrink: 0; font-weight: 650; color: var(--muted); }
    .line .text { flex: 1; padding-top: 1px; }
    .line .controls { display: flex; gap: 6px; align-items: center; flex-shrink: 0; }
    .line.cue { background: var(--panel); border-color: var(--border); }
    .line.cue .speaker { color: var(--accent); }
    .line.context { opacity: .55; }
    .line.context .controls { display: none; }
    .icon-btn {
        width: 40px; height: 40px; padding: 0; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center; font-size: 16px;
    }
    .icon-btn.rec { background: var(--record); }
    .icon-btn.rec.recording { animation: pulse 1s infinite; }
    .icon-btn.play { background: var(--accent-2); }
    .icon-btn.play:disabled { background: var(--panel-2); }
    .icon-btn.del { background: transparent; color: var(--muted); font-size: 18px; }
    @keyframes pulse { 0%,100% { filter: brightness(1); } 50% { filter: brightness(1.5); } }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--border); flex-shrink:0; }
    .status-dot.has { background: var(--good); }
    .hint { font-size: 13px; color: var(--muted); }
</style>
@endpush

@section('content')
    <div class="row-flex" style="justify-content:space-between; margin-bottom:14px;">
        <h2 style="margin:0">{{ $script->title }}</h2>
        <a href="{{ route('scripts.index') }}" class="muted">← all scripts</a>
    </div>

    <div class="toolbar">
        <div>
            <label style="margin-bottom:2px">Cue character (whose lines you record)</label>
            <select id="cueSelect">
                <option value="__all__">Every line</option>
                @foreach($speakers as $speaker)
                    <option value="{{ $speaker }}" @if($loop->first) selected @endif>{{ $speaker }}</option>
                @endforeach
            </select>
        </div>
        <div class="grow"></div>
        <button id="playAllBtn" class="btn-ghost">▶ Play cue lines in order</button>
    </div>

    <p class="hint" id="micHint">Hit the red button on a cue line to record it. Recordings save automatically.</p>

    <div id="lines"
         data-store-tpl="{{ route('recordings.store', ['script' => $script->id, 'lineIndex' => '__IDX__']) }}"
         data-destroy-tpl="{{ route('recordings.destroy', ['script' => $script->id, 'lineIndex' => '__IDX__']) }}">
        @foreach($lines as $line)
            <div class="line" data-index="{{ $line['index'] }}" data-speaker="{{ $line['speaker'] }}">
                <div class="speaker">{{ $line['speaker'] ?? '' }}</div>
                <div class="text">{{ $line['text'] }}</div>
                <div class="controls">
                    <span class="status-dot {{ isset($recordingUrls[$line['index']]) ? 'has' : '' }}"></span>
                    <button class="icon-btn rec" title="Record">●</button>
                    <button class="icon-btn play" title="Play" {{ isset($recordingUrls[$line['index']]) ? '' : 'disabled' }}>▶</button>
                    <button class="icon-btn del" title="Delete" style="{{ isset($recordingUrls[$line['index']]) ? '' : 'display:none' }}">×</button>
                </div>
            </div>
        @endforeach
    </div>

    <details style="margin-top:24px">
        <summary class="muted" style="cursor:pointer">Edit script text</summary>
        <form method="POST" action="{{ route('scripts.update', $script) }}" class="panel" style="margin-top:12px">
            @csrf
            @method('PUT')
            <label>Title</label>
            <input type="text" name="title" value="{{ $script->title }}" required>
            <label>Script</label>
            <textarea name="body" required>{{ $script->body }}</textarea>
            <div class="row-flex" style="justify-content:space-between">
                <button type="submit">Save changes</button>
            </div>
        </form>
        <form method="POST" action="{{ route('scripts.destroy', $script) }}"
              onsubmit="return confirm('Delete this script and all its recordings?')" style="margin-top:8px">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-danger">Delete script</button>
        </form>
    </details>

    <script>
        const CSRF = document.querySelector('meta[name=csrf-token]').content;
        const linesEl = document.getElementById('lines');
        const storeTpl = linesEl.dataset.storeTpl;
        const destroyTpl = linesEl.dataset.destroyTpl;
        const cueSelect = document.getElementById('cueSelect');
        const micHint = document.getElementById('micHint');

        // Existing recordings from the server.
        const urls = @json($recordingUrls);

        let mediaRecorder = null;
        let currentRow = null;
        let chunks = [];
        let currentAudio = null;

        function applyCueFilter() {
            const cue = cueSelect.value;
            document.querySelectorAll('.line').forEach(row => {
                const speaker = row.dataset.speaker || '';
                const isCue = cue === '__all__' || speaker === cue;
                row.classList.toggle('cue', isCue);
                row.classList.toggle('context', !isCue);
            });
        }
        cueSelect.addEventListener('change', applyCueFilter);
        applyCueFilter();

        function urlFor(tpl, idx) { return tpl.replace('__IDX__', idx); }

        async function startRecording(row, recBtn) {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                micHint.textContent = 'This browser does not support microphone recording.';
                return;
            }
            let stream;
            try {
                stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            } catch (e) {
                micHint.textContent = 'Microphone permission denied. Allow mic access and try again.';
                return;
            }
            chunks = [];
            mediaRecorder = new MediaRecorder(stream);
            currentRow = row;
            mediaRecorder.ondataavailable = e => { if (e.data.size) chunks.push(e.data); };
            mediaRecorder.onstop = async () => {
                stream.getTracks().forEach(t => t.stop());
                const blob = new Blob(chunks, { type: mediaRecorder.mimeType || 'audio/webm' });
                await uploadBlob(row, blob);
                recBtn.classList.remove('recording');
                recBtn.textContent = '●';
            };
            mediaRecorder.start();
            recBtn.classList.add('recording');
            recBtn.textContent = '■';
        }

        function stopRecording(recBtn) {
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
            }
        }

        async function uploadBlob(row, blob) {
            const idx = row.dataset.index;
            const form = new FormData();
            const ext = (blob.type.includes('ogg')) ? 'ogg' : 'webm';
            form.append('audio', blob, `line-${idx}.${ext}`);
            const res = await fetch(urlFor(storeTpl, idx), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: form,
            });
            if (!res.ok) {
                micHint.textContent = 'Upload failed (' + res.status + '). Try again.';
                return;
            }
            const data = await res.json();
            urls[idx] = data.url + '?t=' + Date.now();
            markRecorded(row);
        }

        function markRecorded(row) {
            row.querySelector('.status-dot').classList.add('has');
            row.querySelector('.play').disabled = false;
            row.querySelector('.del').style.display = '';
        }

        function playRow(row) {
            const idx = row.dataset.index;
            if (!urls[idx]) return null;
            if (currentAudio) { currentAudio.pause(); currentAudio = null; }
            const audio = new Audio(urls[idx]);
            currentAudio = audio;
            audio.play();
            return audio;
        }

        async function deleteRow(row) {
            const idx = row.dataset.index;
            await fetch(urlFor(destroyTpl, idx), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            delete urls[idx];
            row.querySelector('.status-dot').classList.remove('has');
            row.querySelector('.play').disabled = true;
            row.querySelector('.del').style.display = 'none';
        }

        linesEl.addEventListener('click', e => {
            const row = e.target.closest('.line');
            if (!row) return;
            if (e.target.classList.contains('rec')) {
                if (mediaRecorder && mediaRecorder.state === 'recording' && currentRow === row) {
                    stopRecording(e.target);
                } else if (mediaRecorder && mediaRecorder.state === 'recording') {
                    // ignore while another row is recording
                } else {
                    startRecording(row, e.target);
                }
            } else if (e.target.classList.contains('play')) {
                playRow(row);
            } else if (e.target.classList.contains('del')) {
                if (confirm('Delete this recording?')) deleteRow(row);
            }
        });

        // Play all cue lines in order, waiting for each to finish.
        const playAllBtn = document.getElementById('playAllBtn');
        let sequencePlaying = false;
        playAllBtn.addEventListener('click', async () => {
            if (sequencePlaying) { if (currentAudio) currentAudio.pause(); sequencePlaying = false; playAllBtn.textContent = '▶ Play cue lines in order'; return; }
            sequencePlaying = true;
            playAllBtn.textContent = '■ Stop';
            const rows = [...document.querySelectorAll('.line.cue')];
            for (const row of rows) {
                if (!sequencePlaying) break;
                if (!urls[row.dataset.index]) continue;
                await new Promise(resolve => {
                    const audio = playRow(row);
                    if (!audio) return resolve();
                    audio.onended = resolve;
                    audio.onerror = resolve;
                });
            }
            sequencePlaying = false;
            playAllBtn.textContent = '▶ Play cue lines in order';
        });
    </script>
@endsection
