# Media Manager PDF Icon (`plg_system_mediapdficon`)

A **Joomla 5** system plugin that shows a PDF-specific icon for PDF files
in the Media Manager **grid view**, instead of the generic document icon
Joomla core shows for every non-image/video/audio file type. Built for



## Status

Verified so far: PHP syntax valid, the injected JS passes a real syntax
check (`node --check`) and its file-matching regex passes a table of
realistic filenames including deliberate near-miss cases (`report.pdfx`,
`my.pdf.backup.txt`) that should NOT match. **NOT yet verified in an
actual browser** — the DOM structure this relies on was read directly from
Joomla's own compiled source (see below), not guessed, but only a live
page can confirm the MutationObserver actually finds and patches real
items as the grid renders/re-renders.

## Why this needed a plugin at all — confirmed, not assumed

Traced Joomla's own compiled Vue SPA (`media/com_media/js/media-manager.js`)
before building anything:

- The grid's item-type dispatcher (`itemType()`) routes any extension
  listed in `com_media`'s own `doc_extensions` param
  (`doc,odg,odp,ods,odt,pdf,ppt,txt,xcf,xls,csv`) to
  `MediaBrowserItemDocument`, which unconditionally renders
  `<span class="fas fa-file">` — **no per-extension distinction of any
  kind** within that whole bucket. PDF, a spreadsheet, and a plain `.txt`
  file all render identically.
- The **list view** groups the same extensions under one shared CSS rule
  too (`media/com_media/css/media-manager.css`,
  `.type[data-type="doc" i]:before, ..."pdf"..., ..."txt" i]:before`) — but
  via a **custom private-use-area icon font**, not FontAwesome, with no
  discoverable PDF-specific glyph in it. Reverse-engineering an unknown
  custom font's available glyphs was out of scope for what was actually
  asked (the grid view, with its visible image previews) — **the list view
  is a known, separate limitation this plugin does not cover.**
- FontAwesome's `fa-file-pdf` icon **is** available and already loaded on
  this exact page — confirmed both that the class exists in the bundled
  `fontawesome.css`, and that the identical pattern is already used by
  Joomla core itself for video/audio files (`fas fa-file-video` /
  `fas fa-file-audio`, same `MediaBrowserItem*` component family). No new
  asset to load, just reusing what's already active.

## How it works

There's no supported override system for this compiled Vue SPA (no
"Template > Overrides" equivalent, and no plugin/config hook into
`itemType()`'s dispatch logic), so this uses the only mechanism actually
available: a small `MutationObserver`-based DOM patch, injected only on
the `com_media` admin page (`onBeforeCompileHead`, checked against
`option=com_media`), that:

1. Finds `.media-browser-doc` items (Joomla's own class for the generic
   document bucket) not yet checked (`data-pdf-icon-checked` guard, so it
   doesn't reprocess the same items on every grid re-render).
2. Reads the item's name from `.media-browser-item-info`'s `title`
   attribute (falls back to its text content).
3. If the name ends in `.pdf` (case-insensitive, word-boundary-safe —
   won't false-positive on `report.pdfx` or `my.pdf.backup.txt`), swaps
   the icon's `fa-file` class for `fa-file-pdf`.
4. Observes the grid container for further mutations (folder navigation,
   uploads, deletes all re-render the Vue tree), re-running the same
   check — so newly uploaded PDFs get the icon too, not just what was on
   screen at page load.

Legacy CMSPlugin format on purpose (same reasoning as the other custom
plugins in the project): loads via PluginHelper without a namespace map,
so it works immediately after a CLI `extension:discover:install`.

## Installation

**Packaged zip:** zip the repo contents (manifest `mediapdficon.xml` at
the zip root) → Joomla admin **System → Install → Upload Package File**.

**From source (dev):** copy `mediapdficon/` into
`<joomla>/plugins/system/mediapdficon/`, then install via
**System → Manage → Discover**, then enable it in **System → Manage →
Plugins**.

## License

GNU General Public License version 2 or later — see `LICENSE`.
