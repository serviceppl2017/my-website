<?php
require_once '../includes/auth.php';
validateSession();

if (!isStudent()) {
    header('Location: /ss/courses/manage.php');
    exit();
}

$pageTitle = 'مساقاتي';
require_once '../includes/header.php';

// Fetch enrolled courses with progress information
$stmt = $conn->prepare("
    SELECT 
        c.*,
        u.full_name as doctor_name,
        COUNT(DISTINCT un.id) as total_units,
        COUNT(DISTINCT q.id) as total_quizzes,
        COUNT(DISTINCT CASE WHEN qa.passed = 1 THEN q.id END) as passed_quizzes
    FROM courses c
    JOIN users u ON c.doctor_id = u.id
    JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN units un ON c.id = un.course_id
    LEFT JOIN quizzes q ON un.id = q.unit_id
    LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id AND qa.student_id = ?
    WHERE e.student_id = ? AND c.is_active = 1
    GROUP BY c.id
    ORDER BY e.enrollment_date DESC
");
$stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$courses = $stmt->get_result();
?>

<div class="card">
    <h2 class="text-center" style="margin-bottom: 2rem;">مساقاتي</h2>

    <?php if ($courses->num_rows > 0): ?>
        <div class="course-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem;">
            <?php while ($course = $courses->fetch_assoc()): ?>
                <?php
                $progress = $course['total_quizzes'] > 0 
                    ? round(($course['passed_quizzes'] / $course['total_quizzes']) * 100) 
                    : 0;
                ?>
                <div class="course-card" style="background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden;">
                    <div style="background: #2c3e50; color: white; padding: 1.5rem;">
                        <h3 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($course['title']); ?></h3>
                        <p style="font-size: 0.9rem; opacity: 0.9;">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($course['doctor_name']); ?>
                        </p>
                    </div>
                    
                    <div style="padding: 1.5rem;">
                        <p style="color: #666; margin-bottom: 1rem;">
                            <?php echo htmlspecialchars($course['description'] ?: 'لا يوجد وصف للمساق'); ?>
                        </p>
                        
                        <div style="margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="color: #666;">التقدم في المساق</span>
                                <span style="color: #666;"><?php echo $progress; ?>%</span>
                            </div>
                            <div style="background: #eee; height: 10px; border-radius: 5px; overflow: hidden;">
                                <div style="background: #27ae60; height: 100%; width: <?php echo $progress; ?>%;"></div>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; color: #666; font-size: 0.9rem;">
                            <div>
                                <i class="fas fa-tasks"></i>
                                <?php echo $course['total_units']; ?> وحدة
                            </div>
                            <div>
                                <i class="fas fa-check-circle"></i>
                                <?php echo $course['passed_quizzes']; ?>/<?php echo $course['total_quizzes']; ?> اختبار
                            </div>
                        </div>
                        
                        <a href="/ss/courses/view.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-eye"></i> متابعة الدراسة
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center">
            <p style="margin-bottom: 1rem;">لم تسجل في أي مساق بعد</p>
            <a href="/ss/courses/list.php" class="btn btn-primary">
                <i class="fas fa-search"></i> استعرض المساقات المتاحة
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
