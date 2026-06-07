---
name: project-docker-is-orbstack
description: Docker on this machine is OrbStack — start it with `open -a OrbStack`, not `open -a Docker`
metadata:
  type: project
---

The Docker daemon on Ivan's machine is provided by **OrbStack** (`/Applications/OrbStack.app`), not Docker Desktop. There is no `Docker.app` and no colima.

**Why:** `open -a Docker` fails with "Unable to find application named 'Docker'". The daemon comes up ~5s after `open -a OrbStack`.

**How to apply:** When `docker info` fails with the daemon down, run `open -a OrbStack` and poll `docker info` until it succeeds. Previously-created ik2 stack containers auto-restart with the daemon.
