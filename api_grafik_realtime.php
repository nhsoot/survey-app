<?php
// File: api_grafik_realtime.php
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    // 1. Cari survei aktif yang paling baru dibuat
    $stmtSurvey = $pdo->query("SELECT id, title FROM surveys WHERE status = 'active' ORDER BY created_at DESC LIMIT 1");
    $survey = $stmtSurvey->fetch(PDO::FETCH_ASSOC);

    if (!$survey) {
        echo json_encode(['status' => 'empty', 'message' => 'Belum ada survei aktif.']);
        exit();
    }

    $surveyId = $survey['id'];
    $stmtCount = $pdo->prepare("SELECT COUNT(id) FROM responses WHERE survey_id = ?");
    $stmtCount->execute([$surveyId]);
    $totalResponden = (int) $stmtCount->fetchColumn();

    // 2. Ambil pertanyaan dan konversi teks jawaban ke angka skor (1-5) untuk dihitung rata-ratanya
    $query = "
        SELECT 
            q.id,
            q.question_text, 
            COALESCE(AVG(
                CASE 
                    WHEN a.answer_value = 'Sangat Puas' THEN 5
                    WHEN a.answer_value = 'Puas' THEN 4
                    WHEN a.answer_value = 'Cukup' THEN 3
                    WHEN a.answer_value = 'Kurang' THEN 2
                    WHEN a.answer_value = 'Sangat Kurang' THEN 1
                    -- Jika tersimpan sebagai angka langsung (misal: '5', '4')
                    WHEN a.answer_value REGEXP '^[0-9]+$' THEN CAST(a.answer_value AS UNSIGNED)
                    ELSE NULL
                END
            ), 0) AS rata_rata_nilai
        FROM questions q
        LEFT JOIN answers a ON q.id = a.question_id
        WHERE q.survey_id = ? AND q.type IN ('likert', 'multiple_choice')
        GROUP BY q.id, q.question_text
        ORDER BY q.order_number ASC
    ";
    
    $stmtData = $pdo->prepare($query);
    $stmtData->execute([$surveyId]);
    $results = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $dataNilai = [];

    foreach ($results as $row) {
        // Potong teks pertanyaan jika terlalu panjang agar tidak merusak tampilan grafik
        $teks_pendek = strlen($row['question_text']) > 35 ? substr($row['question_text'], 0, 35) . '...' : $row['question_text'];
        
        $labels[] = $teks_pendek;
        $dataNilai[] = round((float) $row['rata_rata_nilai'], 2);
    }

    // 3. Kirimkan data hasil olahan ke frontend
    echo json_encode([
        'status' => 'success',
        'survey_title' => $survey['title'],
        'total_responden' => $totalResponden,
        'labels' => $labels,
        'data' => $dataNilai
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>