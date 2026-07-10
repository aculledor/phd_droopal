(function () {
  function findBoxplotLibrary() {
    return window.ChartBoxPlot
      || window.chartjsChartBoxplot
      || window.ChartjsChartBoxplot
      || window['chartjs-chart-boxplot'];
  }

  function registerBoxplot() {
    if (!window.Chart) {
      console.warn('[citius_analytics] Chart.js not available.');
      return;
    }

    var boxplot = findBoxplotLibrary();

    if (!boxplot) {
      console.warn('[citius_analytics] Boxplot library global not found.');
      console.log(
        '[citius_analytics] possible globals:',
        Object.keys(window).filter(function (k) {
          return k.toLowerCase().includes('box') || k.toLowerCase().includes('violin');
        })
      );
      return;
    }

    if (window.Chart.registry.controllers.items.boxplot) {
      console.log('[citius_analytics] Boxplot already registered.');
      return;
    }

    var items = [
      boxplot.BoxPlotController,
      boxplot.BoxAndWiskers,
      boxplot.ViolinController,
      boxplot.Violin
    ].filter(Boolean);

    if (!items.length) {
      console.warn('[citius_analytics] No registerable boxplot items found.', boxplot);
      return;
    }

    window.Chart.register.apply(window.Chart, items);
    console.log('[citius_analytics] Boxplot registered.', items);
  }

  registerBoxplot();
})();