<?php
include __DIR__ . '/fragments/header.php';
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<!-- Flatpickr (Custom Date Picker) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link rel="stylesheet" href="../css/sales_report.css">

<div class="sr-page">

    <!-- Page Header -->
    <div class="sr-page-header">
        <div>
            <h2>Sales Report</h2>
            <p><?php echo htmlspecialchars($title); ?></p>
        </div>
        <div class="sr-header-actions">
            <button onclick="openPDF()" class="btn-sr btn-sr-pdf">
                <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <form class="sr-filters" method="GET" action="sales_report.php" id="filterForm">
        <div class="sr-filters-top">
            <!-- Primary Filters (Report Type, Month, Year) -->
            <div class="sr-filter-group sr-filter-primary">
                <div class="fg">
                    <label>Report Type</label>
                    <select name="range" id="paramRange">
                        <option value="daily"   <?php echo ($range==='daily')   ? 'selected':'' ?>>Daily</option>
                        <option value="monthly" <?php echo ($range==='monthly') ? 'selected':'' ?>>Monthly</option>
                        <option value="yearly"  <?php echo ($range==='yearly')  ? 'selected':'' ?>>Yearly</option>
                    </select>
                </div>

                <!-- Daily -->
                <div class="fg range-daily" <?php echo $range!=='daily'   ? 'style="display:none"':'' ?>>
                    <label>Select Date</label>
                    <div class="input-with-icon">
                        <i class="bi bi-calendar2-day"></i>
                        <input type="text" name="date" id="datePicker" class="admin-datepicker" value="<?php echo htmlspecialchars($date); ?>" placeholder="Select date">
                    </div>
                </div>

                <!-- Monthly -->
                <div class="fg range-monthly" <?php echo $range!=='monthly' ? 'style="display:none"':'' ?>>
                    <label>Month</label>
                    <select name="month">
                        <?php for($i=1;$i<=12;$i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($month==$i)?'selected':'' ?>>
                                <?php echo date('F', mktime(0,0,0,$i,1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Month year / Yearly  -->
                <div class="fg range-monthly range-yearly" <?php echo !in_array($range,['monthly','yearly']) ? 'style="display:none"':'' ?>>
                    <label>Year</label>
                    <select name="yearMonth" id="yearSelect">
                        <?php for($i=(int)date('Y');$i>=2020;$i--): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($year==$i)?'selected':'' ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                    <!-- hidden duplicate for yearly route -->
                    <input type="hidden" name="year" id="yearHidden" value="<?php echo $year; ?>">
                </div>
            </div>

            <!-- Divider -->
            <div class="sr-filter-divider range-monthly range-yearly" <?php echo !in_array($range,['monthly','yearly']) ? 'style="display:none"':'' ?>></div>

            <!-- Secondary Filters (Custom Range) -->
            <div class="sr-filter-group sr-filter-secondary range-monthly range-yearly" <?php echo !in_array($range,['monthly','yearly']) ? 'style="display:none"':'' ?>>
                <div class="fg">
                    <label>Start Date <span class="fw-normal text-muted text-lowercase">(Optional)</span></label>
                    <div class="input-with-icon">
                        <i class="bi bi-calendar-event"></i>
                        <input type="text" name="startDate" id="startDatePicker" class="admin-datepicker" value="<?php echo htmlspecialchars($startDate); ?>" placeholder="From">
                    </div>
                </div>
                <div class="fg">
                    <label>End Date <span class="fw-normal text-muted text-lowercase">(Optional)</span></label>
                    <div class="input-with-icon">
                        <i class="bi bi-calendar-event"></i>
                        <input type="text" name="endDate" id="endDatePicker" class="admin-datepicker" value="<?php echo htmlspecialchars($endDate); ?>" placeholder="To">
                    </div>
                </div>
            </div>
            <!-- Action Buttons -->
            <div class="sr-filter-group sr-filter-actions">
                <button type="submit" class="btn-filter">
                    <i class="bi bi-funnel-fill"></i> Apply Filter
                </button>
            </div>
        </div>
    </form>

    <!-- KPI Cards -->
    <div class="sr-kpi-grid">
        <div class="kpi-card kpi-blue">
            <div class="kpi-icon"><i class="bi bi-receipt"></i></div>
            <div class="kpi-label">Transactions</div>
            <div class="kpi-value"><?php echo count($sales); ?></div>
            <div class="kpi-sub">Verified payments</div>
        </div>
        <div class="kpi-card kpi-green">
            <div class="kpi-icon"><i class="bi bi-cash-stack"></i></div>
            <div class="kpi-label">Gross Revenue</div>
            <div class="kpi-value">₱<?php echo number_format($totalRevenue, 2); ?></div>
            <div class="kpi-sub">Total collected</div>
        </div>
        <div class="kpi-card kpi-orange">
            <div class="kpi-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="kpi-label">Avg. Transaction</div>
            <div class="kpi-value">₱<?php echo count($sales) > 0 ? number_format($totalRevenue / count($sales), 2) : '0.00'; ?></div>
            <div class="kpi-sub">Per order average</div>
        </div>
        <div class="kpi-card kpi-red">
            <div class="kpi-icon"><i class="bi bi-wallet2"></i></div>
            <div class="kpi-label">Payment Methods</div>
            <div class="kpi-value"><?php echo count($methodTotals); ?></div>
            <div class="kpi-sub"><?php echo implode(', ', array_keys($methodTotals)) ?: 'N/A'; ?></div>
        </div>
    </div>



    <!-- Transaction Table -->
    <div class="sr-table-panel" id="printableTable">
        <div class="sr-table-header">
            <p class="sr-panel-title" style="margin:0;"><i class="bi bi-table"></i> Transaction Log</p>
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="tableSearch" placeholder="Search customer or order...">
            </div>
        </div>
        <div class="sr-table-wrap">
            <table class="sr-table" id="salesTable">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Product Name</th>
                        <th style="text-align: center;">Quantity</th>
                        <th>Customer</th>
                        <th>Method</th>
                        <th>Amount Paid</th>
                    </tr>
                </thead>
                <tbody id="salesTableBody">
                <?php if (empty($sales)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="sr-empty">
                                <i class="bi bi-inbox"></i>
                                <p>No verified sales recorded for this period.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sales as $sale): ?>
                    <?php
                    // Dynamically fetch Product Name and Quantity for the order
                    $orderID = (int) $sale['orderID'];
                    $prodName = 'Custom Tailoring';
                    $qty = 1;

                    if (isset($conn)) {
                        $piQuery = "SELECT 
                                        GROUP_CONCAT(DISTINCT COALESCE(p.productName, 'Custom Tailoring') ORDER BY p.productName SEPARATOR '||') as productNames,
                                        SUM(ci.quantity) as totalQty,
                                        COUNT(DISTINCT COALESCE(p.productID, 0)) as productCount
                                    FROM tbl_order o 
                                    JOIN tbl_cartItem ci ON o.cartID = ci.cartID 
                                    LEFT JOIN tbl_product p ON ci.productID = p.productID 
                                    WHERE o.orderID = $orderID";
                        $piRes = $conn->query($piQuery);
                        if ($piRes && $piRow = $piRes->fetch_assoc()) {
                            $allNames = explode('||', $piRow['productNames'] ?? 'Custom Tailoring');
                            $productCount = (int)($piRow['productCount'] ?? 1);
                            if ($productCount > 1) {
                                $prodName = htmlspecialchars($allNames[0]) . ' <span class="text-muted" style="font-size:12px;">+' . ($productCount - 1) . ' more</span>';
                            } else {
                                $prodName = htmlspecialchars($allNames[0] ?? 'Custom Tailoring');
                            }
                            $qty = $piRow['totalQty'] ?? 1;
                        }
                    }
                    ?>
                    <tr>
                        <td><span class="order-id-pill">#ORD-<?php echo str_pad($sale['orderID'], 3, '0', STR_PAD_LEFT); ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($sale['dateCreated'])); ?></td>
                        <td><?php echo $prodName; ?></td>
                        <td style="text-align: center;"><?php echo (int)$qty; ?></td>
                        <td><?php echo htmlspecialchars($sale['firstName'] . ' ' . $sale['lastName']); ?></td>
                        <td><span class="method-badge"><?php echo $methodMap[$sale['paymentMethodID'] ?? 0] ?? 'Other'; ?></span></td>
                        <td class="amount-cell">₱<?php echo number_format($sale['fullPaymentAmount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($sales)): ?>
                <tfoot>
                    <tr>
                        <td colspan="6" class="tfoot-label">TOTAL COLLECTED</td>
                        <td class="tfoot-total">₱<?php echo number_format($totalRevenue, 2); ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

</div><!-- .sr-page -->

<?php include __DIR__ . '/fragments/footer.php'; ?>

<script>
    // Export formatted filename from PHP to JS securely to avoid breaking strictly typed JS extraction
    window.SALES_REPORT_FILENAME = 'Sales_Report_<?php echo addslashes(str_replace([' ', '—', '/'], '_', $title ?? '')); ?>.pdf';
</script>
<script src="../js/sales_report.js"></script>

<!-- Hidden PDF Source (Populated Server-Side) -->
<div id="hiddenPDFSource" style="position: absolute; left: -9999px; width: 800px; background: white; pointer-events: none;">
    <?php include __DIR__ . '/../backend/pdf_sales_report_template.php'; ?>
</div>

<!-- html2pdf.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

