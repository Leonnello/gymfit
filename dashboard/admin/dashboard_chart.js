document.addEventListener("DOMContentLoaded", () => {
  const ctx = document.getElementById("dashboardChart").getContext("2d");

  fetch("get_dashboard_data.php")
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        console.error("Error loading chart data:", data.error);
        return;
      }

      new Chart(ctx, {
        type: "bar",
        data: {
          labels: ["Sign-up Requests", "Members", "Staff", "Payments", "Inventory", "Analytics"],
          datasets: [{
            label: "Gym Overview",
            data: [
              data.signups,
              data.members,
              data.staff,
              data.payments,
              data.inventory,
              data.analytics
            ],
            backgroundColor: [
              "rgba(255, 99, 132, 0.6)",
              "rgba(54, 162, 235, 0.6)",
              "rgba(255, 206, 86, 0.6)",
              "rgba(75, 192, 192, 0.6)",
              "rgba(153, 102, 255, 0.6)",
              "rgba(255, 159, 64, 0.6)"
            ],
            borderColor: [
              "rgba(255, 99, 132, 1)",
              "rgba(54, 162, 235, 1)",
              "rgba(255, 206, 86, 1)",
              "rgba(75, 192, 192, 1)",
              "rgba(153, 102, 255, 1)",
              "rgba(255, 159, 64, 1)"
            ],
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { display: false },
            title: {
              display: true,
              text: "Gym Overview Dashboard Statistics"
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { stepSize: 1 }
            }
          }
        }
      });
    })
    .catch(error => console.error("Fetch error:", error));
});
