</main>
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> DroneSafe. Todos los derechos reservados.</p>
            <div class="footer-links">
                <a href="#">Política de privacidad</a>
                <a href="#">Términos de servicio</a>
                <a href="#">Contacto</a>
            </div>
        </div>
    </footer>
    <?php if (function_exists('isLoggedIn') && !isLoggedIn()): ?>
    <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
          .then(registration => console.log('SW registrado: ', registration.scope))
          .catch(error => console.log('Error SW: ', error));
      });
    }
    </script>
    <?php endif; ?>
</body>
</html>