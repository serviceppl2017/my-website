<?php
require_once '../includes/auth.php';
validateSession();

if (!isDoctor()) {
    header('Location: /ss/courses/list.php');
    exit();
}

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Verify course ownership
$stmt = $conn->prepare("SELECT title FROM courses WHERE id = ? AND doctor_id = ?");
$stmt->bind_param("ii", $course_id, $_SESSION['user_id']);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    header('Location: /ss/courses/manage.php');
    exit();
}

$pageTitle = 'إدارة الوحدات - ' . $course['title'];
require_once '../includes/header.php';

// Handle unit creation
if (isset($_POST['create_unit'])) {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // Get max order number
    $stmt = $conn->prepare("SELECT MAX(order_num) as max_order FROM units WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $order_num = ($result['max_order'] ?? 0) + 1;
    
    if (!empty($title)) {
        $stmt = $conn->prepare("INSERT INTO units (course_id, title, description, order_num) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $course_id, $title, $description, $order_num);
        
        if ($stmt->execute()) {
            $success = "تم إنشاء الوحدة بنجاح";
        } else {
            $error = "حدث خطأ أثناء إنشاء الوحدة";
        }
    } else {
        $error = "عنوان الوحدة مطلوب";
    }
}

// Handle unit reordering
if (isset($_POST['reorder'])) {
    $unit_id = (int)$_POST['unit_id'];
    $direction = $_POST['direction'];
    
    $stmt = $conn->prepare("SELECT order_num FROM units WHERE id = ? AND course_id = ?");
    $stmt->bind_param("ii", $unit_id, $course_id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    
    if ($current) {
        if ($direction === 'up') {
            $stmt = $conn->prepare("
                UPDATE units u1
                JOIN units u2 ON u1.course_id = u2.course_id AND u2.order_num = u1.order_num - 1
                SET u1.order_num = u1.order_num - 1,
                    u2.order_num = u2.order_num + 1
                WHERE u1.id = ? AND u1.course_id = ?
            ");
        } else {
            $stmt = $conn->prepare("
                UPDATE units u1
                JOIN units u2 ON u1.course_id = u2.course_id AND u2.order_num = u1.order_num + 1
                SET u1.order_num = u1.order_num + 1,
                    u2.order_num = u2.order_num - 1
                WHERE u1.id = ? AND u1.course_id = ?
            ");
        }
        $stmt->bind_param("ii", $unit_id, $course_id);
        $stmt->execute();
    }
}

// Fetch units with video and quiz counts
$stmt = $conn->prepare("
    SELECT u.*,
           COUNT(DISTINCT v.id) as video_count,
           COUNT(DISTINCT q.id) as quiz_count
    FROM units u
    LEFT JOIN videos v ON u.id = v.unit_id
    LEFT JOIN quizzes q ON u.id = q.unit_id
    WHERE u.course_id = ?
    GROUP BY u.id
    ORDER BY u.order_num ASC
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$units = $stmt->get_result();
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2>إدارة الوحدات - <?php echo htmlspecialchars($course['title']); ?></h2>
        <a href="/ss/courses/manage.php" class="btn btn-primary">
            <i class="fas fa-arrow-right"></i> العودة للمساقات
        </a>
    </div>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Create New Unit Form -->
    <form method="POST" action="" style="max-width: 600px; margin: 0 auto 2rem auto;">
        <h3 style="margin-bottom: 1rem;">إنشاء وحدة جديدة</h3>
        <div class="form-group">
            <label for="title">عنوان الوحدة</label>
            <input type="text" id="title" name="title" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="description">وصف الوحدة</label>
            <textarea id="description" name="description" class="form-control" rows="3"></textarea>
        </div>
        <button type="submit" name="create_unit" class="btn btn-primary">
            <i class="fas fa-plus"></i> إنشاء وحدة
        </button>
    </form>

    <!-- Units List -->
    <h3 style="margin-bottom: 1rem;">الوحدات الحالية</h3>
    <?php if ($units->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>الترتيب</th>
                        <th>عنوان الوحدة</th>
                        <th>عدد الفيديوهات</th>
                        <th>عدد الاختبارات</th>
                        <th>تاريخ الإنشاء</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($unit = $units->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="unit_id" value="<?php echo $unit['id']; ?>">
                                        <input type="hidden" name="direction" value="up">
                                        <button type="submit" name="reorder" class="btn btn-primary" style="padding: 0.25rem 0.5rem;" <?php echo $unit['order_num'] === 1 ? 'disabled' : ''; ?>>
                                            <i class="fas fa-arrow-up"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="unit_id" value="<?php echo $unit['id']; ?>">
                                        <input type="hidden" name="direction" value="down">
                                        <button type="submit" name="reorder" class="btn btn-primary" style="padding: 0.25rem 0.5rem;" <?php echo $unit['order_num'] === $units->num_rows ? 'disabled' : ''; ?>>
                                            <i class="fas fa-arrow-down"></i>
                                        </button>
                                    </form>
                                    <?php echo $unit['order_num']; ?>
                                </div>
                            </td>
                            <td>
                                <a href="/ss/units/content.php?unit_id=<?php echo $unit['id']; ?>" style="text-decoration: none; color: #2980b9;">
                                    <?php echo htmlspecialchars($unit['title']); ?>
                                </a>
                            </td>
                            <td><?php echo $unit['video_count']; ?></td>
                            <td><?php echo $unit['quiz_count']; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($unit['created_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="/ss/units/content.php?unit_id=<?php echo $unit['id']; ?>" class="btn btn-primary" style="font-size: 0.9rem;">
                                        <i class="fas fa-edit"></i> إدارة المحتوى
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-center">لا توجد وحدات حالياً. قم بإنشاء وحدة جديدة للبدء.</p>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
