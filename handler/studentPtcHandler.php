<?php
// handler/studentPtcHandler.php

if (!isset($_SESSION)) session_start();

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/auth.php'; // ensures logged-in + sets session ids

// 🔒 Restrict to students only
if (($_SESSION['account_type'] ?? '') !== 'student') {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['student_id'] ?? null;
$studentName = '';
$currentBooking = null;
$availableSchedules = [];

// =========================
// 🔹 Fetch Student Name
// =========================
if ($student_id) {
    $stmt = $pdo->prepare("
        SELECT CONCAT(u.Name, ' ', u.Surname) AS studentName
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $studentName = $stmt->fetchColumn() ?: ($_SESSION['Name'] ?? 'Student');
}

// =========================
// 🔹 Handle Booking / Cancel
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // --- BOOK ---
        if (isset($_POST['book'], $_POST['schedule_id'])) {
            $schedule_id = (int) $_POST['schedule_id'];

            // Ensure no active booking
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM ptc_bookings 
                WHERE student_id = ? AND status = 'booked'
            ");
            $stmt->execute([$student_id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("You already have an active booking. Cancel it first.");
            }

            // Validate schedule
            $stmt = $pdo->prepare("
                SELECT ps.schedule_id, ps.date, ps.startTime
                FROM ptc_schedules ps
                JOIN class_students cs ON ps.teacher_id = cs.teacher_id
                WHERE ps.schedule_id = ? 
                  AND cs.student_id = ? 
                  AND ps.status = 'open'
            ");
            $stmt->execute([$schedule_id, $student_id]);
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$schedule) {
                throw new Exception("Invalid or unavailable schedule.");
            }

            // Ensure not in the past
            $scheduleTime = new DateTime($schedule['date'] . ' ' . $schedule['startTime']);
            if ($scheduleTime < new DateTime()) {
                throw new Exception("Cannot book a schedule in the past.");
            }

            // Insert booking + mark schedule
            $pdo->prepare("
                INSERT INTO ptc_bookings (student_id, schedule_id, status) 
                VALUES (?, ?, 'booked')
            ")->execute([$student_id, $schedule_id]);

            $pdo->prepare("
                UPDATE ptc_schedules SET status = 'booked' WHERE schedule_id = ?
            ")->execute([$schedule_id]);

            $_SESSION['success'] = "PTC booking confirmed.";
        }

        // --- CANCEL ---
        if (isset($_POST['cancel'], $_POST['booking_id'])) {
            $booking_id = (int) $_POST['booking_id'];

            $stmt = $pdo->prepare("
                SELECT pb.booking_id, ps.schedule_id, ps.date, ps.startTime
                FROM ptc_bookings pb
                JOIN ptc_schedules ps ON pb.schedule_id = ps.schedule_id
                WHERE pb.booking_id = ? 
                  AND pb.student_id = ? 
                  AND pb.status = 'booked'
            ");
            $stmt->execute([$booking_id, $student_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                throw new Exception("Invalid or unauthorized booking.");
            }

            // Prevent canceling past schedules
            $scheduleTime = new DateTime($booking['date'] . ' ' . $booking['startTime']);
            if ($scheduleTime < new DateTime()) {
                throw new Exception("Cannot cancel a schedule that has already passed.");
            }

            // Cancel booking + reopen slot
            $pdo->prepare("UPDATE ptc_bookings SET status = 'cancelled' WHERE booking_id = ?")
                ->execute([$booking_id]);

            $pdo->prepare("UPDATE ptc_schedules SET status = 'open' WHERE schedule_id = ?")
                ->execute([$booking['schedule_id']]);

            $_SESSION['success'] = "PTC booking cancelled.";
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }

    header("Location: ../pages/studentPtc.php");
    exit;
}

// =========================
// 🔹 Fetch Current Booking
// =========================
if ($student_id) {
    $stmt = $pdo->prepare("
        SELECT pb.booking_id, ps.schedule_id, ps.date, ps.startTime, ps.endTime,
               CONCAT(u.Name, ' ', u.Surname) AS teacherName
        FROM ptc_bookings pb
        JOIN ptc_schedules ps ON pb.schedule_id = ps.schedule_id
        JOIN teachers t ON ps.teacher_id = t.teacher_id
        JOIN users u ON t.user_id = u.user_id
        WHERE pb.student_id = ?
          AND pb.status = 'booked'
          AND (ps.date > CURDATE() OR (ps.date = CURDATE() AND ps.startTime >= TIME(NOW())))
        ORDER BY ps.date, ps.startTime
        LIMIT 1
    ");
    $stmt->execute([$student_id]);
    $currentBooking = $stmt->fetch(PDO::FETCH_ASSOC);
}

// =========================
// 🔹 Fetch Available Slots
// =========================
if (!$currentBooking && $student_id) {
    $stmt = $pdo->prepare("
        SELECT ps.schedule_id, ps.date, ps.startTime, ps.endTime,
               CONCAT(u.Name, ' ', u.Surname) AS teacherName
        FROM ptc_schedules ps
        JOIN teachers t ON ps.teacher_id = t.teacher_id
        JOIN users u ON t.user_id = u.user_id
        JOIN class_students cs ON ps.teacher_id = cs.teacher_id
        WHERE cs.student_id = ?
          AND ps.status = 'open'
          AND (ps.date > CURDATE() OR (ps.date = CURDATE() AND ps.startTime >= TIME(NOW())))
        ORDER BY ps.date, ps.startTime
    ");
    $stmt->execute([$student_id]);
    $availableSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Export for frontend: $studentName, $currentBooking, $availableSchedules
