document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    const btnReset = document.getElementById('btnResetDashboard');
    if (btnReset) {
        btnReset.addEventListener('click', resetDashboard);
    }
});

async function resetDashboard() {
    if (!confirm('Reset dashboard to zero? This will clear data/sales.json and mark all Paid orders as Deleted. Reports and dashboard will show zero. This cannot be undone.')) {
        return;
    }
    try {
        const response = await fetch('controllers/POSController.php?action=resetDashboard');
        const result = await response.json();
        if (result.success) {
            alert(result.message || 'Dashboard reset to zero.');
            loadDashboard();
        } else {
            alert('Failed: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Reset error:', error);
        alert('Error resetting dashboard.');
    }
}

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
    const weekSalesEl = document.getElementById('stat-week-sales');
    const overallSalesEl = document.getElementById('stat-overall-sales');
    const openOrdersEl = document.getElementById('stat-open-orders');

    if (totalOrdersEl) {
        totalOrdersEl.textContent = formatNumber(totals.total_orders || 0);
    }
    if (totalSalesEl) {
        totalSalesEl.textContent = formatCurrency(totals.total_sales || 0);
    }
    if (weekSalesEl) {
        weekSalesEl.textContent = formatCurrency(totals.week_sales || 0);
    }
    if (overallSalesEl) {
        overallSalesEl.textContent = formatCurrency(totals.overall_sales || 0);
    }
    if (openOrdersEl) {
        openOrdersEl.textContent = (totals.open_orders || 0) + ' open orders';
    }
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
            : status === 'deleted' ? 'deleted'
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


