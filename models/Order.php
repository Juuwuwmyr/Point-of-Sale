<?php

class Order {
    private $conn;
    private $table_name = "orders";
    
    public $OrderID;
    public $OrderNumber;
    public $OrderDate;
    public $OrderTypeID;
    public $UserID;
    public $TableNumber;
    public $CustomerName;
    public $Status;
    public $TotalAmount;
    public $Notes;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (OrderNumber, OrderDate, OrderTypeID, UserID, TableNumber, CustomerName, Status, TotalAmount, Notes) 
                  VALUES (:ordernumber, NOW(), :ordertypeid, :userid, :tablenumber, :customername, :status, :totalamount, :notes)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->OrderNumber = $this->generateOrderNumber();
        
        $stmt->bindParam(":ordernumber", $this->OrderNumber);
        $stmt->bindParam(":ordertypeid", $this->OrderTypeID);
        $stmt->bindParam(":userid", $this->UserID);
        $stmt->bindParam(":tablenumber", $this->TableNumber);
        $stmt->bindParam(":customername", $this->CustomerName);
        $stmt->bindParam(":status", $this->Status);
        $stmt->bindParam(":totalamount", $this->TotalAmount);
        $stmt->bindParam(":notes", $this->Notes);
        
        if($stmt->execute()) {
            $this->OrderID = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    private function generateOrderNumber() {
        $date = date('YmdHis');
        $random = mt_rand(10, 99);
        return "ORD-" . $date . $random;
    }
    
    public function addOrderItem($itemId, $quantity, $unitPrice, $notes = "") {
        $query = "INSERT INTO orderdetails (OrderID, ItemID, Quantity, UnitPrice, Notes) 
                  VALUES (:orderid, :itemid, :quantity, :unitprice, :notes)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":orderid", $this->OrderID);
        $stmt->bindParam(":itemid", $itemId);
        $stmt->bindParam(":quantity", $quantity);
        $stmt->bindParam(":unitprice", $unitPrice);
        $stmt->bindParam(":notes", $notes);
        
        return $stmt->execute();
    }

