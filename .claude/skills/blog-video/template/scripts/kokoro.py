"""Speak each job with Kokoro and write a WAV per job. Usage: python -I kokoro.py <models-dir> <jobs.json>"""
import json
import sys

import soundfile as sf
from kokoro_onnx import Kokoro

models, jobs = sys.argv[1], json.load(open(sys.argv[2]))
k = Kokoro(f'{models}/kokoro-v1.0.onnx', f'{models}/voices-v1.0.bin')
for j in jobs:
    samples, sr = k.create(j['text'], voice=j['voice'], speed=j['speed'], lang=j['lang'])
    sf.write(j['out'], samples, sr)
