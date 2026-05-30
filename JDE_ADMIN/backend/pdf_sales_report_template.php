<?php
// Company Info
$companyName = "JDE Work of Our Hands";
$companyAddress = "Hilltop Branch<br>11 Esperanza, Novaliches<br>Hilltop Subd. Greater Lagro<br>Quezon City";
$companyPhone = "+63 912 345 6789";
$companyEmail = "jdeworks0@gmail.com";

// Calculate Dynamic Date Display for the header based on filter inputs
$dateDisplay = "";
if ($startDate && $endDate) {
    $dateDisplay = date('F j, Y', strtotime($startDate)) . ' - ' . date('F j, Y', strtotime($endDate));
} elseif ($range === 'daily') {
    $dateDisplay = date('F j, Y', strtotime($date));
} elseif ($range === 'monthly') {
    $dateDisplay = date('F 1, Y', mktime(0, 0, 0, $month, 1, $year)) . ' - ' . date('F t, Y', mktime(0, 0, 0, $month, 1, $year));
} else {
    $dateDisplay = "January 1, $year - December 31, $year";
}

// Logo Base64
$logoPath = __DIR__ . '/../assets/img/logo.png';
$logoData = '';
if (file_exists($logoPath)) {
    $type = pathinfo($logoPath, PATHINFO_EXTENSION);
    $data = file_get_contents($logoPath);
    $logoData = 'data:image/' . $type . ';base64,' . base64_encode($data);
}
?>

<style>
    /* Print & Pagination Optimizations */
    .pdf-container {
        width: 190mm;
        /* A4 width minus margins */
        margin: 0 auto;
        background: white;
        padding: 5mm;
        font-family: 'Inter', Arial, sans-serif;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        page-break-inside: auto;
    }

    thead {
        display: table-header-group;
        /* Repeat header on every page */
    }

    tfoot {
        display: table-footer-group;
        /* Show footer at end of table */
    }

    tr {
        page-break-inside: avoid;
        break-inside: avoid;
        /* Prevent row from splitting */
    }

    .signature-area {
        page-break-inside: avoid;
        /* Keep signature block together */
        margin-top: 40px;
    }
</style>

