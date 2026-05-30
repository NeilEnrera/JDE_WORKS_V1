<?php
/**
 * DiscountSystem Helper
 * Centralizes the logic for both Automatic Bulk Discounts and Admin Overrides.
 */
class DiscountSystem {
    /**
     * calculateItemDiscount
     * @param int $qty Total quantity of a specific product
     * @param float $price Unit price of the product
     * @param array|null $product Optional product data (if we have overrides)
     * @return array [percentage, amount, isOverride, minQty]
     */
    public static function calculateItemDiscount($qty, $price, $product = null) {
        $pct = 0;
        $isOverride = false;
        $minQtyUsed = 0;

        // Apply Admin-Defined Discount only
        if ($product && isset($product['customDiscountPercent']) && $product['customDiscountPercent'] !== null && $product['customDiscountPercent'] > 0) {
            $customMin = isset($product['customMinQty']) ? intval($product['customMinQty']) : 1;
            if ($qty >= $customMin) {
                $pct = floatval($product['customDiscountPercent']);
                $isOverride = true;
                $minQtyUsed = $customMin;
            }
        }

        // Hard Limit (Safety)
        if ($pct > 30) $pct = 30;

        $discountAmount = ($price * $qty) * ($pct / 100);

        return [
            'percentage' => $pct,
            'amount' => $discountAmount,
            'isOverride' => $isOverride,
            'minQty' => $minQtyUsed,
            'baseTotal' => ($price * $qty),
            'finalTotal' => ($price * $qty) - $discountAmount
        ];
    }
}
