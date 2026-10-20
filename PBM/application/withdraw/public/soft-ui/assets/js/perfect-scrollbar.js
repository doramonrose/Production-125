(function () {
  // Soft UI originally binds PerfectScrollbar to <main> on Windows.
  // This app uses native document flow and AJAX-rendered lists, so that
  // binding clips new content (overflow:hidden) until a manual scroll/reflow.
  // Sidenav already uses [overflow-y:auto] to avoid this plugin.
})();
