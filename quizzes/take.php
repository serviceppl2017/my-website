<?php
require_once '../includes/auth.php';
validateSession();

if (!isStudent()) {
    header('Location: /ss/courses/manage.php');
    exit();
}

$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;

// Verify quiz access and unit unlock status
$stmt = $conn->prepare("
    SELECT 
        q.*,
        u.title as unit_title,
        c.id as course_id,
        c.title as course_title,
        CASE 
            WHEN u.order_num = 1 THEN 1
            WHEN (
                SELECT COUNT(*)
                FROM quizzes q2
                LEFT JOIN quiz_attempts qa2 ON q2.id = qa2.quiz_id AND qa2.student_id = ?
                WHERE q2.unit_id = (
                    SELECT id FROM units 
                    WHERE course_id = c.id 
                    AND order_num = u.order_num - 1
                )
                AND (qa2.passed IS NULL OR qa2.passed = 0)
            ) = 0 THEN 1
            ELSE 0
        END as is_unlocked
    FROM quizzes q
    JOIN units u ON q.unit_id = u.id
    JOIN courses c ON u.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    WHERE q.id = ? AND e.student_id = ? AND c.is_active = 1
");
$stmt->bind_param("iii", $_SESSION['user_id'], $quiz_id, $_SESSION['user_id']);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();

if (!$quiz || !$quiz['is_unlocked']) {
    header('Location: /ss/courses/enrolled.php');
    exit();
}

// Check if quiz was already passed
$stmt = $conn->prepare("SELECT * FROM quiz_attempts WHERE quiz_id = ? AND student_id = ? AND passed = 1");
$stmt->bind_param("ii", $quiz_id, $_SESSION['user_id']);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header('Location: /ss/courses/view.php?course_id=' . $quiz['course_id']);
    exit();
}

// Handle quiz submission BEFORE including header.php
if (isset($_POST['submit_quiz'])) {
    $answers = $_POST['answers'] ?? [];
    $score = 0;
    $total_questions = 0;
    
    // Fetch questions and correct answers
    $stmt = $conn->prepare("
        SELECT q.*, 
               GROUP_CONCAT(
                   CONCAT(a.id, ':', a.answer_text, ':', a.is_correct)
                   ORDER BY a.id ASC
                   SEPARATOR '|'
               ) as answers
        FROM quiz_questions q
        JOIN quiz_answers a ON q.id = a.question_id
        WHERE q.quiz_id = ?
        GROUP BY q.id
    ");
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $questions = $stmt->get_result();
    
    while ($question = $questions->fetch_assoc()) {
        $total_questions++;
        $question_answers = array_map(function($answer) {
            list($id, $text, $is_correct) = explode(':', $answer);
            return ['id' => $id, 'text' => $text, 'is_correct' => $is_correct];
        }, explode('|', $question['answers']));
        
        $student_answer = $answers[$question['id']] ?? null;
        if ($student_answer) {
            foreach ($question_answers as $answer) {
                if ($answer['id'] == $student_answer && $answer['is_correct']) {
                    $score++;
                    break;
                }
            }
        }
    }
    
    // Calculate percentage score
    $percentage_score = round(($score / $total_questions) * 100);
    $passed = $percentage_score >= $quiz['passing_score'] ? 1 : 0;
    
    // Record attempt
    $stmt = $conn->prepare("
        INSERT INTO quiz_attempts (
            student_id, 
            quiz_id, 
            score, 
            passed, 
            attempt_date
        ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    
    if ($stmt === false) {
        die('خطأ في إعداد الاستعلام: ' . $conn->error);
    }
    
    $stmt->bind_param("iiii", $_SESSION['user_id'], $quiz_id, $percentage_score, $passed);
    
    if ($stmt->execute()) {
        $attempt_id = $conn->insert_id;
        if ($attempt_id) {
            header('Location: /ss/quizzes/result.php?attempt_id=' . $attempt_id);
            exit();
        } else {
            $error = "حدث خطأ في الحصول على معرف المحاولة";
        }
    } else {
        $error = "حدث خطأ أثناء حفظ نتيجة الاختبار: " . $stmt->error;
        error_log("Quiz attempt save error for user {$_SESSION['user_id']}, quiz {$quiz_id}: " . $stmt->error);
    }
}

$pageTitle = 'اختبار: ' . $quiz['title'];
require_once '../includes/header.php';

// Fetch quiz questions for display
$stmt = $conn->prepare("
    SELECT q.*, 
           GROUP_CONCAT(
               CONCAT(a.id, ':', a.answer_text)
               ORDER BY RAND()
               SEPARATOR '|'
           ) as answers
    FROM quiz_questions q
    JOIN quiz_answers a ON q.id = a.question_id
    WHERE q.quiz_id = ?
    GROUP BY q.id
    ORDER BY RAND()
");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$questions = $stmt->get_result();
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2><?php echo htmlspecialchars($quiz['title']); ?></h2>
            <p style="color: #666;">
                <i class="fas fa-book"></i> <?php echo htmlspecialchars($quiz['course_title']); ?> - 
                <?php echo htmlspecialchars($quiz['unit_title']); ?>
            </p>
        </div>
        <a href="/ss/courses/view.php?course_id=<?php echo $quiz['course_id']; ?>" class="btn btn-primary">
            <i class="fas fa-times"></i> إلغاء الاختبار
        </a>
    </div>

    <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; margin-bottom: 2rem;">
        <p><i class="fas fa-info-circle"></i> تعليمات الاختبار:</p>
        <ul style="margin: 0.5rem 2rem;">
            <li>درجة النجاح المطلوبة: <?php echo $quiz['passing_score']; ?>%</li>
            <li>يجب الإجابة على جميع الأسئلة</li>
            <li>لا يمكن تغيير الإجابات بعد تسليم الاختبار</li>
        </ul>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="quiz-form">
        <?php $question_number = 1; ?>
        <?php while ($question = $questions->fetch_assoc()): ?>
            <div class="question-card" style="background: white; border: 1px solid #eee; border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem;">
                <h3 style="margin-bottom: 1rem;">
                    السؤال <?php echo $question_number; ?>:
                    <?php echo htmlspecialchars($question['question_text']); ?>
                </h3>

                <div class="answers" style="display: grid; gap: 0.5rem;">
                    <?php
                    $answers = array_map(function($answer) {
                        list($id, $text) = explode(':', $answer);
                        return ['id' => $id, 'text' => $text];
                    }, explode('|', $question['answers']));
                    ?>

                    <?php foreach ($answers as $answer): ?>
                        <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; border: 1px solid #eee; border-radius: 5px; cursor: pointer;">
                            <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="<?php echo $answer['id']; ?>" required>
                            <?php echo htmlspecialchars($answer['text']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php $question_number++; ?>
        <?php endwhile; ?>

        <button type="submit" name="submit_quiz" class="btn btn-primary" style="width: 100%;" onclick="return confirm('هل أنت متأكد من تسليم الاختبار؟')">
            <i class="fas fa-check"></i> تسليم الاختبار
        </button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