    public function archiveAndDeleteSalesForDate(string $date): array
    {
        $this->conn->beginTransaction();
        try {
            // Archive only PAID orders
            $paidOrdersStmt = $this->conn->prepare(
                "SELECT OrderID, OrderDate, TotalAmount FROM " . $this->table_name . " WHERE DATE(OrderDate) = :d AND Status = 'Paid'"
            );
            $paidOrdersStmt->bindParam(':d', $date);
            $paidOrdersStmt->execute();
            $paidOrders = $paidOrdersStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Identify all orders to delete (Paid or Deleted)
            $delOrdersStmt = $this->conn->prepare(
                "SELECT OrderID FROM " . $this->table_name . " WHERE DATE(OrderDate) = :d AND Status IN ('Paid','Deleted')"
            );
            $delOrdersStmt->bindParam(':d', $date);
            $delOrdersStmt->execute();
            $ordersToDelete = $delOrdersStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $checkCol = $this->conn->query("SHOW COLUMNS FROM orderdetails LIKE 'Status'");
            $hasStatusCol = $checkCol && ($checkCol->fetch(PDO::FETCH_ASSOC) !== false);

            $archiveRecords = [];
            $itemsArchived = 0;

            foreach ($paidOrders as $o) {
                $oid = (int)$o['OrderID'];
                $itemsQuery = "SELECT od.ItemID, mi.ItemName, od.Quantity, od.UnitPrice FROM orderdetails od JOIN menuitems mi ON mi.ItemID = od.ItemID WHERE od.OrderID = :oid";
                if ($hasStatusCol) {
                    $itemsQuery .= " AND (od.Status IS NULL OR od.Status != 'Cancelled')";
                }
                $itemsStmt = $this->conn->prepare($itemsQuery);
                $itemsStmt->bindParam(':oid', $oid);
                $itemsStmt->execute();
                $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $itemsList = [];
                foreach ($items as $it) {
                    $itemsList[] = [
                        'item_id' => (int)$it['ItemID'],
                        'item_name' => $it['ItemName'],
                        'quantity' => (int)$it['Quantity'],
                        'unit_price' => (float)$it['UnitPrice'],
                    ];
                }
                $itemsArchived += count($itemsList);

                $archiveRecords[] = [
                    'order_date' => $o['OrderDate'],
                    'total_amount' => (float)$o['TotalAmount'],
                    'items' => $itemsList,
                ];
            }

            $file = __DIR__ . '/../data/sales.json';
            $existing = [];
            if (file_exists($file)) {
                $raw = file_get_contents($file);
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $existing = $decoded;
                }
            }
            $newData = array_merge($existing, $archiveRecords);
            $ok = file_put_contents($file, json_encode($newData, JSON_PRETTY_PRINT));
            if ($ok === false) {
                throw new Exception('Failed to write archive file');
            }

            $ids = array_column($ordersToDelete, 'OrderID');
            if (empty($ids)) {
                $this->conn->commit();
                return ['archived_orders' => count($paidOrders), 'archived_items' => $itemsArchived];
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $delDetails = $this->conn->prepare("DELETE FROM orderdetails WHERE OrderID IN ($placeholders)");
            $delDetails->execute($ids);

            $delOrders = $this->conn->prepare("DELETE FROM " . $this->table_name . " WHERE OrderID IN ($placeholders)");
            $delOrders->execute($ids);

            $this->conn->commit();
            return ['archived_orders' => count($paidOrders), 'archived_items' => $itemsArchived];
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
    
    public function getOrderItems($excludeCancelled = false) {
        $query = "SELECT od.*, mi.ItemName 
                  FROM orderdetails od
                  JOIN menuitems mi ON od.ItemID = mi.ItemID
                  WHERE od.OrderID = :orderid";

        // Query to check if Status column exists
        $checkCol = $this->conn->query("SHOW COLUMNS FROM orderdetails LIKE 'Status'");
        $hasStatusCol = $checkCol && ($checkCol->fetch(PDO::FETCH_ASSOC) !== false);

        // Exclude cancelled items if requested AND the column exists
        if ($excludeCancelled && $hasStatusCol) {
            $query .= " AND COALESCE(od.Status, 'Active') != 'Cancelled'";
        }

        $query .= " ORDER BY od.OrderDetailID";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":orderid", $this->OrderID);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAll($status = "", $dateFilter = "") {
        $query = "SELECT o.*, u.FullName as CashierName, ot.TypeName 
                  FROM " . $this->table_name . " o
                  LEFT JOIN users u ON o.UserID = u.UserID
                  LEFT JOIN ordertypes ot ON o.OrderTypeID = ot.OrderTypeID";
        
        $params = [];
        
        // Always exclude deleted orders from dashboard stats
        if($status !== "") {
            $query .= " WHERE o.Status = :status AND o.Status != 'Deleted'";
            $params[':status'] = $status;
        } else {
            $query .= " WHERE o.Status != 'Deleted'";
        }
        
        if($dateFilter !== "") {
            if(empty($params)) {
                $query .= " AND DATE(o.OrderDate) = DATE(:datefilter)";
            } else {
                $query .= " AND DATE(o.OrderDate) = DATE(:datefilter)";
            }
            $params[':datefilter'] = $dateFilter;
        }
        
        $query .= " ORDER BY o.OrderDate DESC";
        
        $stmt = $this->conn->prepare($query);
        
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get recent orders for dashboard (including Deleted) so the list does not change
     * when an order is deleted from the orders page.
     */
    public function getRecentOrdersForDashboard(string $date, int $limit = 10): array
    {
        $query = "SELECT o.*, u.FullName as CashierName, ot.TypeName 
                  FROM " . $this->table_name . " o
                  LEFT JOIN users u ON o.UserID = u.UserID
                  LEFT JOIN ordertypes ot ON o.OrderTypeID = ot.OrderTypeID
                  WHERE DATE(o.OrderDate) = DATE(:datefilter)
                  ORDER BY o.OrderDate DESC
                  LIMIT " . (int) $limit;
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':datefilter', $date);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    public function getById($id) {
        $query = "SELECT o.*, u.FullName as CashierName, ot.TypeName 
                  FROM " . $this->table_name . " o
                  LEFT JOIN users u ON o.UserID = u.UserID
                  LEFT JOIN ordertypes ot ON o.OrderTypeID = ot.OrderTypeID
                  WHERE o.OrderID = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateStatus($status) {
        $query = "UPDATE " . $this->table_name . " SET Status = :status WHERE OrderID = :orderid";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":orderid", $this->OrderID);
        
        return $stmt->execute();
    }

    /**
     * Mark all Paid orders as Deleted so they are excluded from dashboard/reports.
     * Used when resetting dashboard to zero.
     */
    public function markAllPaidAsDeleted(): int
    {
        $stmt = $this->conn->prepare("UPDATE " . $this->table_name . " SET Status = 'Deleted' WHERE Status = 'Paid'");
        $stmt->execute();
        return (int) $stmt->rowCount();
    }
    
    public function getDailySales($date) {
        $query = "SELECT COUNT(*) as total_orders, SUM(TotalAmount) as total_sales 
                  FROM " . $this->table_name . " 
                  WHERE DATE(OrderDate) = :date AND Status = 'Paid'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":date", $date);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_orders' => 0, 'total_sales' => 0];

        // Include archived (deleted) orders from data/sales.json
        $archived = $this->getArchivedSalesForDate($date);

        return [
            'total_orders' => (int)$row['total_orders'] + $archived['total_orders'],
            'total_sales'  => (float)$row['total_sales'] + $archived['total_sales'],
        ];
    }

    /**
     * Sales for the last N days (DB Paid + data/sales.json).
     * Used for dashboard/reports week and as main source from sales.json.
     */
    public function getWeekSales(int $days = 7): array
    {
        $query = "SELECT COUNT(*) as total_orders, COALESCE(SUM(TotalAmount), 0) as total_sales 
                  FROM " . $this->table_name . " 
                  WHERE Status = 'Paid' 
                  AND OrderDate >= DATE_SUB(CURDATE(), INTERVAL :days DAY)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_orders' => 0, 'total_sales' => 0];

        $archivedOrders = 0;
        $archivedSales = 0.0;
        $records = $this->loadArchivedSales();
        $cutoff = (new DateTime())->modify("-{$days} days");

        foreach ($records as $rec) {
            if (empty($rec['order_date'])) continue;
            $dt = new DateTime($rec['order_date']);
            if ($dt < $cutoff) continue;
            $archivedOrders++;
            $archivedSales += isset($rec['total_amount']) ? (float)$rec['total_amount'] : 0.0;
        }

        return [
            'total_orders' => (int)$row['total_orders'] + $archivedOrders,
            'total_sales'  => (float)$row['total_sales'] + $archivedSales,
        ];
    }

    public function getOverallSales() {
        $query = "SELECT SUM(TotalAmount) as overall_sales 
                  FROM " . $this->table_name . " 
                  WHERE Status = 'Paid'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $liveOverall = (float)($row['overall_sales'] ?? 0);

        // Include archived sales
        $archivedSales = 0.0;
        $records = $this->loadArchivedSales();
        foreach ($records as $rec) {
            $archivedSales += isset($rec['total_amount']) ? (float)$rec['total_amount'] : 0.0;
        }

        return $liveOverall + $archivedSales;
    }

    public function getSalesHistory($days = 7) {
        $query = "SELECT DATE(OrderDate) as sale_date, SUM(TotalAmount) as total_sales 
                  FROM " . $this->table_name . " 
                  WHERE Status = 'Paid' 
                  AND OrderDate >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                  GROUP BY DATE(OrderDate)
                  ORDER BY DATE(OrderDate) ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":days", $days, PDO::PARAM_INT);
        $stmt->execute();
        
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Index by date for easier merging
        $byDate = [];
        foreach ($rows as $r) {
            $d = $r['sale_date'];
            $byDate[$d] = (float)$r['total_sales'];
        }

        // Merge archived orders from sales.json
        $this->mergeArchivedSalesHistory($byDate, $days);

        // Rebuild array sorted by date
        ksort($byDate);
        $result = [];
        foreach ($byDate as $d => $total) {
            $result[] = [
                'sale_date'    => $d,
                'total_sales'  => $total,
            ];
        }
        return $result;
    }
    
    public function getMostPurchasedItems($date, $limit = 5) {
        $checkCol = $this->conn->query("SHOW COLUMNS FROM orderdetails LIKE 'Status'");
        $hasStatusCol = $checkCol && ($checkCol->fetch(PDO::FETCH_ASSOC) !== false);

        $query = "SELECT mi.ItemName, SUM(od.Quantity) as total_quantity, SUM(od.Quantity * od.UnitPrice) as total_revenue
                  FROM orderdetails od
                  JOIN menuitems mi ON od.ItemID = mi.ItemID
                  JOIN orders o ON od.OrderID = o.OrderID
                  WHERE DATE(o.OrderDate) = :date AND o.Status = 'Paid'";
        if ($hasStatusCol) {
            $query .= " AND (od.Status IS NULL OR od.Status != 'Cancelled')";
        }
        $query .= " GROUP BY mi.ItemName";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":date", $date);
        $stmt->execute();
        
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Aggregate live DB data
        $byItem = [];
        foreach ($rows as $r) {
            $name = $r['ItemName'];
            $qty  = (int)$r['total_quantity'];
            $rev  = (float)$r['total_revenue'];
            if (!isset($byItem[$name])) {
                $byItem[$name] = ['ItemName' => $name, 'total_quantity' => 0, 'total_revenue' => 0.0];
            }
            $byItem[$name]['total_quantity'] += $qty;
            $byItem[$name]['total_revenue']  += $rev;
        }

        // Merge archived items from sales.json
        $this->mergeArchivedTopItemsForDate($byItem, $date);

        // Convert to array and sort
        $list = array_values($byItem);
        usort($list, function ($a, $b) {
            return $b['total_quantity'] <=> $a['total_quantity'];
        });

        return array_slice($list, 0, $limit);
    }
    
