#!/usr/bin/env bash
# Add narration + soft ambient music to the NexaCRM product demo MP4.
#
# Prerequisites: ffmpeg, sox, edge-tts (pip install edge-tts)
#
# Usage:
#   ./scripts/add-demo-video-audio.sh
#   ./scripts/add-demo-video-audio.sh /path/to/silent-or-existing.mp4
#
# Input defaults to public/marketing/videos/nexacrm-product-demo.mp4
# (video stream is copied; audio is replaced).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VIDEO="${1:-$ROOT/public/marketing/videos/nexacrm-product-demo.mp4}"
NARRATION="$ROOT/scripts/demo-video-narration.txt"
VOICE="${VOICE:-en-US-JennyNeural}"
RATE="${RATE:--8%}"

command -v ffmpeg >/dev/null
command -v sox >/dev/null
command -v edge-tts >/dev/null || command -v "$HOME/.local/bin/edge-tts" >/dev/null
EDGE_TTS="$(command -v edge-tts || true)"
EDGE_TTS="${EDGE_TTS:-$HOME/.local/bin/edge-tts}"

[[ -f "$VIDEO" ]] || { echo "missing video: $VIDEO" >&2; exit 1; }
[[ -f "$NARRATION" ]] || { echo "missing narration: $NARRATION" >&2; exit 1; }

WORKDIR="$(mktemp -d /tmp/nexacrm-demo-audio-XXXXXX)"
cleanup() { rm -rf "$WORKDIR"; }
trap cleanup EXIT

DUR="$(ffprobe -v error -show_entries format=duration -of csv=p=0 "$VIDEO")"
echo "video duration: ${DUR}s"

"$EDGE_TTS" --voice "$VOICE" --rate="$RATE" --file "$NARRATION" --write-media "$WORKDIR/voice.mp3"

# Soft original ambient bed (no third-party music license required)
sox -n -r 44100 -c 2 "$WORKDIR/pad1.wav" synth "$DUR" sin 196 sin 246.94 fade 2 "$DUR" 3 vol 0.12
sox -n -r 44100 -c 2 "$WORKDIR/pad2.wav" synth "$DUR" sin 293.66 sin 392 fade 2 "$DUR" 3 vol 0.08
sox -n -r 44100 -c 2 "$WORKDIR/pulse.wav" synth "$DUR" sin 98 fade 2 "$DUR" 3 vol 0.05 tremolo 0.25 40
sox -m "$WORKDIR/pad1.wav" "$WORKDIR/pad2.wav" "$WORKDIR/pulse.wav" "$WORKDIR/music_raw.wav" norm -18
sox "$WORKDIR/music_raw.wav" "$WORKDIR/music.wav" bass -2 treble -1 reverb 20 50 100 100 0 0

ffmpeg -y -i "$WORKDIR/voice.mp3" -ac 2 -ar 44100 "$WORKDIR/voice_stereo.wav" </dev/null
ffmpeg -y -i "$WORKDIR/voice_stereo.wav" -af "adelay=1200|1200,apad=whole_dur=${DUR}" "$WORKDIR/voice_padded.wav" </dev/null
ffmpeg -y -i "$WORKDIR/music.wav" -t "$DUR" -af "volume=0.22" "$WORKDIR/music_quiet.wav" </dev/null
ffmpeg -y \
  -i "$WORKDIR/voice_padded.wav" \
  -i "$WORKDIR/music_quiet.wav" \
  -filter_complex "[0:a]volume=1.15[v];[1:a]volume=1.0[m];[v][m]amix=inputs=2:duration=first:dropout_transition=2,loudnorm=I=-16:TP=-1.5:LRA=11[aout]" \
  -map "[aout]" -t "$DUR" "$WORKDIR/mix.wav" </dev/null

TMP="${VIDEO}.audio-tmp.mp4"
ffmpeg -y -i "$VIDEO" -i "$WORKDIR/mix.wav" \
  -map 0:v:0 -map 1:a:0 -c:v copy -c:a aac -b:a 160k -shortest \
  -movflags +faststart \
  -metadata title="NexaCRM product demo" \
  -metadata comment="NexaCRM — A Modern Multi-Tenant CRM for Growing Businesses (with narration and music)" \
  "$TMP" </dev/null

mv "$TMP" "$VIDEO"
echo "updated $VIDEO"
ffprobe -hide_banner "$VIDEO" 2>&1 | sed -n '1,20p'
