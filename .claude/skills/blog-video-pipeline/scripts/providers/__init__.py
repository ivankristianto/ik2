"""Video hosts the pipeline publishes to. Every registered provider gets every video unless --only narrows it.

A provider is a module with NAME, CREDENTIALS ({env var: op:// reference}) and validate, preflight, plan, upload, readback.
"""
from . import youtube

REGISTRY = {p.NAME: p for p in (youtube,)}
