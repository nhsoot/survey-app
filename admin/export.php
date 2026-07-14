<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$survey_id = $_GET['survey_id'] ?? 0;
$admin_id = $_SESSION['admin_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ? AND admin_id = ?");
    $stmt->execute([$survey_id, $admin_id]);
    $survey = $stmt->fetch();
    
    if (!$survey) {
        die('Survey tidak ditemukan');
    }
    
    // Get questions
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE survey_id = ? ORDER BY order_number");
    $stmt->execute([$survey_id]);
    $questions = $stmt->fetchAll();
    
    // Get responses
    $stmt = $pdo->prepare("
        SELECT r.*, 
        GROUP_CONCAT(CONCAT(q.id, '|', a.answer_value) ORDER BY q.order_number SEPARATOR '||') as answers
        FROM responses r
        LEFT JOIN answers a ON r.id = a.response_id
        LEFT JOIN questions q ON a.question_id = q.id
        WHERE r.survey_id = ?
        GROUP BY r.id
        ORDER BY r.submitted_at DESC
    ");
    $stmt->execute([$survey_id]);
    $responses = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="survey_' . $survey_id . '_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header row
$header = ['Responden ID', 'Nama', 'Email', 'Tanggal Pengisian'];
foreach ($questions as $q) {
    $header[] = substr($q['question_text'], 0, 50) . (strlen($q['question_text']) > 50 ? '...' : '');
}
fputcsv($output, $header);

// Data rows
foreach ($responses as $response) {
    $row = [
        $response['id'],
        $response['respondent_name'] ?? '',
        $response['respondent_email'] ?? '',
        $response['submitted_at']
    ];
    
    // Parse answers
    $answerMap = [];
    if ($response['answers']) {
        $pairs = explode('||', $response['answers']);
        foreach ($pairs as $pair) {
            if (strpos($pair, '|') !== false) {
                list($qid, $value) = explode('|', $pair, 2);
                $answerMap[$qid] = $value;
            }
        }
    }
    
    foreach ($questions as $q) {
        $row[] = $answerMap[$q['id']] ?? '';
    }
    
    fputcsv($output, $row);
}

fclose($output);
exit();
?>