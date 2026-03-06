document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
});

async function loadDashboard() {
    try {
        const response = await fetch('controllers/POSController.php?action=getDashboardStats');
        const result = await response.json();

        if (!result.success || !result.data) {
            showDashboardError('Failed to load dashboard data');
            return;
        }

        const data = result.data;
        updateStatCards(data.totals || {});
        renderSalesChart(data.sales_history || []);
        renderTopItems(data.top_items || []);
        renderRecentOrders(data.recent_orders || []);
    } catch (error) {
        console.error('Error loading dashboard:', error);
        showDashboardError('Error loading dashboard data');
    }
}

function updateStatCards(totals) {
    const totalOrdersEl = document.getElementById('stat-total-orders');
    const totalSalesEl = document.getElementById('stat-total-sales');
    const avgOrderEl = document.getElementById('stat-average-order');
    const openOrdersEl = document.getElementById('stat-open-orders');

    if (totalOrdersEl) {
        totalOrdersEl.textContent = formatNumber(totals.total_orders || 0);
    }
    if (totalSalesEl) {
        totalSalesEl.textContent = formatCurrency(totals.total_sales || 0);
    }
    if (avgOrderEl) {
        const value = totals.average_order_value || 0;
        avgOrderEl.textContent = value > 0 ? formatCurrency(value) : '₱0.00';
    }
    if (openOrdersEl) {
        openOrdersEl.textContent = (totals.open_orders || 0) + ' open orders';
    }
}

function renderSalesChart(history) {
    const canvas = document.getElementById('salesChart');
    const emptyState = document.getElementById('salesChartEmpty');

    if (!canvas) return;

    if (!history.length) {
        canvas.style.display = 'none';
        if (emptyState) {
            emptyState.style.display = 'block';
        }
        return;
    }

    canvas.style.display = 'block';
    if (emptyState) {
        emptyState.style.display = 'none';
    }

    const labels = history.map(row => {
        // row.sale_date is YYYY-MM-DD
        try {
            const d = new Date(row.sale_date);
            if (!isNaN(d.getTime())) {
                return d.toLocaleDateString(undefined, {
                    month: 'short',
                    day: 'numeric'
                });
            }
        } catch (e) {
            // ignore parse errors
        }
        return row.sale_date;
    });

    const values = history.map(row => Number(row.total_sales) || 0);

    const ctx = canvas.getContext('2d');

    // Destroy existing chart instance if re-rendering
    if (window._salesChart) {
        window._salesChart.destroy();
    }

    window._salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Sales',
                data: values,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.12)',
                borderWidth: 2,
                fill: true,
                tension: 0.35,
                pointRadius: 3,
                pointBackgroundColor: '#2563eb'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: context => formatCurrency(context.parsed.y || 0)
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => formatCurrency(value)
                    }
                }
            }
        }
    });
}

function renderTopItems(items) {
    const container = document.getElementById('topItems');
    if (!container) return;

    if (!items.length) {
        container.innerHTML = '<div class="chart-empty">No top items yet for today.</div>';
        return;
    }

    const maxQty = Math.max(...items.map(i => Number(i.total_quantity) || 0), 1);

    container.innerHTML = '';
    items.forEach(item => {
        const qty = Number(item.total_quantity) || 0;
        const revenue = Number(item.total_revenue) || 0;
        const width = Math.round((qty / maxQty) * 100);

        const card = document.createElement('div');
        card.className = 'top-item-card';
        card.innerHTML = `
            <div class="item-info">
                <div class="item-name">${escapeHtml(item.ItemName || 'Unknown Item')}</div>
                <div class="item-qty">${qty} orders</div>
            </div>
            <div class="item-progress">
                <div class="progress-bar" style="width: ${width}%;"></div>
            </div>
            <div class="item-revenue">${formatCurrency(revenue)}</div>
        `;
        container.appendChild(card);
    });
}

function renderRecentOrders(orders) {
    const container = document.getElementById('recentOrders');
    if (!container) return;

    if (!orders.length) {
        container.innerHTML = '<div class="recent-empty">No recent orders yet.</div>';
        return;
    }

    container.innerHTML = '';

    orders.forEach(order => {
        const item = document.createElement('div');
        item.className = 'recent-order-item';

        const status = String(order.Status || '').toLowerCase();
        const pillClass = status === 'paid' ? 'paid'
            : status === 'ready' ? 'ready'
            : 'pending';

        const orderDate = order.OrderDate ? new Date(order.OrderDate) : null;
        const timeLabel = orderDate && !isNaN(orderDate.getTime())
            ? orderDate.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })
            : '';

        item.innerHTML = `
            <div>
                <div class="order-id">#${escapeHtml(order.OrderNumber || String(order.OrderID || ''))}</div>
                <div class="order-time">${escapeHtml(order.TypeName || '')}${timeLabel ? ' · ' + timeLabel : ''}</div>
            </div>
            <div style="text-align: right;">
                <div class="order-amount">${formatCurrency(order.TotalAmount || 0)}</div>
                <div class="status-pill ${pillClass}">${escapeHtml(order.Status || '')}</div>
            </div>
        `;

        container.appendChild(item);
    });
}

function showDashboardError(message) {
    const page = document.querySelector('.dashboard-page');
    if (!page) return;

    page.innerHTML = `
        <div class="loading-spinner">
            ${escapeHtml(message)}
        </div>
    `;
}

function formatCurrency(value) {
    const num = Number(value) || 0;
    return '₱' + num.toFixed(2);
}

function formatNumber(value) {
    return (Number(value) || 0).toLocaleString();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = String(text ?? '');
    return div.innerHTML;
}