    public function getKitchenOrders() {
        // First get orders
        $query = "SELECT o.*, u.FullName as CashierName, ot.TypeName 
                  FROM " . $this->table_name . " o
                  LEFT JOIN users u ON o.UserID = u.UserID
                  LEFT JOIN ordertypes ot ON o.OrderTypeID = ot.OrderTypeID
                  WHERE o.Status IN ('Pending', 'Ready')
                  ORDER BY o.OrderDate ASC";
        
        $stmt = $this->conn->prepare($query);
        try {
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($orders)) return [];

            // Get all items for these orders in one query to make it FAST (1-second sync)
            $orderIds = array_column($orders, 'OrderID');
            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            
            $itemQuery = "SELECT od.*, mi.ItemName
                          FROM orderdetails od
                          JOIN menuitems mi ON od.ItemID = mi.ItemID
                          WHERE od.OrderID IN ($placeholders) AND (od.Status IS NULL OR od.Status != 'Cancelled')";
            
            $itemStmt = $this->conn->prepare($itemQuery);
            $itemStmt->execute($orderIds);
            $allItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

            // Group items by OrderID
            $itemsByOrder = [];
            foreach ($allItems as $item) {
                $itemsByOrder[$item['OrderID']][] = $item;
            }

            // Attach items to orders
            foreach ($orders as &$o) {
                $o['KitchenItems'] = $itemsByOrder[$o['OrderID']] ?? [];
            }

            return $orders;
        } catch (Exception $e) {
            error_log("Kitchen refresh error: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateOrderStatus($orderId, $status) {
        $query = "UPDATE " . $this->table_name . " 
                  SET Status = :status 
                  WHERE OrderID = :orderid";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":orderid", $orderId);
        $stmt->bindParam(":status", $status);
        
        return $stmt->execute();
    }

    /**
     * Load archived sales records from data/sales.json.
     */
    private function loadArchivedSales(): array
    {
        $file = __DIR__ . '/../data/sales.json';
        if (!file_exists($file)) {
            return [];
        }
        $json = file_get_contents($file);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    private function getArchivedSalesForDate(string $date): array
    {
        $records = $this->loadArchivedSales();
        $orders = 0;
        $sales  = 0.0;

        foreach ($records as $rec) {
            if (empty($rec['order_date'])) continue;
            $d = date('Y-m-d', strtotime($rec['order_date']));
            if ($d === $date) {
                $orders++;
                $sales += isset($rec['total_amount']) ? (float)$rec['total_amount'] : 0.0;
            }
        }

        return [
            'total_orders' => $orders,
            'total_sales'  => $sales,
        ];
    }

    private function mergeArchivedSalesHistory(array &$byDate, int $days): void
    {
        $records = $this->loadArchivedSales();
        if (!$records) return;

        $end   = new DateTime(); // today
        $start = (clone $end)->modify("-{$days} day");

        foreach ($records as $rec) {
            if (empty($rec['order_date'])) continue;
            $dt = new DateTime($rec['order_date']);
            if ($dt < $start || $dt > $end) continue;

            $d = $dt->format('Y-m-d');
            $amount = isset($rec['total_amount']) ? (float)$rec['total_amount'] : 0.0;
            if (!isset($byDate[$d])) {
                $byDate[$d] = 0.0;
            }
            $byDate[$d] += $amount;
        }
    }

    private function mergeArchivedTopItemsForDate(array &$byItem, string $date): void
    {
        $records = $this->loadArchivedSales();
        if (!$records) return;

        foreach ($records as $rec) {
            if (empty($rec['order_date'])) continue;
            $d = date('Y-m-d', strtotime($rec['order_date']));
            if ($d !== $date) continue;

            if (empty($rec['items']) || !is_array($rec['items'])) continue;

            foreach ($rec['items'] as $item) {
                $name = $item['item_name'] ?? '';
                if ($name === '') continue;

                $qty = isset($item['quantity']) ? (int)$item['quantity'] : 0;
                $unit = isset($item['unit_price']) ? (float)$item['unit_price'] : 0.0;
                $rev = $qty * $unit;

                if (!isset($byItem[$name])) {
                    $byItem[$name] = ['ItemName' => $name, 'total_quantity' => 0, 'total_revenue' => 0.0];
                }
                $byItem[$name]['total_quantity'] += $qty;
                $byItem[$name]['total_revenue']  += $rev;
            }
        }
    }
    
    public function getByTableNumber($tableNumber) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE TableNumber = :table_number 
                  AND Status NOT IN ('Paid', 'Deleted')
                  ORDER BY OrderDate DESC 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":table_number", $tableNumber);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateTotalAmount() {
        // First check if Status column exists
        $checkCol = $this->conn->query("SHOW COLUMNS FROM orderdetails LIKE 'Status'");
        $hasStatusCol = $checkCol && ($checkCol->fetch(PDO::FETCH_ASSOC) !== false);

        if ($hasStatusCol) {
            $query = "SELECT SUM(Quantity * UnitPrice) as total 
                      FROM orderdetails 
                      WHERE OrderID = :order_id AND COALESCE(Status, 'Active') != 'Cancelled'";
        } else {
            $query = "SELECT SUM(Quantity * UnitPrice) as total 
                      FROM orderdetails 
                      WHERE OrderID = :order_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":order_id", $this->OrderID);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $result['total'] ?? 0;
        
        // Update the order total
        $query = "UPDATE " . $this->table_name . " 
                  SET TotalAmount = :total 
                  WHERE OrderID = :order_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":total", $total);
        $stmt->bindParam(":order_id", $this->OrderID);
        
        return $stmt->execute();
    }
}
?>
