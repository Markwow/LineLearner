<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Script extends Model
{
    protected $fillable = ['title', 'body'];

    public function recordings(): HasMany
    {
        return $this->hasMany(Recording::class);
    }

    /**
     * Parse the raw body into a list of lines.
     * Each line looks like "Speaker: text". Lines without a
     * colon are treated as stage directions (no speaker).
     *
     * @return array<int, array{index:int, speaker:?string, text:string}>
     */
    public function lines(): array
    {
        $lines = [];
        $index = 0;

        foreach (preg_split('/\r\n|\r|\n/', $this->body) as $raw) {
            $raw = trim($raw);
            if ($raw === '') {
                continue;
            }

            $speaker = null;
            $text = $raw;

            if (preg_match('/^([^:]{1,40}):\s*(.*)$/', $raw, $m)) {
                $speaker = trim($m[1]);
                $text = trim($m[2]);
            }

            $lines[] = [
                'index' => $index++,
                'speaker' => $speaker,
                'text' => $text,
            ];
        }

        return $lines;
    }

    /**
     * Distinct speaker names in order of first appearance.
     *
     * @return array<int, string>
     */
    public function speakers(): array
    {
        $speakers = [];
        foreach ($this->lines() as $line) {
            if ($line['speaker'] !== null && ! in_array($line['speaker'], $speakers, true)) {
                $speakers[] = $line['speaker'];
            }
        }

        return $speakers;
    }

    /**
     * Map of line_index => public audio URL for existing recordings.
     *
     * @return array<int, string>
     */
    public function recordingUrls(): array
    {
        return $this->recordings
            ->mapWithKeys(fn (Recording $r) => [$r->line_index => $r->url()])
            ->all();
    }
}
