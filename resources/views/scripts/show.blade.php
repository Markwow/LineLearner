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
    .icon-btn.play.playing { background: var(--record); }
    .icon-btn.play:disabled { background: var(--panel-2); }
    .icon-btn.del { background: transparent; color: var(--muted); font-size: 18px; }
    @keyframes pulse { 0%,100% { filter: brightness(1); } 50% { filter: brightness(1.5); } }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--border); flex-shrink:0; }
    .status-dot.has { background: var(--good); }
    .hint { font-size: 13px; color: var(--muted); }

    /* Currently-playing line during rehearsal */
    .line.now-playing {
        border-color: var(--accent);
        box-shadow: 0 0 0 1px var(--accent), 0 6px 22px rgba(108,140,255,.25);
    }

    /* Sticky rehearsal control bar */
    .rehearsal-bar {
        position: fixed; left: 0; right: 0; bottom: 0; z-index: 50;
        background: rgba(15,17,21,.86); backdrop-filter: blur(10px);
        border-top: 1px solid var(--border);
        padding: 10px 16px calc(10px + env(safe-area-inset-bottom));
    }
    .rb-inner { max-width: 780px; margin: 0 auto; display: flex; flex-direction: column; gap: 8px; }
    .rb-nextup { display: flex; align-items: baseline; gap: 8px; min-height: 20px; }
    .rb-label {
        font-size: 11px; text-transform: uppercase; letter-spacing: .06em;
        color: var(--muted); flex-shrink: 0;
    }
    .rb-next-text { font-size: 14px; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .rb-controls { display: flex; gap: 8px; }
    .rb-btn {
        height: 52px; border-radius: 12px; font-size: 16px; font-weight: 650;
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        background: var(--panel-2); color: var(--text); border: 1px solid var(--border);
    }
    .rb-btn.wide { flex: 1; }
    .rb-btn.fixed { width: 60px; flex-shrink: 0; }
    .rb-btn.primary { background: var(--accent-2); border-color: transparent; color: #fff; }
    .rb-btn.playpause.active { background: var(--good); border-color: transparent; color: #06231a; }
    .rb-kbd { font-size: 12px; color: var(--muted); text-align: center; }
    .rb-kbd kbd {
        background: var(--panel-2); border: 1px solid var(--border); border-bottom-width: 2px;
        border-radius: 6px; padding: 1px 6px; font-family: inherit; font-size: 11px; color: var(--text);
    }
    @media (min-width: 560px) {
        .rb-inner { flex-direction: row; align-items: center; }
        .rb-nextup { flex: 1; min-width: 0; }
        .rb-controls { flex-shrink: 0; }
        .rb-kbd { display: none; }
    }
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

    {{-- Spacer so the fixed rehearsal bar never hides the last lines --}}
    <div style="height: 150px"></div>

    <div class="rehearsal-bar" id="rehearsalBar">
        <div class="rb-inner">
            <div class="rb-nextup">
                <span class="rb-label">Next&nbsp;up</span>
                <span class="rb-next-text" id="rbNextText">—</span>
            </div>
            <div class="rb-controls">
                <button id="rbRestart" class="rb-btn fixed" title="Restart from first line (R)">⏮</button>
                <button id="rbPlay" class="rb-btn fixed playpause" title="Play / Pause (Space)">▶</button>
                <button id="rbNext" class="rb-btn wide primary" title="Play next cue line (→)">Next cue ⏭</button>
            </div>
            <div class="rb-kbd"><kbd>Space</kbd> play/pause · <kbd>→</kbd> next cue</div>
        </div>
    </div>

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
        let mediaStream = null;
        let currentRow = null;
        let currentRecBtn = null;
        let isRecording = false;
        let chunks = [];
        let currentAudio = null;
        let currentPlayBtn = null;

        function applyCueFilter() {
            const cue = cueSelect.value;
            document.querySelectorAll('.line').forEach(row => {
                const speaker = row.dataset.speaker || '';
                const isCue = cue === '__all__' || speaker === cue;
                row.classList.toggle('cue', isCue);
                row.classList.toggle('context', !isCue);
            });
        }
        // Changing the cue character rebuilds the queue, so reset the rehearsal cursor.
        cueSelect.addEventListener('change', () => { applyCueFilter(); restartRehearsal(); });
        applyCueFilter();

        function urlFor(tpl, idx) { return tpl.replace('__IDX__', idx); }

        // Release the mic no matter what state the recorder is in. iOS holds the
        // mic open (the orange dot / "recording" state) until every track is stopped,
        // so this must be callable independently of the recorder's own events.
        function releaseMic() {
            if (mediaStream) {
                mediaStream.getTracks().forEach(t => t.stop());
                mediaStream = null;
            }
        }

        function resetRecBtn(recBtn) {
            if (!recBtn) return;
            recBtn.classList.remove('recording');
            recBtn.textContent = '●';
        }

        // Safari/iOS produces audio/mp4, Chrome/Firefox produce webm. Pick the first
        // type the browser actually supports so MediaRecorder never throws on iOS.
        function pickMimeType() {
            const candidates = ['audio/mp4', 'audio/webm;codecs=opus', 'audio/webm', 'audio/mpeg'];
            if (window.MediaRecorder && MediaRecorder.isTypeSupported) {
                for (const t of candidates) {
                    if (MediaRecorder.isTypeSupported(t)) return t;
                }
            }
            return '';
        }

        function extFor(mime) {
            if (mime.includes('mp4')) return 'm4a';
            if (mime.includes('mpeg')) return 'mp3';
            if (mime.includes('ogg')) return 'ogg';
            return 'webm';
        }

        async function startRecording(row, recBtn) {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.MediaRecorder) {
                micHint.textContent = 'This browser does not support microphone recording.';
                return;
            }
            try {
                mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
            } catch (e) {
                mediaStream = null;
                micHint.textContent = 'Microphone permission denied. Allow mic access and try again.';
                return;
            }

            chunks = [];
            const mime = pickMimeType();
            try {
                mediaRecorder = mime
                    ? new MediaRecorder(mediaStream, { mimeType: mime })
                    : new MediaRecorder(mediaStream);
            } catch (e) {
                // Constructing the recorder failed — release the mic so it isn't stuck on.
                releaseMic();
                micHint.textContent = 'Could not start recording on this browser.';
                return;
            }

            currentRow = row;
            currentRecBtn = recBtn;
            isRecording = true;
            micHint.textContent = 'Recording… tap ■ to stop.';

            mediaRecorder.ondataavailable = e => { if (e.data && e.data.size) chunks.push(e.data); };
            mediaRecorder.onstop = async () => {
                releaseMic();
                resetRecBtn(recBtn);
                if (!chunks.length) {
                    micHint.textContent = 'That recording came out empty — try again.';
                    return;
                }
                const type = (mediaRecorder && mediaRecorder.mimeType) || mime || 'audio/mp4';
                const blob = new Blob(chunks, { type });
                await uploadBlob(row, blob);
            };
            mediaRecorder.onerror = () => { releaseMic(); resetRecBtn(recBtn); isRecording = false; };

            // Timeslice makes Safari flush data during recording instead of only at
            // stop(), which is far more reliable on iOS and avoids empty recordings.
            mediaRecorder.start(1000);
            recBtn.classList.add('recording');
            recBtn.textContent = '■';
        }

        function stopRecording(recBtn) {
            isRecording = false;
            currentRecBtn = null;
            // Reset the UI immediately so the button never looks stuck.
            resetRecBtn(recBtn);
            try {
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    mediaRecorder.stop();
                }
            } catch (e) { /* ignore */ }
            // Safety net: if onstop never fires (a known iOS quirk), force the mic off.
            setTimeout(releaseMic, 1500);
        }

        async function uploadBlob(row, blob) {
            const idx = row.dataset.index;
            const form = new FormData();
            form.append('audio', blob, `line-${idx}.${extFor(blob.type)}`);
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
            updateNextUp();
        }

        function stopCurrentAudio() {
            if (currentAudio) { currentAudio.pause(); currentAudio = null; }
            if (currentPlayBtn) {
                currentPlayBtn.textContent = '▶';
                currentPlayBtn.classList.remove('playing');
                currentPlayBtn = null;
            }
        }

        function playRow(row) {
            const idx = row.dataset.index;
            if (!urls[idx]) return null;
            stopCurrentAudio();
            const playBtn = row.querySelector('.play');
            const audio = new Audio(urls[idx]);
            currentAudio = audio;
            currentPlayBtn = playBtn;
            playBtn.textContent = '■';
            playBtn.classList.add('playing');
            const reset = () => {
                if (currentPlayBtn === playBtn) {
                    playBtn.textContent = '▶';
                    playBtn.classList.remove('playing');
                    currentPlayBtn = null;
                    currentAudio = null;
                }
            };
            audio.addEventListener('ended', reset);
            audio.addEventListener('error', reset);
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
            if (row === currentCueRow) { currentCueRow = null; row.classList.remove('now-playing'); }
            updateNextUp();
        }

        linesEl.addEventListener('click', e => {
            const row = e.target.closest('.line');
            if (!row) return;
            if (e.target.classList.contains('rec')) {
                if (isRecording && currentRecBtn === e.target) {
                    stopRecording(e.target);
                } else if (isRecording) {
                    // ignore while another row is recording
                } else {
                    startRecording(row, e.target);
                }
            } else if (e.target.classList.contains('play')) {
                // Toggle: tapping the button that's currently playing stops it.
                if (currentPlayBtn === e.target) {
                    stopCurrentAudio();
                } else {
                    playRow(row);
                    // Tapping a cue line directly moves the rehearsal cursor here,
                    // so "Next" continues from this point.
                    if (row.classList.contains('cue')) {
                        currentCueRow = row;
                        highlightCue(row);
                        updateNextUp();
                    }
                }
            } else if (e.target.classList.contains('del')) {
                if (confirm('Delete this recording?')) deleteRow(row);
            }
        });

        // ---- Rehearsal engine: step through the cue queue hands-free ----
        const rbPlay = document.getElementById('rbPlay');
        const rbNext = document.getElementById('rbNext');
        const rbRestart = document.getElementById('rbRestart');
        const rbNextText = document.getElementById('rbNextText');
        let currentCueRow = null;   // last cue line played
        let autoAdvance = false;    // true while auto-playing straight through the queue

        // The queue is every cue line that actually has a recording, in order.
        function buildQueue() {
            return [...document.querySelectorAll('.line.cue')].filter(r => urls[r.dataset.index]);
        }

        function nextCueRow() {
            const q = buildQueue();
            if (!q.length) return null;
            if (!currentCueRow) return q[0];
            const i = q.indexOf(currentCueRow);
            if (i === -1) return q[0];
            return q[i + 1] || null; // null once we're past the last line
        }

        function highlightCue(row) {
            document.querySelectorAll('.line.now-playing').forEach(r => r.classList.remove('now-playing'));
            if (row) row.classList.add('now-playing');
        }

        function setPlayIcon(playing) {
            rbPlay.textContent = playing ? '⏸' : '▶';
            rbPlay.classList.toggle('active', playing);
        }

        function updateNextUp() {
            const row = nextCueRow();
            if (!row) {
                rbNextText.textContent = buildQueue().length
                    ? 'End of scene — press ▶ or Next to start over'
                    : 'No recorded cue lines yet';
                return;
            }
            const sp = row.dataset.speaker || '';
            const txt = row.querySelector('.text').textContent.trim();
            rbNextText.textContent = (sp ? sp + ' — ' : '') + '“' + txt + '”';
        }

        // Play one cue line, scrolling it into view. If `auto`, chain to the next when it ends.
        function playCue(row, auto) {
            currentCueRow = row;
            highlightCue(row);
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            const audio = playRow(row);
            setPlayIcon(!!audio);
            if (audio) {
                audio.addEventListener('ended', () => {
                    if (autoAdvance) advance(true);
                    else setPlayIcon(false);
                });
            }
            updateNextUp();
        }

        // Advance to and play the next cue line. Returns false at the end of the queue.
        function advance(auto) {
            const row = nextCueRow();
            if (!row) {
                currentCueRow = null;   // reset so the next press starts from the top
                autoAdvance = false;
                highlightCue(null);
                setPlayIcon(false);
                updateNextUp();
                return false;
            }
            playCue(row, auto);
            return true;
        }

        function togglePlayPause() {
            if (currentAudio && !currentAudio.paused && !currentAudio.ended) {
                currentAudio.pause();          // playing -> pause
                setPlayIcon(false);
            } else if (currentAudio && currentAudio.paused) {
                currentAudio.play();           // paused -> resume
                setPlayIcon(true);
            } else {
                autoAdvance = true;            // idle -> start auto-playing the queue
                advance(true);
            }
        }

        function restartRehearsal() {
            stopCurrentAudio();
            autoAdvance = false;
            currentCueRow = null;
            highlightCue(null);
            setPlayIcon(false);
            updateNextUp();
        }

        rbPlay.addEventListener('click', togglePlayPause);
        rbNext.addEventListener('click', () => advance(autoAdvance));
        rbRestart.addEventListener('click', restartRehearsal);

        // Keyboard: Space = play/pause, → = next cue, R = restart.
        document.addEventListener('keydown', e => {
            const t = e.target;
            if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable)) return;
            if (e.code === 'Space') { e.preventDefault(); togglePlayPause(); }
            else if (e.code === 'ArrowRight') { e.preventDefault(); advance(autoAdvance); }
            else if (e.key === 'r' || e.key === 'R') { e.preventDefault(); restartRehearsal(); }
        });

        updateNextUp();
    </script>
@endsection
