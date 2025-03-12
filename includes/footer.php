    </div>
    <footer style="background: #2c3e50; color: white; padding: 1rem; margin-top: auto; text-align: center;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <p>جميع الحقوق محفوظة © <?php echo date('Y'); ?> جامعة القدس المفتوحة</p>
        </div>
    </footer>
</body>
</html>
<?php
// Flush and end output buffering started in header.php
if (ob_get_level() > 0) {
    ob_end_flush();
}
?>
