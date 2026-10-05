// runs before the page paints so a dark theme never flashes white first.
// the choice is light, dark or system; system follows the device setting live.
(function () {
    var stored = null;
    try { stored = localStorage.getItem('theme'); } catch (e) {}
    var choice = stored === 'light' || stored === 'dark' ? stored : 'system';
    var media = window.matchMedia('(prefers-color-scheme: dark)');
    function apply() {
        var dark = choice === 'dark' || (choice === 'system' && media.matches);
        document.documentElement.dataset.theme = dark ? 'dark' : 'light';
        document.documentElement.dataset.themeChoice = choice;
    }
    apply();
    media.addEventListener('change', function () { if (choice === 'system') apply(); });
    window.__setTheme = function (next) {
        choice = next;
        try { next === 'system' ? localStorage.removeItem('theme') : localStorage.setItem('theme', next); } catch (e) {}
        apply();
    };
})();
