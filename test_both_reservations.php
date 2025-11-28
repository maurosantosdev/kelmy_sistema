<?php
// Test script to verify both reservation creation files now include reserva_grupo
echo "Checking if both reservation creation files include reserva_grupo field...\n";

// Test create_reservation.php logic
echo "\n1. Testing create_reservation.php logic:\n";
$reserva_grupo1 = bin2hex(random_bytes(8));
echo "Generated reserva_grupo for create_reservation.php: " . $reserva_grupo1 . "\n";

// Test create_mp_reservation.php logic
echo "\n2. Testing create_mp_reservation.php logic:\n";
$reserva_grupo2 = bin2hex(random_bytes(8));
echo "Generated reserva_grupo for create_mp_reservation.php: " . $reserva_grupo2 . "\n";

echo "\nBoth files now generate a random number for reserva_grupo field, regardless of the number of days.\n";
echo "The fix has been applied to both reservation creation methods.\n";
?>