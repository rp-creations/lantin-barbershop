/**
 * LANTIN Barbershop Management System - Charts Library
 * Contains reusable chart functions for the dashboard
 */

class DashboardCharts {
    constructor() {
        this.colors = {
            primary: '#6366f1',
            secondary: '#8b5cf6',
            success: '#10b981',
            warning: '#f59e0b',
            danger: '#ef4444',
            info: '#3b82f6'
        };
    }

    // Initialize all charts on the dashboard
    initDashboardCharts() {
        this.initSalesChart();
        this.initInventoryChart();
        this.initBarberPerformanceChart();
    }

    // Sales Trend Chart
    initSalesChart() {
        const ctx = document.getElementById('salesChart');
        if (!ctx) return;

        const salesData = {
            labels: ['6am', '9am', '12pm', '3pm', '6pm', '9pm'],
            datasets: [{
                label: 'Today\'s Sales',
                data: [1200, 4500, 7200, 8900, 10500, 12500],
                borderColor: this.colors.primary,
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4,
                fill: true
            }]
        };

        new Chart(ctx, {
            type: 'line',
            data: salesData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // Inventory Status Chart
    initInventoryChart() {
        const ctx = document.getElementById('inventoryChart');
        if (!ctx) return;

        const inventoryData = {
            labels: ['In Stock', 'Low Stock', 'Out of Stock'],
            datasets: [{
                data: [15, 3, 2],
                backgroundColor: [
                    this.colors.success,
                    this.colors.warning,
                    this.colors.danger
                ],
                borderWidth: 2
            }]
        };

        new Chart(ctx, {
            type: 'doughnut',
            data: inventoryData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Barber Performance Chart
    initBarberPerformanceChart() {
        const ctx = document.getElementById('barberPerformanceChart');
        if (!ctx) return;

        const performanceData = {
            labels: ['Mark N.', 'Carl S.', 'Denzel C.', 'Julius T.'],
            datasets: [{
                label: 'Services Today',
                data: [12, 10, 8, 6],
                backgroundColor: this.colors.primary,
                borderColor: this.colors.primary,
                borderWidth: 1
            }]
        };

        new Chart(ctx, {
            type: 'bar',
            data: performanceData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Services'
                        }
                    }
                }
            }
        });
    }

    // Monthly Sales Comparison
    createMonthlyComparisonChart(elementId, currentMonthData, previousMonthData) {
        const ctx = document.getElementById(elementId);
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [
                    {
                        label: 'Current Month',
                        data: currentMonthData,
                        backgroundColor: this.colors.primary
                    },
                    {
                        label: 'Previous Month',
                        data: previousMonthData,
                        backgroundColor: this.colors.secondary
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // Service Popularity Chart
    createServicePopularityChart(elementId, services, counts) {
        const ctx = document.getElementById(elementId);
        if (!ctx) return;

        new Chart(ctx, {
            type: 'polarArea',
            data: {
                labels: services,
                datasets: [{
                    data: counts,
                    backgroundColor: [
                        this.colors.primary,
                        this.colors.secondary,
                        this.colors.success,
                        this.colors.warning,
                        this.colors.info
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });
    }
}

// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    const charts = new DashboardCharts();
    charts.initDashboardCharts();
    
    // Example usage of other charts
    // charts.createMonthlyComparisonChart('monthlyComparisonChart', [5000, 7000, 8000, 9000], [4500, 6500, 7500, 8500]);
    // charts.createServicePopularityChart('servicePopularityChart', ['Haircut', 'Haircut Pro', 'Haircut Deluxe'], [45, 30, 25]);
});

// Utility function to format currency
function formatCurrency(amount) {
    return '₱' + parseFloat(amount).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { DashboardCharts, formatCurrency };
}