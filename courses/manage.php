<?php
require_once '../includes/auth.php';
validateSession();

if (!isDoctor()) {
    header('Location: /ss/courses/list.php');
    exit();
}

$pageTitle = 'إدارة المساقات';
require_once '../includes/header.php';

// Handle course activation/deactivation
if (isset($_POST['toggle_status'])) {
    $course_id = (int)$_POST['course_id'];
    $new_status = $_POST['new_status'] === '1' ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE courses SET is_active = ? WHERE id = ? AND doctor_id = ?");
    $stmt->bind_param("iii", $new_status, $course_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $success = $new_status ? "تم تفعيل المساق بنجاح" : "تم تعطيل المساق بنجاح";
    } else {
        $error = "حدث خطأ أثناء تحديث حالة المساق";
    }
}

// Handle course creation
if (isset($_POST['create_course'])) {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (!empty($title)) {
        $stmt = $conn->prepare("INSERT INTO courses (title, description, doctor_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $title, $description, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = "تم إنشاء المساق بنجاح";
        } else {
            $error = "حدث خطأ أثناء إنشاء المساق";
        }
    } else {
        $error = "عنوان المساق مطلوب";
    }
}

// Fetch doctor's courses
$stmt = $conn->prepare("
    SELECT c.*, 
           COUNT(DISTINCT e.student_id) as enrolled_students,
           COUNT(DISTINCT u.id) as unit_count
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN units u ON c.id = u.course_id
    WHERE c.doctor_id = ?
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$courses = $stmt->get_result();
?>

<div class="card">
    <h2 class="text-center" style="margin-bottom: 2rem;">إدارة المساقات</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Create New Course Form -->
    <form method="POST" action="" style="max-width: 600px; margin: 0 auto 2rem auto;">
        <h3 style="margin-bottom: 1rem;">إنشاء مساق جديد</h3>
        <div class="form-group">
            <label for="title">عنوان المساق</label>
            <input type="text" id="title" name="title" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="description">وصف المساق</label>
            <textarea id="description" name="description" class="form-control" rows="3"></textarea>
        </div>
        <button type="submit" name="create_course" class="btn btn-primary">
            <i class="fas fa-plus"></i> إنشاء مساق
        </button>
    </form>

    <!-- Courses List -->
    <h3 style="margin-bottom: 1rem;">المساقات الحالية</h3>
    <?php if ($courses->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>عنوان المساق</th>
                        <th>عدد الطلاب</th>
                        <th>عدد الوحدات</th>
                        <th>الحالة</th>
                        <th>تاريخ الإنشاء</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($course = $courses->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <a href="/ss/units/manage.php?course_id=<?php echo $course['id']; ?>" style="text-decoration: none; color: #2980b9;">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </a>
                            </td>
                            <td><?php echo $course['enrolled_students']; ?></td>
                            <td><?php echo $course['unit_count']; ?></td>
                            <td>
                                <?php if ($course['is_active']): ?>
                                    <span style="color: #27ae60;">مفعل</span>
                                <?php else: ?>
                                    <span style="color: #c0392b;">معطل</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($course['created_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="/ss/units/manage.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary" style="font-size: 0.9rem;">
                                        <i class="fas fa-tasks"></i> إدارة الوحدات
                                    </a>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo $course['is_active'] ? '0' : '1'; ?>">
                                        <button type="submit" name="toggle_status" class="btn <?php echo $course['is_active'] ? 'btn-danger' : 'btn-primary'; ?>" style="font-size: 0.9rem;">
                                            <?php if ($course['is_active']): ?>
                                                <i class="fas fa-times"></i> تعطيل
                                            <?php else: ?>
                                                <i class="fas fa-check"></i> تفعيل
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-center">لا توجد مساقات حالياً. قم بإنشاء مساق جديد للبدء.</p>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
