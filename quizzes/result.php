<?php
require_once '../includes/auth.php';
validateSession();

if (!isStudent()) {
    header('Location: /ss/courses/manage.php');
    exit();
}

$attempt_id = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0;

// Verify attempt ownership and get details
$stmt = $conn->prepare("
    SELECT 
        qa.*,
        q.title as quiz_title,
        q.passing_score,
        u.title as unit_title,
        c.id as course_id,
        c.title as course_title
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    JOIN units u ON q.unit_id = u.id
    JOIN courses c ON u.course_id = c.id
    WHERE qa.id = ? AND qa.student_id = ?
");
$stmt->bind_param("ii", $attempt_id, $_SESSION['user_id']);
$stmt->execute();
$attempt = $stmt->get_result()->fetch_assoc();

if (!$attempt) {
    header('Location: /ss/courses/enrolled.php');
    exit();
}

$pageTitle = 'نتيجة الاختبار - ' . $attempt['quiz_title'];
require_once '../includes/header.php';

// Get next unit's quiz if this one was passed
$next_quiz = null;
if ($attempt['passed']) {
    $stmt = $conn->prepare("
        SELECT q.id, q.title, u.title as unit_title
        FROM units u1
        JOIN units u2 ON u2.course_id = u1.course_id AND u2.order_num = u1.order_num + 1
        JOIN quizzes q ON q.unit_id = u2.id
        WHERE u1.id = (
            SELECT unit_id 
            FROM quizzes 
            WHERE id = ?
        )
        LIMIT 1
    ");
    $stmt->bind_param("i", $attempt['quiz_id']);
    $stmt->execute();
    $next_quiz = $stmt->get_result()->fetch_assoc();
}
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2><?php echo htmlspecialchars($attempt['quiz_title']); ?></h2>
            <p style="color: #666;">
                <i class="fas fa-book"></i> <?php echo htmlspecialchars($attempt['course_title']); ?> - 
                <?php echo htmlspecialchars($attempt['unit_title']); ?>
            </p>
        </div>
        <a href="/ss/courses/view.php?course_id=<?php echo $attempt['course_id']; ?>" class="btn btn-primary">
            <i class="fas fa-arrow-right"></i> العودة للمساق
        </a>
    </div>

    <div style="text-align: center; margin-bottom: 3rem;">
        <?php if ($attempt['passed']): ?>
            <div style="color: #27ae60; font-size: 4rem; margin-bottom: 1rem;">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 style="color: #27ae60; margin-bottom: 0.5rem;">تهانينا! لقد نجحت في الاختبار</h2>
        <?php else: ?>
            <div style="color: #c0392b; font-size: 4rem; margin-bottom: 1rem;">
                <i class="fas fa-times-circle"></i>
            </div>
            <h2 style="color: #c0392b; margin-bottom: 0.5rem;">للأسف، لم تحقق درجة النجاح المطلوبة</h2>
        <?php endif; ?>
        
        <div style="font-size: 1.2rem; margin-bottom: 2rem;">
            <p>درجتك: <strong><?php echo $attempt['score']; ?>%</strong></p>
            <p>درجة النجاح المطلوبة: <strong><?php echo $attempt['passing_score']; ?>%</strong></p>
        </div>
    </div>

    <?php if ($attempt['passed'] && $next_quiz): ?>
        <div style="text-align: center; margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1rem;">الخطوة التالية</h3>
            <p style="margin-bottom: 1rem;">
                يمكنك الآن الانتقال إلى الوحدة التالية والبدء في اختبارها:
                <strong><?php echo htmlspecialchars($next_quiz['unit_title']); ?></strong>
            </p>
            <a href="/ss/quizzes/take.php?quiz_id=<?php echo $next_quiz['id']; ?>" class="btn btn-primary">
                <i class="fas fa-play"></i> بدء الاختبار التالي
            </a>
        </div>
    <?php elseif (!$attempt['passed']): ?>
        <div style="text-align: center; margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1rem;">ماذا بعد؟</h3>
            <p style="margin-bottom: 1rem;">
                راجع محتوى الوحدة جيداً ثم حاول الاختبار مرة أخرى.
                تذكر أن عليك تحقيق درجة <?php echo $attempt['passing_score']; ?>% على الأقل للنجاح.
            </p>
            <a href="/ss/quizzes/take.php?quiz_id=<?php echo $attempt['quiz_id']; ?>" class="btn btn-primary">
                <i class="fas fa-redo"></i> إعادة المحاولة
            </a>
        </div>
    <?php endif; ?>

    <div style="text-align: center;">
        <a href="/ss/courses/view.php?course_id=<?php echo $attempt['course_id']; ?>" class="btn btn-primary">
            <i class="fas fa-book"></i> العودة لمحتوى المساق
        </a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
