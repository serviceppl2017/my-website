<?php
require_once '../includes/auth.php';
validateSession();

if (!isDoctor()) {
    header('Location: /ss/courses/list.php');
    exit();
}

$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;

// Verify quiz ownership
$stmt = $conn->prepare("
    SELECT q.*, u.title as unit_title, c.id as course_id
    FROM quizzes q
    JOIN units u ON q.unit_id = u.id
    JOIN courses c ON u.course_id = c.id
    WHERE q.id = ? AND c.doctor_id = ?
");
$stmt->bind_param("ii", $quiz_id, $_SESSION['user_id']);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();

if (!$quiz) {
    header('Location: /ss/courses/manage.php');
    exit();
}

$pageTitle = 'تعديل الاختبار - ' . $quiz['title'];
require_once '../includes/header.php';

// Handle adding multiple choice question
if (isset($_POST['add_multiple_choice'])) {
    $question_text = $_POST['question_text'] ?? '';
    $answers = $_POST['answers'] ?? [];
    $correct_answer = $_POST['correct_answer'] ?? '';
    
    if (!empty($question_text) && !empty($answers) && isset($correct_answer)) {
        $stmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, question_type) VALUES (?, ?, 'multiple_choice')");
        $stmt->bind_param("is", $quiz_id, $question_text);
        
        if ($stmt->execute()) {
            $question_id = $conn->insert_id;
            
            foreach ($answers as $index => $answer_text) {
                if (!empty($answer_text)) {
                    $is_correct = $index == $correct_answer;
                    $stmt = $conn->prepare("INSERT INTO quiz_answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
                    $stmt->bind_param("isi", $question_id, $answer_text, $is_correct);
                    $stmt->execute();
                }
            }
            $success = "تم إضافة السؤال بنجاح";
        } else {
            $error = "حدث خطأ أثناء إضافة السؤال";
        }
    } else {
        $error = "جميع الحقول مطلوبة";
    }
}

// Handle adding true/false question
if (isset($_POST['add_true_false'])) {
    $question_text = $_POST['question_text'] ?? '';
    $correct_answer = $_POST['correct_answer'] ?? '';
    
    if (!empty($question_text) && isset($correct_answer)) {
        $stmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, question_type) VALUES (?, ?, 'true_false')");
        $stmt->bind_param("is", $quiz_id, $question_text);
        
        if ($stmt->execute()) {
            $question_id = $conn->insert_id;
            
            // Add True answer
            $stmt = $conn->prepare("INSERT INTO quiz_answers (question_id, answer_text, is_correct) VALUES (?, 'صح', ?)");
            $is_true = $correct_answer === 'true';
            $stmt->bind_param("ii", $question_id, $is_true);
            $stmt->execute();
            
            // Add False answer
            $stmt = $conn->prepare("INSERT INTO quiz_answers (question_id, answer_text, is_correct) VALUES (?, 'خطأ', ?)");
            $is_false = $correct_answer === 'false';
            $stmt->bind_param("ii", $question_id, $is_false);
            $stmt->execute();
            
            $success = "تم إضافة السؤال بنجاح";
        } else {
            $error = "حدث خطأ أثناء إضافة السؤال";
        }
    } else {
        $error = "جميع الحقول مطلوبة";
    }
}

// Handle deleting question
if (isset($_POST['delete_question'])) {
    $question_id = (int)$_POST['question_id'];
    
    $stmt = $conn->prepare("
        DELETE q FROM quiz_questions q
        JOIN quizzes qz ON q.quiz_id = qz.id
        JOIN units u ON qz.unit_id = u.id
        JOIN courses c ON u.course_id = c.id
        WHERE q.id = ? AND c.doctor_id = ?
    ");
    $stmt->bind_param("ii", $question_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $success = "تم حذف السؤال بنجاح";
    } else {
        $error = "حدث خطأ أثناء حذف السؤال";
    }
}

// Fetch questions with answers
$stmt = $conn->prepare("
    SELECT q.*, GROUP_CONCAT(
        CONCAT(a.id, ':', a.answer_text, ':', a.is_correct)
        ORDER BY a.id ASC
        SEPARATOR '|'
    ) as answers
    FROM quiz_questions q
    LEFT JOIN quiz_answers a ON q.id = a.question_id
    WHERE q.quiz_id = ?
    GROUP BY q.id
    ORDER BY q.created_at ASC
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
                <i class="fas fa-book"></i> <?php echo htmlspecialchars($quiz['unit_title']); ?>
            </p>
        </div>
        <a href="/ss/units/content.php?unit_id=<?php echo $quiz['unit_id']; ?>" class="btn btn-primary">
            <i class="fas fa-arrow-right"></i> العودة للوحدة
        </a>
    </div>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Multiple Choice Question Form -->
        <div>
            <h3 style="margin-bottom: 1rem;">إضافة سؤال اختيار من متعدد</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="mc_question">نص السؤال</label>
                    <textarea id="mc_question" name="question_text" class="form-control" rows="3" required></textarea>
                </div>
                <?php for ($i = 0; $i < 4; $i++): ?>
                    <div class="form-group" style="display: flex; gap: 1rem; align-items: center;">
                        <input type="radio" name="correct_answer" value="<?php echo $i; ?>" required>
                        <input type="text" name="answers[]" class="form-control" placeholder="الإجابة <?php echo $i + 1; ?>" required>
                    </div>
                <?php endfor; ?>
                <button type="submit" name="add_multiple_choice" class="btn btn-primary">
                    <i class="fas fa-plus"></i> إضافة سؤال
                </button>
            </form>
        </div>

        <!-- True/False Question Form -->
        <div>
            <h3 style="margin-bottom: 1rem;">إضافة سؤال صح/خطأ</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="tf_question">نص السؤال</label>
                    <textarea id="tf_question" name="question_text" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <div style="display: flex; gap: 2rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="radio" name="correct_answer" value="true" required>
                            صح
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="radio" name="correct_answer" value="false" required>
                            خطأ
                        </label>
                    </div>
                </div>
                <button type="submit" name="add_true_false" class="btn btn-primary">
                    <i class="fas fa-plus"></i> إضافة سؤال
                </button>
            </form>
        </div>
    </div>

    <!-- Questions List -->
    <h3 style="margin: 2rem 0 1rem;">الأسئلة الحالية</h3>
    <?php if ($questions->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>نوع السؤال</th>
                        <th>نص السؤال</th>
                        <th>الإجابات</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($question = $questions->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php echo $question['question_type'] === 'multiple_choice' ? 'اختيار من متعدد' : 'صح/خطأ'; ?>
                            </td>
                            <td><?php echo htmlspecialchars($question['question_text']); ?></td>
                            <td>
                                <?php
                                $answers = array_map(function($answer) {
                                    list($id, $text, $is_correct) = explode(':', $answer);
                                    return sprintf(
                                        '%s %s',
                                        htmlspecialchars($text),
                                        $is_correct ? '✓' : ''
                                    );
                                }, explode('|', $question['answers']));
                                echo implode('<br>', $answers);
                                ?>
                            </td>
                            <td>
                                <form method="POST" action="" onsubmit="return confirm('هل أنت متأكد من حذف هذا السؤال؟')">
                                    <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                    <button type="submit" name="delete_question" class="btn btn-danger" style="font-size: 0.9rem;">
                                        <i class="fas fa-trash"></i> حذف
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-center">لا توجد أسئلة حالياً</p>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
