# ik2 embedder

The Corpus worker. Each run embeds the Documents and Chunks the ik2 plugin
queued, ranks related Articles, writes the results, and exits. The behaviour
is defined in `docs/specs/2026-10-30-embedding-corpus-design.md`.

This is its own npm package with its own `package-lock.json`. It is not part
of the root pnpm workspace, so use npm here, not pnpm.

## Environment

| Variable | Default | |
| --- | --- | --- |
| `CORPUS_DB_HOST` | | required |
| `CORPUS_DB_PORT` | `3306` | |
| `CORPUS_DB_USER` | | required, `ik2_corpus` |
| `CORPUS_DB_PASSWORD` | | required |
| `CORPUS_DB_NAME` | | required |
| `CORPUS_TABLE_PREFIX` | `wp_` | letters, digits and `_` only |
| `EMBEDDER_MODELS_DIR` | `/models` | set by the image; override only to run outside Docker |

## Tests

```bash
npm ci
npm test
```

The tests cover only the pure modules (`bm25`, `rrf`, `rank`, `vectors`,
`staleness`, `levels`, `config`) and the Run flow against a fake database.
They don't import `mysql2` or transformers.js, so they pass without
`node_modules` too.

## Image

```bash
docker build -t ik2-embedder:dev services/embedder
```

The build downloads the model from a pinned Hugging Face revision into
`/models`, then runs `src/selfcheck.mjs` with the network off. A missing
model file or a broken prune fails the build here, before any hourly run.

Pruned at build: `onnxruntime-node` binaries for other platforms,
`onnxruntime-web` (transformers.js bundles its own copy into
`transformers.node.mjs`), and source maps. The image is about 450 MB, of
which 132 MB is the model.

## Running locally

From the repo root, `composer dev:corpus` applies the local grants and runs
the `embedder` compose service. To run the image by hand against the local
`db`:

```bash
docker run --rm --memory=1g --network ik2org_default \
  -e CORPUS_DB_HOST=db -e CORPUS_DB_USER=ik2_corpus \
  -e CORPUS_DB_PASSWORD=ik2_corpus_local -e CORPUS_DB_NAME=wordpress \
  ik2-embedder:dev
```

## Output

One line per run, on stdout:

```
ik2-embedder: no work, 0.0s
ik2-embedder: run 4, 0 documents and 1 chunks embedded, 1 removed, 0 failed, 1 orphan vectors deleted, 489 related lists, 1.2s, peak rss 407 MB
```

Exit 0 on success, on no work, or when another run holds the lock. Exit 1
when the run itself failed (bad config, database unreachable, model would
not load); the error goes to stderr. A Document whose embedding fails gets
an attempt and `last_error`, and is parked at 3. That doesn't fail the run.

## Numbers from a local backfill

490 Articles, 651 Chunks, `--memory=1g`, OrbStack on Apple Silicon (arm64):
169 s for the full backfill, peak RSS 598 MB. A run with no work finishes
in about half a second including container start, without loading the
model.

ONNX Runtime's CPU arena is turned off in `src/embedder.mjs`. With it on,
peak RSS was 855 MB, too close to the 1 GB cap.
