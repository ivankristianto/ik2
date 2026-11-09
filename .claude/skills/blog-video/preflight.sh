#!/usr/bin/env bash
# Install or check everything blog-video needs: node, ffmpeg and uv from Homebrew, then the shared Kokoro venv and model files.
# Usage: preflight.sh [--check]   --check only reports. Run on the host; the tools container has no Homebrew and no GPU.
set -euo pipefail

home=${BLOG_VIDEO_HOME:-"${XDG_CACHE_HOME:-$HOME/.cache}/blog-video"}
check=0
[ "${1:-}" = "--check" ] && check=1
fail=0
ok() { printf 'ok       %s\n' "$*"; }
missing() { printf 'MISSING  %s\n' "$*"; fail=1; }

tool() {
  if command -v "$1" >/dev/null 2>&1; then ok "$1"; return; fi
  if [ $check = 1 ] || ! command -v brew >/dev/null 2>&1; then missing "$1 (brew install $2)"; return; fi
  brew install "$2" >/dev/null && ok "$1 (installed with brew)"
}
tool node node
tool ffmpeg ffmpeg
tool ffprobe ffmpeg
tool uv uv
if command -v node >/dev/null 2>&1 && [ "$(node -p 'process.versions.node.split(".")[0]')" -lt 20 ]; then missing "node 20 or newer (have $(node -v))"; fi

venv="$home/venv"
if "$venv/bin/python" -I -c 'import kokoro_onnx, soundfile' 2>/dev/null; then
  ok "kokoro venv ($venv)"
elif [ $check = 1 ]; then
  missing "kokoro venv ($venv)"
else
  uv venv -q -p 3.12 "$venv"
  VIRTUAL_ENV="$venv" uv pip install -q kokoro-onnx==0.6.1 soundfile==0.14.0
  ok "kokoro venv (installed in $venv)"
fi

# Whisper for `npm run hear`: MLX only runs on Apple Silicon, so elsewhere the check is skipped, not failed.
if [ "$(uname -sm)" = "Darwin arm64" ]; then
  if [ -x "$venv/bin/mlx_whisper" ]; then
    ok "mlx_whisper"
  elif [ $check = 1 ]; then
    missing "mlx_whisper (for npm run hear)"
  elif [ -x "$venv/bin/python" ]; then
    VIRTUAL_ENV="$venv" uv pip install -q mlx-whisper==0.4.3 && ok "mlx_whisper (installed)"
  fi
else
  echo "skip     mlx_whisper (needs Apple Silicon; npm run hear won't work here)"
fi

# Pinned by checksum so a re-uploaded release can't change the voice under an old video.
models="$home/models"
mkdir -p "$models"
while read -r sum name; do
  f="$models/$name"
  if [ -f "$f" ] && [ "$(shasum -a 256 "$f" | cut -d' ' -f1)" = "$sum" ]; then ok "$name"; continue; fi
  if [ $check = 1 ]; then missing "$name"; continue; fi
  curl -fsSL -o "$f.part" "https://github.com/thewh1teagle/kokoro-onnx/releases/download/model-files-v1.0/$name"
  if [ "$(shasum -a 256 "$f.part" | cut -d' ' -f1)" != "$sum" ]; then rm -f "$f.part"; missing "$name (checksum mismatch, download deleted)"; continue; fi
  mv "$f.part" "$f" && ok "$name (downloaded)"
done <<'SUMS'
7d5df8ecf7d4b1878015a32686053fd0eebe2bc377234608764cc0ef3636a6c5 kokoro-v1.0.onnx
bca610b8308e8d99f32e6fe4197e7ec01679264efed0cac9140fe9c29f1fbf7d voices-v1.0.bin
SUMS

if [ $fail = 0 ]; then
  "$venv/bin/python" -I -c "
import sys
from kokoro_onnx import Kokoro
k = Kokoro(sys.argv[1] + '/kokoro-v1.0.onnx', sys.argv[1] + '/voices-v1.0.bin')
s, sr = k.create('Preflight check.', voice='bm_george', speed=1.0, lang='en-gb')
assert len(s) > sr * 0.3, 'kokoro produced no audio'
" "$models" 2>/dev/null && ok "kokoro speaks (bm_george)" || missing "kokoro smoke test failed"
fi

[ $fail = 0 ] && echo "preflight passed ($home)" || { echo "preflight failed"; exit 1; }
