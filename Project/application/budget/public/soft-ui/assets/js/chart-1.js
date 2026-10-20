// chart 1

var chartBarsEl = document.getElementById("chart-bars");
if (!chartBarsEl || typeof Chart === "undefined") {
  // chart canvas does not exist on this page
} else {
  var existingChartBars = Chart.getChart(chartBarsEl);
  if (existingChartBars) existingChartBars.destroy();
  var ctx = chartBarsEl.getContext("2d");

  new Chart(ctx, {
  type: "bar",
  data: {
    labels: ["Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
    datasets: [
      {
        label: "Sales",
        tension: 0.4,
        borderWidth: 0,
        borderRadius: 4,
        borderSkipped: false,
        backgroundColor: "#fff",
        data: [450, 200, 100, 220, 500, 100, 400, 230, 500],
        maxBarThickness: 6,
      },
    ],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false,
      },
    },
    interaction: {
      intersect: false,
      mode: "index",
    },
    scales: {
      y: {
        grid: {
          drawBorder: false,
          display: false,
          drawOnChartArea: false,
          drawTicks: false,
        },
        ticks: {
          suggestedMin: 0,
          suggestedMax: 600,
          beginAtZero: true,
          padding: 15,
          font: {
            size: 14,
            family: "Open Sans",
            style: "normal",
            lineHeight: 2,
          },
          color: "#fff",
        },
      },
      x: {
        grid: {
          drawBorder: false,
          display: false,
          drawOnChartArea: false,
          drawTicks: false,
        },
        ticks: {
          display: false,
        },
      },
    },
  },
  });
}

// end chart 1
