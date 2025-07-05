<?php

class Product {
    public string $name;
    public float $price;
    public int $quantity;

    public function __construct(string $name, float $price, int $quantity) {
        $this->name = $name;
        $this->price = $price;
        $this->quantity = $quantity;
    }

    public function reduceQuantity(int $amount) {
        $this->quantity -= $amount;
    }

    public function isExpired(): bool {
        return false;
    }

    public function needsShipping(): bool {
        return false;
    }

    public function getWeight(): float {
        return 0;
    }
}

class ShippableProduct extends Product {
    public float $weight;

    public function __construct(string $name, float $price, int $quantity, float $weight) {
        parent::__construct($name, $price, $quantity);
        $this->weight = $weight;
    }

    public function needsShipping(): bool {
        return true;
    }

    public function getWeight(): float {
        return $this->weight;
    }
}

class ExpirableProduct extends Product {
    public string $expiryDate;

    public function __construct(string $name, float $price, int $quantity, string $expiryDate) {
        parent::__construct($name, $price, $quantity);
        $this->expiryDate = $expiryDate;
    }

    public function isExpired(): bool {
        return strtotime($this->expiryDate) < time();
    }
}

class Customer {
    public string $name;
    public float $balance;

    public function __construct(string $name, float $balance) {
        $this->name = $name;
        $this->balance = $balance;
    }

    public function deduct(float $amount) {
        $this->balance -= $amount;
    }
}

class Cart {
    public array $products = [];

    public function add(Product $product, int $qty) {
        if ($qty > $product->quantity) {
            echo "Not enough stock for {$product->name}\n";
            return;
        }
        $this->products[] = ["product" => $product, "qty" => $qty];
    }
}

function checkout(Customer $customer, Cart $cart) {
    $total = 0;
    $shipping = 0;
    $weight = 0;

    echo "<pre>";

    foreach ($cart->products as $item) {
        $product = $item["product"];
        $qty = $item["qty"];

        if ($product->isExpired()) {
            echo "Expired: {$product->name}\n";
            continue;
        }

        $linePrice = $product->price * $qty;
        $total += $linePrice;

        if ($product->needsShipping()) {
            $shipping += 10 * $qty;
            $weight += $product->getWeight() * $qty;
        }
    }

    $finalTotal = $total + $shipping;

    if ($customer->balance < $finalTotal) {
        echo "Not enough balance\n";
        echo "</pre>";
        return;
    }

    foreach ($cart->products as $item) {
        $product = $item["product"];
        $qty = $item["qty"];

        if (!$product->isExpired()) {
            $product->reduceQuantity($qty);
        }
    }
    $customer->deduct($finalTotal);

    echo "** Receipt **\n";
    echo "Products total: $total\n";
    echo "Shipping: $shipping\n";
    echo "Total: $finalTotal\n";
    echo "Weight: " . number_format($weight, 2) . " kg\n";
    echo "</pre>";
}

// Example
$cheese = new ShippableProduct("Cheese", 50, 5, 0.5);
$bread = new ExpirableProduct("Bread", 20, 3, "2024-07-01");
$card = new Product("Card", 10, 10);

$customer = new Customer("Ali", 500);

$cart = new Cart();
$cart->add($cheese, 2);
$cart->add($bread, 1);
$cart->add($card, 1);

checkout($customer, $cart);

echo "<pre>After checkout:\n";
echo "Cheese: {$cheese->quantity}\n";
echo "Bread: {$bread->quantity}\n";
echo "Card: {$card->quantity}\n";
echo "Balance: {$customer->balance}\n";
echo "</pre>";
