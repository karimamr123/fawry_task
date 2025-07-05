<?php

class Product {
    public string $name;
    public float $price;
    public int $stockQuantity;

    public function __construct(string $name, float $price, int $stockQuantity) {
        $this->name = $name;
        $this->price = $price;
        $this->stockQuantity = $stockQuantity;
    }

    public function reduceQuantity(int $amount) {
        $this->stockQuantity -= $amount;
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

interface Shippable {
    public function getName(): string;
    public function getWeight(): float;
}

class ShippableProduct extends Product implements Shippable {
    public float $weight;

    public function __construct(string $name, float $price, int $stockQuantity, float $weight) {
        parent::__construct($name, $price, $stockQuantity);
        $this->weight = $weight;
    }

    public function needsShipping(): bool {
        return true;
    }

    public function getWeight(): float {
        return $this->weight;
    }

    public function getName(): string {
        return $this->name;
    }
}

class ExpirableProduct extends Product {
    public string $expiryDate;

    public function __construct(string $name, float $price, int $stockQuantity, string $expiryDate) {
        parent::__construct($name, $price, $stockQuantity);
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
    public array $cartItems = [];

    public function add(Product $product, int $quantityToAdd) {
        if ($quantityToAdd > $product->stockQuantity) {
            echo "Not enough stock for {$product->name}\n";
            return;
        }
        $this->cartItems[] = ["product" => $product, "quantity" => $quantityToAdd];
    }
}

class ShippingService {
    public static function ship(array $shippableProducts) {
        echo "** Shipping Service **\n";
        foreach ($shippableProducts as $product) {
            echo $product->getName() . " (" . $product->getWeight() . " kg)\n";
        }
        echo "\n";
    }
}

function checkout(Customer $customer, Cart $cart) {
    if (empty($cart->cartItems)) {
        echo "Cart is empty\n";
        return;
    }

    $subtotal = 0;
    $shippingCost = 0;
    $totalWeight = 0;
    $productsToShip = [];
    $validCartItems = [];

    echo "<pre>";

    foreach ($cart->cartItems as $cartItem) {
        $product = $cartItem["product"];
        $quantity = $cartItem["quantity"];

        if ($product->isExpired()) {
            echo "Expired: {$product->name}\n";
            continue;
        }

        $itemTotal = $product->price * $quantity;
        $subtotal += $itemTotal;
        $validCartItems[] = $cartItem;

        if ($product->needsShipping()) {
            $shippingCost += 10 * $quantity;
            $totalWeight += $product->getWeight() * $quantity;

            if ($product instanceof Shippable) {
                $productsToShip[] = $product;
            }
        }
    }

    if ($subtotal == 0) {
        echo "All products are expired, cannot checkout.\n";
        echo "</pre>";
        return;
    }

    $totalAmount = $subtotal + $shippingCost;

    if ($customer->balance < $totalAmount) {
        echo "Not enough balance\n";
        echo "</pre>";
        return;
    }

    foreach ($validCartItems as $cartItem) {
        $product = $cartItem["product"];
        $quantity = $cartItem["quantity"];
        $product->reduceQuantity($quantity);
    }

    $customer->deduct($totalAmount);

    if (!empty($productsToShip)) {
        ShippingService::ship($productsToShip);
    }

    echo "** Receipt **\n";
    foreach ($validCartItems as $cartItem) {
        $product = $cartItem["product"];
        $quantity = $cartItem["quantity"];
        $itemTotal = $product->price * $quantity;
        echo "{$quantity}x {$product->name} = $itemTotal\n";
    }
    echo "Products subtotal: $subtotal\n";
    echo "Shipping: $shippingCost\n";
    echo "Total: $totalAmount\n";
    echo "Total weight: " . number_format($totalWeight, 2) . " kg\n";
    echo "</pre>";
}

// Example
$cheese = new ShippableProduct("Cheese", 50, 5, 0.5);
$bread = new ExpirableProduct("Bread", 20, 3, "2024-07-01");
$card = new Product("Scratch Card", 10, 10);

$customer = new Customer("Ali", 500);

$cart = new Cart();
$cart->add($cheese, 2);
$cart->add($bread, 1);
$cart->add($card, 1);

checkout($customer, $cart);

echo "<pre>After checkout:\n";
echo "Cheese stock: {$cheese->stockQuantity}\n";
echo "Bread stock: {$bread->stockQuantity}\n";
echo "Card stock: {$card->stockQuantity}\n";
echo "Customer balance: {$customer->balance}\n";
echo "</pre>";
