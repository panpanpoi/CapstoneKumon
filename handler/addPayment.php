<?php
session_start();
require_once "../database.php"; // connect to database

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id        = $_POST['student_id']        ?? null;
    $amount            = $_POST['amount']            ?? null;
    $payment_date      = $_POST['payment_date']      ?? null;
    $payment_method    = $_POST['payment_method']    ?? null;
    $reference_number  = $_POST['reference_number']  ?? null;
    $tfMonthCovered    = $_POST['tfMonthCovered']    ?? null; // ✅ NEW FIELD
    $remarks           = $_POST['remarks']           ?? null;

    // ✅ Required field check
    if (!$student_id || !$amount || !$payment_date || !$payment_method) {
        $_SESSION['error'] = "Missing required fields. Please complete the form.";
        header("Location: ../pages/recordPayment.php");
        exit;
    }

    try {
        // ✅ Check if this month was already paid for
        $check = $pdo->prepare("
            SELECT COUNT(*) FROM payments 
            WHERE student_id = :student_id AND tf_month_covered = :tfMonthCovered
        ");
        $check->execute([
            ':student_id' => $student_id,
            ':tfMonthCovered' => $tfMonthCovered
        ]);
        $alreadyPaid = $check->fetchColumn();

        if ($alreadyPaid > 0) {
            $_SESSION['error'] = "⚠️ Payment for {$tfMonthCovered} already exists for this student.";
            header("Location: ../pages/recordPayment.php");
            exit;
        }

        // ✅ Compute due date based on TF-Month Covered (preferred)
        if ($tfMonthCovered) {
            $tfDateObj = DateTime::createFromFormat('F Y', $tfMonthCovered);
            if ($tfDateObj) {
                $tfDateObj->modify('+1 month');
                $due_date = $tfDateObj->format('Y-m-01'); // or 'Y-m-05'
            } else {
                // fallback
                $dueDateObj = new DateTime($payment_date);
                $dueDateObj->modify('+30 days');
                $due_date = $dueDateObj->format('Y-m-d');
            }
        } else {
            // fallback if tfMonthCovered empty
            $dueDateObj = new DateTime($payment_date);
            $dueDateObj->modify('+30 days');
            $due_date = $dueDateObj->format('Y-m-d');
        }


        // ✅ Insert payment
        $stmt = $pdo->prepare("
            INSERT INTO payments 
                (student_id, amount, payment_date, due_date, payment_method, reference_number, tf_month_covered, remarks) 
            VALUES 
                (:student_id, :amount, :payment_date, :due_date, :payment_method, :reference_number, :tfMonthCovered, :remarks)
        ");

        $stmt->execute([
            ':student_id'        => $student_id,
            ':amount'            => $amount,
            ':payment_date'      => $payment_date,
            ':due_date'          => $due_date,
            ':payment_method'    => $payment_method,
            ':reference_number'  => $reference_number ?: null,
            ':tfMonthCovered'    => $tfMonthCovered ?: null,
            ':remarks'           => $remarks ?: null
        ]);

        // ✅ Compute next due month for success message (next month after tfMonthCovered)
        $tfMonthDate = DateTime::createFromFormat('F Y', $tfMonthCovered);
        if ($tfMonthDate) {
            $tfMonthDate->modify('+1 month');
            $next_due_month = $tfMonthDate->format('F Y');
        } else {
            $next_due_month = 'Unknown';
        }

        $_SESSION['success'] = "✅ Payment for {$tfMonthCovered} recorded successfully! (Next due: {$next_due_month})";
        header("Location: ../pages/recordPayment.php");
        exit;


    } catch (PDOException $e) {
        $_SESSION['error'] = "Error saving payment: " . $e->getMessage();
        header("Location: ../pages/recordPayment.php");
        exit;
    }
}
?>
