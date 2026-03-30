<div class="container">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <a href="<?= BASE_URL ?>/login" class="btn btn-primary">Back to Login</a>
    <?php endif; ?>
</div>