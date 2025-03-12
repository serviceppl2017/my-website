<?php
require_once '../includes/auth.php';
validateSession();

if (!isStudent()) {
    header('Location: /ss/courses/manage.php');
    exit();
}

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Verify enrollment
$stmt = $conn->prepare("
    SELECT c.*, u.full_name as doctor_name
    FROM courses c
    JOIN users u ON c.doctor_id = u.id
    JOIN enrollments e ON c.id = e.course_id
    WHERE c.id = ? AND e.student_id = ? AND c.is_active = 1
");
$stmt->bind_param("ii", $course_id, $_SESSION['user_id']);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    header('Location: /ss/courses/enrolled.php');
    exit();
}

$pageTitle = $course['title'];
require_once '../includes/header.php';

// First, get all units in order
$stmt = $conn->prepare("
    SELECT 
        u.*,
        COUNT(DISTINCT v.id) as video_count,
        COUNT(DISTINCT q.id) as quiz_count,
        COUNT(DISTINCT CASE WHEN qa.passed = 1 THEN q.id END) as passed_quizzes
    FROM units u
    LEFT JOIN videos v ON u.id = v.unit_id
    LEFT JOIN quizzes q ON u.id = q.unit_id
    LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id AND qa.student_id = ?
    WHERE u.course_id = ?
    GROUP BY u.id
    ORDER BY u.order_num ASC
");

if ($stmt === false) {
    die('خطأ في الاستعلام: ' . $conn->error);
}

$stmt->bind_param("ii", $_SESSION['user_id'], $course_id);
$result = $stmt->execute();

if ($result === false) {
    die('خطأ في تنفيذ الاستعلام: ' . $stmt->error);
}

$units = $stmt->get_result();

if ($units === false) {
    die('خطأ في استرجاع النتائج: ' . $stmt->error);
}

// Process units to determine which are unlocked
$processed_units = array();
$is_unlocked = true; // First unit is always unlocked

while ($unit = $units->fetch_assoc()) {
    // If this is not the first unit, check if previous unit's quiz was passed
    if ($unit['order_num'] > 1) {
        $prev_stmt = $conn->prepare("
            SELECT COUNT(*) as incomplete_quizzes
            FROM quizzes q
            LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id AND qa.student_id = ?
            WHERE q.unit_id = (
                SELECT id FROM units 
                WHERE course_id = ? AND order_num = ?
            )
            AND (qa.passed IS NULL OR qa.passed = 0)
        ");
        $prev_order = $unit['order_num'] - 1;
        $prev_stmt->bind_param("iii", $_SESSION['user_id'], $course_id, $prev_order);
        $prev_stmt->execute();
        $prev_result = $prev_stmt->get_result()->fetch_assoc();
        
        $is_unlocked = ($prev_result['incomplete_quizzes'] == 0);
    }
    
    $unit['is_unlocked'] = $is_unlocked;
    $processed_units[] = $unit;
}

// Reset pointer for template usage
$units = new ArrayObject($processed_units);
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2><?php echo htmlspecialchars($course['title']); ?></h2>
            <p style="color: #666;">
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($course['doctor_name']); ?>
            </p>
        </div>
        <a href="/ss/courses/enrolled.php" class="btn btn-primary">
            <i class="fas fa-arrow-right"></i> العودة لمساقاتي
        </a>
    </div>

    <?php if ($units->count() > 0): ?>
        <div class="units-list">
            <?php foreach ($units as $unit): ?>
                <?php
                $progress = $unit['quiz_count'] > 0 
                    ? round(($unit['passed_quizzes'] / $unit['quiz_count']) * 100) 
                    : 0;
                ?>
                <div class="unit-card" style="background: white; border: 1px solid #eee; border-radius: 10px; margin-bottom: 1.5rem; overflow: hidden;">
                    <div style="background: <?php echo $unit['is_unlocked'] ? '#f8f9fa' : '#eee'; ?>; padding: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="margin: 0; <?php echo $unit['is_unlocked'] ? '' : 'color: #666;'; ?>">
                                <?php echo htmlspecialchars($unit['title']); ?>
                            </h3>
                            <?php if (!$unit['is_unlocked']): ?>
                                <i class="fas fa-lock" style="color: #666;"></i>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($unit['description']): ?>
                            <p style="color: #666; margin: 1rem 0 0 0;">
                                <?php echo htmlspecialchars($unit['description']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($unit['is_unlocked']): ?>
                        <div style="padding: 1.5rem;">
                            <?php if ($unit['video_count'] > 0): ?>
                                <div style="margin-bottom: 1.5rem;">
                                    <h4 style="margin-bottom: 1rem;">الفيديوهات التعليمية</h4>
                                    <?php
                                    $stmt = $conn->prepare("SELECT * FROM videos WHERE unit_id = ? ORDER BY created_at ASC");
                                    $stmt->bind_param("i", $unit['id']);
                                    $stmt->execute();
                                    $videos = $stmt->get_result();
                                    ?>
                                    
                                    <div style="display: grid; gap: 1rem;">
                                        <?php while ($video = $videos->fetch_assoc()): ?>
                                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                                                <div>
                                                    <i class="fas fa-play-circle"></i>
                                                    <?php echo htmlspecialchars($video['title']); ?>
                                                </div>
                                                <a href="/ss/<?php echo htmlspecialchars($video['file_path']); ?>" class="btn btn-primary" style="font-size: 0.9rem;" target="_blank">
                                                    مشاهدة
                                                </a>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($unit['quiz_count'] > 0): ?>
                                <div>
                                    <h4 style="margin-bottom: 1rem;">الاختبارات</h4>
                                    <?php
                                    $stmt = $conn->prepare("
                                        SELECT q.*, qa.score, qa.passed
                                        FROM quizzes q
                                        LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id AND qa.student_id = ?
                                        WHERE q.unit_id = ?
                                        ORDER BY q.created_at ASC
                                    ");
                                    $stmt->bind_param("ii", $_SESSION['user_id'], $unit['id']);
                                    $stmt->execute();
                                    $quizzes = $stmt->get_result();
                                    ?>
                                    
                                    <div style="display: grid; gap: 1rem;">
                                        <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                                                <div>
                                                    <div style="margin-bottom: 0.5rem;">
                                                        <i class="fas fa-question-circle"></i>
                                                        <?php echo htmlspecialchars($quiz['title']); ?>
                                                    </div>
                                                    <?php if (isset($quiz['score'])): ?>
                                                        <div style="font-size: 0.9rem; color: <?php echo $quiz['passed'] ? '#27ae60' : '#c0392b'; ?>;">
                                                            النتيجة: <?php echo $quiz['score']; ?>% 
                                                            <?php echo $quiz['passed'] ? '(ناجح)' : '(راسب)'; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!isset($quiz['score']) || !$quiz['passed']): ?>
                                                    <a href="/ss/quizzes/take.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-primary" style="font-size: 0.9rem;">
                                                        بدء الاختبار
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-center">لا توجد وحدات في هذا المساق حالياً</p>
    <?php endif; ?>
	
	<div>

<form action="assignments/upload.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="course_id" value="<?= $course_id ?>">
    <input type="file" name="assignment_file" required>
    <button type="submit">رفع الواجب</button>
</form>
</div>

	
	
</div>

<?php require_once '../includes/footer.php'; ?>
