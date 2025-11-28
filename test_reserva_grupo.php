<?php
// Test script to verify the random group ID generation
echo "Testing random group ID generation:\n";

// Test multiple times to see different generated values
for ($i = 0; $i < 5; $i++) {
    $reserva_grupo = bin2hex(random_bytes(8));
    echo "Generated group ID " . ($i+1) . ": " . $reserva_grupo . "\n";
}

echo "\nTesting the condition for single vs multiple days:\n";
$single_day_dates = ['2023-12-25'];
$multiple_day_dates = ['2023-12-25', '2023-12-26', '2023-12-27'];

// Old logic (commented to show what it was):
// $old_reserva_grupo = count($single_day_dates) > 1 ? bin2hex(random_bytes(8)) : null;
// echo "Old logic - Single day: " . ($old_reserva_grupo ?? 'NULL') . "\n";

// New logic (what we implemented):
$new_reserva_grupo_single = bin2hex(random_bytes(8));
echo "New logic - Single day: " . $new_reserva_grupo_single . "\n";

// New logic for multiple days:
$new_reserva_grupo_multiple = bin2hex(random_bytes(8));
echo "New logic - Multiple days: " . $new_reserva_grupo_multiple . "\n";

echo "\nWith the new implementation, both single and multiple day reservations will get a random group ID.\n";
?>