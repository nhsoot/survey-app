<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config/database.php';

$admin_id = $_SESSION['admin_id'];
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

if ($action === 'get_data') {
    $survey_id = $_GET['survey_id'] ?? 0;
    $question_id = $_GET['question_id'] ?? 0;
    $mode = $_GET['mode'] ?? 'normal'; // 'normal' atau 'satisfaction'
    
    if ($survey_id == 0 || $question_id == 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit();
    }
    
    try {
        // Verify survey belongs to admin
        $stmt = $pdo->prepare("SELECT id FROM surveys WHERE id = ? AND admin_id = ?");
        $stmt->execute([$survey_id, $admin_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Survey not found']);
            exit();
        }
        
        // Get question text
        $stmt = $pdo->prepare("SELECT question_text FROM questions WHERE id = ? AND survey_id = ?");
        $stmt->execute([$question_id, $survey_id]);
        $question = $stmt->fetch();
        
        if (!$question) {
            echo json_encode(['success' => false, 'message' => 'Question not found']);
            exit();
        }
        
        // Get answer data with grouping
        $stmt = $pdo->prepare("
            SELECT a.answer_value, COUNT(*) as count 
            FROM answers a 
            JOIN responses r ON a.response_id = r.id 
            WHERE a.question_id = ? AND r.survey_id = ? 
            GROUP BY a.answer_value
            ORDER BY count DESC
        ");
        $stmt->execute([$question_id, $survey_id]);
        $answers = $stmt->fetchAll();
        
        if (count($answers) == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Tidak ada data untuk pertanyaan ini. Belum ada responden.'
            ]);
            exit();
        }
        
        // Total respondents
        $totalRespondents = 0;
        foreach ($answers as $row) {
            $totalRespondents += $row['count'];
        }
        
        if ($mode === 'satisfaction') {
            // MODE KEPUASAN: Hanya hitung Puas dan Sangat Puas
            $satisfiedWords = ['puas', 'sangat puas', 'satisfied', 'very satisfied', 'sangat baik', 'baik'];
            $satisfiedCount = 0;
            $satisfiedData = [];
            
            foreach ($answers as $row) {
                $answerLower = strtolower(trim($row['answer_value']));
                $isSatisfied = false;
                foreach ($satisfiedWords as $word) {
                    if (strpos($answerLower, $word) !== false) {
                        $isSatisfied = true;
                        break;
                    }
                }
                if ($isSatisfied) {
                    $satisfiedCount += $row['count'];
                    $satisfiedData[] = [
                        'label' => $row['answer_value'],
                        'count' => $row['count']
                    ];
                }
            }
            
            // If no satisfied found, check for numeric values (Likert scale: 4-5)
            if ($satisfiedCount == 0) {
                foreach ($answers as $row) {
                    $answerValue = trim($row['answer_value']);
                    if (is_numeric($answerValue) && (int)$answerValue >= 4) {
                        $satisfiedCount += $row['count'];
                        $satisfiedData[] = [
                            'label' => $row['answer_value'],
                            'count' => $row['count']
                        ];
                    }
                }
            }
            
            $percentage = $totalRespondents > 0 ? round(($satisfiedCount / $totalRespondents) * 100, 2) : 0;
            
            // Prepare data for chart - show satisfaction percentage as bar
            $chartData = [
                [
                    'label' => 'Puas / Sangat Puas',
                    'count' => $percentage
                ],
                [
                    'label' => 'Lainnya',
                    'count' => 100 - $percentage
                ]
            ];
            
            // If only satisfied data exists, just show one bar
            if ($satisfiedCount == 0) {
                $chartData = [
                    [
                        'label' => 'Puas / Sangat Puas',
                        'count' => 0
                    ],
                    [
                        'label' => 'Lainnya',
                        'count' => 100
                    ]
                ];
            }
            
            echo json_encode([
                'success' => true,
                'question_text' => $question['question_text'],
                'mode' => 'satisfaction',
                'data' => $chartData,
                'percentage' => $percentage,
                'satisfied_count' => $satisfiedCount,
                'total_respondents' => $totalRespondents,
                'raw_data' => $answers
            ]);
            
        } else {
            // NORMAL MODE: Tampilkan semua data
            $data = [];
            foreach ($answers as $row) {
                $data[] = [
                    'label' => $row['answer_value'],
                    'count' => (int)$row['count']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'question_text' => $question['question_text'],
                'mode' => 'normal',
                'data' => $data,
                'total_respondents' => $totalRespondents
            ]);
        }
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>