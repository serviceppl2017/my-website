<?php
require_once '../includes/auth.php';
validateSession();

if (!isDoctor()) {
    header('Location: /ss/courses/list.php');
    exit();
}

$unit_id = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;

// Verify unit ownership
$stmt = $conn->prepare("
    SELECT u.*, c.title as course_title
    FROM units u
    JOIN courses c ON u.course_id = c.id
    WHERE u.id = ? AND c.doctor_id = ?
");
$stmt->bind_param("ii", $unit_id, $_SESSION['user_id']);
$stmt->execute();
$unit = $stmt->get_result()->fetch_assoc();

if (!$unit) {
    header('Location: /ss/courses/manage.php');
    exit();
}

$pageTitle = 'إدارة محتوى الوحدة - ' . $unit['title'];
require_once '../includes/header.php';

// Handle video upload
if (isset($_POST['upload_video'])) {
    $title = $_POST['video_title'] ?? '';
    $description = $_POST['video_description'] ?? '';
    $video = $_FILES['video'] ?? null;
    
    if (!empty($title) && $video && $video['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['video/mp4', 'video/webm', 'video/ogg'];
        if (in_array($video['type'], $allowed_types)) {
            // Create year/month based directory structure
            $upload_dir = '../uploads/' . date('Y/m');
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = uniqid() . '_' . basename($video['name']);
            $file_path = $upload_dir . '/' . $file_name;
            
            if (move_uploaded_file($video['tmp_name'], $file_path)) {
                $relative_path = str_replace('../', '', $file_path);
                $stmt = $conn->prepare("INSERT INTO videos (unit_id, title, description, file_path) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $unit_id, $title, $description, $relative_path);
                
                if ($stmt->execute()) {
                    $success = "تم رفع الفيديو بنجاح";
                } else {
                    $error = "حدث خطأ أثناء حفظ معلومات الفيديو";
                    unlink($file_path);
                }
            } else {
                $error = "حدث خطأ أثناء رفع الفيديو";
            }
        } else {
            $error = "نوع الملف غير مدعوم. يرجى رفع ملفات MP4, WebM, أو OGG فقط";
        }
    } else {
        $error = "عنوان الفيديو والملف مطلوبان";
    }
}

// Handle quiz creation
if (isset($_POST['create_quiz'])) {
    $title = $_POST['quiz_title'] ?? '';
    $passing_score = (int)($_POST['passing_score'] ?? 70);
    
    if (!empty($title)) {
        $stmt = $conn->prepare("INSERT INTO quizzes (unit_id, title, passing_score) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $unit_id, $title, $passing_score);
        
        if ($stmt->execute()) {
            $quiz_id = $conn->insert_id;
            header("Location: /ss/quizzes/edit.php?quiz_id=" . $quiz_id);
            exit();
        } else {
            $error = "حدث خطأ أثناء إنشاء الاختبار";
        }
    } else {
        $error = "عنوان الاختبار مطلوب";
    }
}

// Fetch videos
$stmt = $conn->prepare("SELECT * FROM videos WHERE unit_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $unit_id);
$stmt->execute();
$videos = $stmt->get_result();

// Fetch quizzes
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE unit_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $unit_id);
$stmt->execute();
$quizzes = $stmt->get_result();
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2><?php echo htmlspecialchars($unit['title']); ?></h2>
            <p style="color: #666;">
                <i class="fas fa-book"></i> <?php echo htmlspecialchars($unit['course_title']); ?>
            </p>
        </div>
        <a href="/ss/units/manage.php?course_id=<?php echo $unit['course_id']; ?>" class="btn btn-primary">
            <i class="fas fa-arrow-right"></i> العودة للوحدات
        </a>
    </div>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Videos Section -->
        <div>
            <h3 style="margin-bottom: 1rem;">الفيديوهات التعليمية</h3>
            
            <form method="POST" action="" enctype="multipart/form-data" style="margin-bottom: 2rem;">
                <div class="form-group">
                    <label for="video_title">عنوان الفيديو</label>
                    <input type="text" id="video_title" name="video_title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="video_description">وصف الفيديو</label>
                    <textarea id="video_description" name="video_description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="video">ملف الفيديو</label>
                    <input type="file" id="video" name="video" class="form-control" accept="video/*" required>
                </div>
                <button type="submit" name="upload_video" class="btn btn-primary">
                    <i class="fas fa-upload"></i> رفع الفيديو
                </button>
            </form>

            <?php if ($videos->num_rows > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>عنوان الفيديو</th>
                                <th>تاريخ الرفع</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($video = $videos->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($video['title']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($video['created_at'])); ?></td>
                                    <td>
                                        <a href="/ss/<?php echo htmlspecialchars($video['file_path']); ?>" class="btn btn-primary" style="font-size: 0.9rem;" target="_blank">
                                            <i class="fas fa-play"></i> عرض
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center">لا توجد فيديوهات حالياً</p>
            <?php endif; ?>
        </div>

        <!-- Quizzes Section -->
        <div>
            <h3 style="margin-bottom: 1rem;">الاختبارات</h3>
            
            <form method="POST" action="" style="margin-bottom: 2rem;">
                <div class="form-group">
                    <label for="quiz_title">عنوان الاختبار</label>
                    <input type="text" id="quiz_title" name="quiz_title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="passing_score">درجة النجاح (%)</label>
                    <input type="number" id="passing_score" name="passing_score" class="form-control" value="70" min="0" max="100" required>
                </div>
                <button type="submit" name="create_quiz" class="btn btn-primary">
                    <i class="fas fa-plus"></i> إنشاء اختبار
                </button>
            </form>

            <?php if ($quizzes->num_rows > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>عنوان الاختبار</th>
                                <th>درجة النجاح</th>
                                <th>تاريخ الإنشاء</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                                    <td><?php echo $quiz['passing_score']; ?>%</td>
                                    <td><?php echo date('Y-m-d', strtotime($quiz['created_at'])); ?></td>
                                    <td>
                                        <a href="/ss/quizzes/edit.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-primary" style="font-size: 0.9rem;">
                                            <i class="fas fa-edit"></i> تعديل
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center">لا توجد اختبارات حالياً</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
