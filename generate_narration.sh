#!/bin/zsh

set -euo pipefail

project_dir="/Users/apple/Downloads/projects/agrishield_api/video/agrishield-promo"
source_audio="/private/tmp/agrishield-human-voice.aiff"
processed_audio="/private/tmp/agrishield-human-voice.m4a"

say -v "Tessa" -r 210 -f "$project_dir/narration.txt" -o "$source_audio"

ffmpeg -y -i "$source_audio" \
  -af "highpass=f=75,lowpass=f=9000,equalizer=f=220:t=q:w=1.1:g=-1.5,equalizer=f=3200:t=q:w=1.2:g=1.4,acompressor=threshold=-20dB:ratio=2.2:attack=18:release=180:makeup=2,loudnorm=I=-16:TP=-1.5:LRA=7" \
  -ar 48000 -c:a aac -b:a 192k "$processed_audio"

cp "$processed_audio" "$project_dir/public/audio/voiceover.m4a"

ffprobe -v error -show_entries format=duration \
  -of default=noprint_wrappers=1:nokey=1 \
  "$project_dir/public/audio/voiceover.m4a"