<div class="pdf-container">

    <!-- Centered Report Title -->
    <!-- Centered Report Title -->
    <h1
        style="text-align: center; font-size: 26px; margin: 0 0 25px 0; font-weight: 800; color: #012B43; text-transform: uppercase; letter-spacing: 1px;">
        Sales Report</h1>

    <!-- Header Section -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <!-- Company Details -->
            <td style="width: 60%; vertical-align: top; padding: 0;">
                <div style="font-weight: bold; font-size: 16px; margin-bottom: 8px; color: #012B43;">
                    <?php echo $companyName; ?>
                </div>
                <div style="font-size: 11px; color: #333; line-height: 1.4;">
                    <?php echo $companyAddress; ?><br><br>
                    <?php echo $companyPhone; ?><br>
                    <?php echo $companyEmail; ?>
                </div>
            </td>
            <!-- Logo and Range -->
            <td style="width: 40%; vertical-align: top; text-align: right; padding: 0;">
                <?php if ($logoData): ?>
                    <img src="<?php echo $logoData; ?>"
                        style="max-width: 150px; height: auto; border-radius: 12px; border: 2px solid #e2e8f0; padding: 8px; margin-bottom: 12px; display: inline-block; object-fit: contain;"
                        alt="Logo">
                <?php endif; ?>
                <div style="font-size: 11px; margin-top: 5px; color: #444;">
                    <strong>Date Range:</strong><br>
                    <?php echo $dateDisplay; ?>
                </div>
            </td>
        </tr>
    </table>

    <!-- Data Table -->
    <table
        style="width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 20px; border: 1px solid black; page-break-inside: auto;">
        <thead>
            <tr
                style="background-color: #012B43 !important; color: #ffffff !important; -webkit-print-color-adjust: exact;">
                <th
                    style="border: 1px solid #000000; padding: 12px 10px; text-align: left; font-weight: 800; width: 15%; color: #ffffff !important; font-size: 12px; text-transform: uppercase;">
                    Date</th>
                <th
                    style="border: 1px solid #000000; padding: 12px 10px; text-align: left; font-weight: 800; width: 30%; color: #ffffff !important; font-size: 12px; text-transform: uppercase;">
                    Product Name</th>
                <th
                    style="border: 1px solid #000000; padding: 12px 10px; text-align: center; font-weight: 800; width: 10%; color: #ffffff !important; font-size: 12px; text-transform: uppercase;">
                    Qty</th>
                <th
                    style="border: 1px solid #000000; padding: 12px 10px; text-align: left; font-weight: 800; width: 22%; color: #ffffff !important; font-size: 12px; text-transform: uppercase;">
                    Customer</th>
                <th
                    style="border: 1px solid #000000; padding: 12px 10px; text-align: center; font-weight: 800; width: 10%; color: #ffffff !important; font-size: 12px; text-transform: uppercase;">
                    Method</th>
                <th
                    style="border: 1px solid #000000; padding: 12px 10px; text-align: right; font-weight: 800; width: 13%; color: #ffffff !important; font-size: 12px; text-transform: uppercase;">
                    Paid</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sales)): ?>
                <tr>
                    <td colspan="6"
                        style="border: 1px solid black; padding: 30px; text-align: center; font-style: italic; color: #999;">
                        No transactions found for the specified period.
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
                            $productCount = (int) ($piRow['productCount'] ?? 1);
                            if ($productCount > 1) {
                                $prodName = htmlspecialchars($allNames[0]) . ' (+' . ($productCount - 1) . ' more)';
                            } else {
                                $prodName = htmlspecialchars($allNames[0] ?? 'Custom Tailoring');
                            }
                            $qty = $piRow['totalQty'] ?? 1;
                        }
                    }

                    $methodName = $methodMap[$sale['paymentMethodID'] ?? 0] ?? 'Other';
                    $formattedDate = date('m/d/Y', strtotime($sale['dateCreated']));
                    ?>
                    <tr style="page-break-inside: avoid;">
                        <td style="border: 1px solid black; padding: 8px 10px; text-align: left;">
                            <?php echo $formattedDate; ?>
                        </td>
                        <td style="border: 1px solid black; padding: 8px 10px; text-align: left;">
                            <?php echo $prodName; ?>
                        </td>
                        <td style="border: 1px solid black; padding: 8px 10px; text-align: center;">
                            <?php echo $qty; ?>
                        </td>
                        <td style="border: 1px solid black; padding: 8px 10px; text-align: left;">
                            <?php echo htmlspecialchars($sale['firstName'] . ' ' . $sale['lastName']); ?>
                        </td>
                        <td style="border: 1px solid black; padding: 8px 10px; text-align: center;">
                            <?php echo $methodName; ?>
                        </td>
                        <td style="border: 1px solid black; padding: 8px 10px; text-align: right; color: #333;">
                            ₱<?php echo number_format($sale['fullPaymentAmount'], 2); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #e2e8f0; font-weight: bold; font-size: 14px; page-break-inside: avoid;">
                <td colspan="5"
                    style="border: 1px solid black; padding: 14px 10px; text-align: right; text-transform: uppercase;">
                    Total Collected
                </td>
                <td style="border: 1px solid black; padding: 14px 10px; text-align: right; color: #012B43;">
                    ₱<?php echo number_format($totalRevenue, 2); ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Signature Area -->
    <div class="signature-area">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 50%; vertical-align: bottom;">
                    <div style="font-size: 11px; color: #444;">
                        <span style="font-weight: bold;">System Generated By</span><br>
                        JDE Works Management Portal
                    </div>
                </td>
                <td style="width: 50%; vertical-align: bottom; text-align: right;">
                    <div style="display: inline-block; width: 220px; text-align: center;">
                        <div style="border-bottom: 1px solid black; margin-bottom: 8px; height: 30px;"></div>
                        <span style="font-size: 11px; font-weight: bold; color: #444;">Name / Signature</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Final Note -->
    <div
        style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; font-size: 10px; color: #666; text-align: center;">
        This document is an official Sales Report generated from the JDE WORKS management system. Confidential.
    </div>

</div>