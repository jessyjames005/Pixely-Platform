---
name: PP_generate-explainer-video
description: "Use when the user invokes /PP_generate-explainer-video to produce explanatory MP4 videos of a feature or bugfix test session on the LOCAL pixely platform (real screen recording of the app driven by Playwright, with French subtitles, config ON vs OFF comparisons), or - as a fallback - a slideshow built from screenshots. Delivers the MP4 files, never commits and never writes into the repository. User-only."
disable-model-invocation: true
---

# Generate explanatory videos of a test session

Two modes. Default = **A (real recording)**: a headless browser replays the scenario on the local app, recorded as video, with a subtitle bar describing each action. Fallback = **B (slideshow)** when recording is impossible (no credentials, no Playwright).

Captions are French; this file and the code stay English. Everything lives in the session scratchpad, never in the repository (`git status` must stay unchanged by this skill).

Rule of truth, both modes: a caption states only what was **observed** (DOM check, JS result, SQL read). Never a hoped-for result. If something did not happen, the caption says so.

## Step 0 - Scope (ask, do not assume)

Use `AskUserQuestion` when unclear: number of videos and their split (e.g. one per tab/area, each comparing config ON vs OFF), subtitle language, which scenarios. Settle how to log in (Step 2) before writing any script.

## Mode A - real recording

### Step 1 - Tooling

- Playwright + Chromium + its video encoder are already in the project node container (`docker-node-1`, under `<repo>/node_modules` and `<repo>/.cache/ms-playwright`). A fresh `docker run` container lacks Chromium system libraries (libnspr4...): run scripts with `docker exec` in the project's node container.
- The app is served on the host. From the container the host is the docker bridge gateway (`172.17.0.1`). Launch Chromium with `--host-resolver-rules=MAP localhost 172.17.0.1` so every URL stays `http://localhost/...`.
- Env for the run: `PLAYWRIGHT_BROWSERS_PATH=/usr/src/app/<app_path>/.cache/ms-playwright`, `NODE_PATH=/usr/src/app/<app_path>/node_modules`. Copy the scripts in with `docker cp` (into `/tmp/rec` in the container); never put them in the repository.
- For the MP4: Playwright records WebM; convert with an `ffmpeg` binary. If none is installed, ask permission to download one (state file, source, size): the `imageio-ffmpeg` Linux wheel (~68 MB) from PyPI, SHA-256 checked, unzipped into its own empty scratchpad directory (no `pip` needed), only the binary is executed. Check `-encoders` lists `libx264`.

### Step 2 - Login (credentials)

Only a login the **user gives in chat for the local app** may be used (never invent or guess one, never reuse it elsewhere). Do not use project e2e passwords you were not given. Put it in an env file (`umask 077`, in the scratchpad, never in the repo), pass it with `docker exec --env-file`, and delete it (`shred -u`) when done. `record_lib.js` logs in inside an **unrecorded** browser context and re-uses its session state in the recorded one, so credentials never appear in the video. Never repeat the password in replies.

### Step 3 - Reusable library

`record_lib.js` (same folder as this file; copy it to the container as `lib.js`) provides `newRecorder(name, outDir)` returning:
- `cap(text, ms)` - subtitle bar (persisted in localStorage, survives navigation), `modalCap(yes, no)` / `popinCap(yes, no)` - subtitle chosen from what was actually observed,
- visible red cursor dot + click flash (hover before click), `click(selector)`, `set(form, field, value)` (pixely platform `$V`, waits for the element inside the page), `goto(url, readySelector)` (wait for list content, not a fixed delay), `closeModal()`, `finish()` (returns the webm path).
- native `confirm()` is replaced by a **simulated popin overlay** (the headless video cannot show native dialogs) that auto-accepts and records the message in `window.__confirms`.

### Step 4 - Scenario scripts

One script per video (copy lib + script into the container, run with `docker exec`). Lessons learned on pixely platform:
- Many modals ("Blocs et SSPI", "Choix de la SSPI"...) are **iframes**: search every `page.frames()` for the text, use `.filter({visible: true})` (the same text often exists hidden elsewhere).
- Autocomplete fields: type with `pressSequentially`, click the `li[id="autocomplete-CPosteSSPI-<id>"]`, and fall back to `$V` on the hidden id field; resolve the form by operation id each time (lists re-render after ajax refreshes, form names change).
- Lists and timing zones refresh unpredictably: wait for the target element, and add a retry that re-opens the page/operation when it does not appear.
- Password validation modals are not filmed: call the next step's function directly (e.g. `submitReveilForm`) and say so in the subtitle. Never type a password in the recorded part.
- Persistent user preferences (e.g. selected blocs/SSPI) can be left changed by a scenario: make an idempotent fix script (read the checkbox state, click only when needed) and run it between videos.
- Print one console line per asserted modal/popin so the run log proves what the subtitles claim.

### Step 5 - Test data writes (only with explicit approval)

Scenarios often need data resets or injected monitoring sessions. SQL writes need the user's explicit approval for exactly those actions (reset of named test records, insertion of test sessions). Implement them as a tiny host-side helper bound to the docker gateway exposing only fixed actions (`/reset`, `/session?type=`, `/endsessions`); stop it afterwards with `pgrep -f '^python3 -I helper'` (never `pkill -f <name>`: it kills your own shell). Tell the user which tables were written.

### Step 6 - Convert, check, deliver

```bash
ffmpeg -y -i page@*.webm -c:v libx264 -crf 23 -pix_fmt yuv420p -movflags +faststart out.mp4
```
Size limit: each MP4 must stay under 10 MB (ask the user for another limit if they give one). After conversion check `ls -la`; if a file is over the limit, re-encode it with a higher CRF (e.g. `-crf 29 -preset slow`, a screen recording with a subtitle bar stays readable up to ~30), then re-check size, duration and resolution. Only raise CRF further or lower the resolution as a last resort.

Extract 3-4 frames per video (`-ss <t> -frames:v 1`) and look at them before delivering. Send each MP4 with `SendUserFile` (`display: "attach"`), name the videos, give duration, and list what was not covered. Then clean up: credentials file, helper process, container `/tmp/rec`, any saved login state.

## Mode B - slideshow of screenshots (fallback)

Screenshots from the browser tool are files whose path is printed in tool results (`.../tool-results/mcp-Claude_Browser-blob-<ms>-<id>.jpg`; the number is a timestamp). Build numbered contact sheets with Pillow (4 columns, ~520x330 thumbnails), match each image to its step, then write one JSON spec per video (format documented at the top of `make_video.py`: slides with `badge`, `title`, `text`, `image` or none for text cards) and render:

```bash
python3 -I ~/.claude/skills/PP_generate-explainer-video/make_video.py <spec.json> --ffmpeg <ffmpeg-binary> --workdir <scratchpad>/work_<n>
```
Text cards cover what cannot be captured (native popins, SQL state, console errors). Say plainly that it is a slideshow, not a recording.

## Always

- Do not commit, push, attach to a merge request or upload anywhere: sharing is the user's decision.
- Check that screenshots/recordings show test data only (no real patient or client data).

