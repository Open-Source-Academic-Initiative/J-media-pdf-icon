<?php

/**
 * @package    plg_system_mediapdficon
 * @brief      Shows a PDF-specific icon for PDF files in the Media Manager
 *             GRID view, instead of the generic document icon Joomla core
 *             uses for every non-image/video/audio file type.
 *
 * Traced Joomla's own compiled Vue SPA (media/com_media/js/media-manager.js)
 * to confirm this is a genuine core gap, not something our other plugins
 * introduced: the grid's item-type dispatcher (`itemType()`) routes any
 * extension in `doc_extensions` (doc,odg,odp,ods,odt,pdf,ppt,txt,xcf,xls,csv
 * — see com_media's own component params) to `MediaBrowserItemDocument`,
 * which unconditionally renders `<span class="fas fa-file">` — no
 * per-extension distinction at all within that bucket. The LIST view's CSS
 * (media/com_media/css/media-manager.css) groups doc/xls/pdf/txt under the
 * exact same `content` glyph too, but via a custom private-use-area icon
 * font (not FontAwesome) with no discoverable PDF-specific glyph — reverse
 * engineering that font was out of scope; this only fixes the GRID view.
 *
 * FontAwesome ("fas fa-file-pdf") is already loaded and already used this
 * same way for video/audio files (`fas fa-file-video` / `fas fa-file-audio`
 * — confirmed in the same compiled JS), so no new asset needs loading, just
 * reusing what's already active on this exact page.
 *
 * There is no supported override system for this compiled Vue SPA (no
 * "Template > Overrides" equivalent, unlike Joomla's traditional MVC
 * views) and no plugin/config hook into `itemType()`'s dispatch logic, so
 * this uses the only mechanism actually available: a small
 * MutationObserver-based DOM patch, scoped strictly to the `com_media`
 * admin page, that swaps the icon class on ".media-browser-doc" items
 * whose filename ends in ".pdf". Re-runs on every DOM mutation inside the
 * browser grid (Vue re-renders on navigation/upload/delete), guarded by a
 * per-element "already checked" flag so it doesn't reprocess unchanged
 * items on every mutation.
 *
 * Legacy CMSPlugin format on purpose: loads via PluginHelper without a
 * namespace map, so it works immediately after a CLI
 * `extension:discover:install`.
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

class PlgSystemMediapdficon extends CMSPlugin
{
    protected $autoloadLanguage = false;

    public function onBeforeCompileHead()
    {
        $app = Factory::getApplication();

        if (!$app->isClient('administrator')) {
            return;
        }

        if ($app->getInput()->getCmd('option') !== 'com_media') {
            return;
        }

        $doc = $app->getDocument();

        if (!$doc || $doc->getType() !== 'html') {
            return;
        }

        $doc->addScriptDeclaration($this->buildJs());
    }

    private function buildJs(): string
    {
        return <<<'JS'
(function () {
    function fixPdfIcons(root) {
        var items = root.querySelectorAll('.media-browser-doc:not([data-pdf-icon-checked])');

        items.forEach(function (item) {
            item.setAttribute('data-pdf-icon-checked', '1');

            var info = item.querySelector('.media-browser-item-info');
            var name = info ? (info.getAttribute('title') || info.textContent || '') : '';

            if (!/\.pdf(\s|$)/i.test(name.trim())) {
                return;
            }

            var icon = item.querySelector('.file-icon .fa-file');

            if (icon) {
                icon.classList.remove('fa-file');
                icon.classList.add('fa-file-pdf');
            }
        });
    }

    function start() {
        var container = document.querySelector('.media-browser') || document.body;

        fixPdfIcons(container);

        new MutationObserver(function () {
            fixPdfIcons(container);
        }).observe(container, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
JS;
    }
}
