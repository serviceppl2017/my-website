<?php
require_once '../includes/auth.php';
validateSession();

if (!isStudent()) {
    header('Location: /ss/courses/manage.php');
    exit();
}

$pageTitle = 'المساقات المتاحة';
require_once '../includes/header.php';

// Handle course enrollment
if (isset($_POST['enroll'])) {
    $course_id = (int)$_POST['course_id'];
    
    // Check if already enrolled
    $stmt = $conn->prepare("SELECT 1 FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->bind_param("ii", $_SESSION['user_id'], $course_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $_SESSION['user_id'], $course_id);
        
        if ($stmt->execute()) {
            $success = "تم التسجيل في المساق بنجاح";
        } else {
            $error = "حدث خطأ أثناء التسجيل في المساق";
        }
    } else {
        $error = "أنت مسجل بالفعل في هذا المساق";
    }
}

// Fetch available courses with enrollment status
$stmt = $conn->prepare("
    SELECT c.*, 
           u.full_name as doctor_name,
           COUNT(DISTINCT e2.student_id) as enrolled_students,
           COUNT(DISTINCT u2.id) as unit_count,
           e1.student_id IS NOT NULL as is_enrolled
    FROM courses c
    JOIN users u ON c.doctor_id = u.id
    LEFT JOIN enrollments e1 ON c.id = e1.course_id AND e1.student_id = ?
    LEFT JOIN enrollments e2 ON c.id = e2.course_id
    LEFT JOIN units u2 ON c.id = u2.course_id
    WHERE c.is_active = 1
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$courses = $stmt->get_result();
?>

<div class="card">
    <h2 class="text-center" style="margin-bottom: 2rem;">المساقات المتاحة</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($courses->num_rows > 0): ?>
        <div class="course-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem;">
            <?php while ($course = $courses->fetch_assoc()): ?>
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
                        
                        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; color: #666; font-size: 0.9rem;">
                            <div>
                                <i class="fas fa-users"></i>
                                <?php echo $course['enrolled_students']; ?> طالب
                            </div>
                            <div>
                                <i class="fas fa-tasks"></i>
                                <?php echo $course['unit_count']; ?> وحدة
                            </div>
                        </div>
                        
                        <?php if ($course['is_enrolled']): ?>
                            <a href="/ss/courses/view.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-eye"></i> عرض المساق
                            </a>
                        <?php else: ?>
                            <form method="POST" action="">
                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                <button type="submit" name="enroll" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-plus"></i> تسجيل في المساق
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="text-center">لا توجد مساقات متاحة حالياً</p>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
