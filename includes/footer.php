        </main>

        <footer class="app-footer">
            <strong>SIMPEG - Sistem Informasi Manajemen Kepegawaian · Pengadilan Negeri Yogyakarta</strong>
            <span>© <?= date('Y') ?> Pengadilan Negeri Yogyakarta</span>
        </footer>
    </div>

    <div class="sidebar-overlay" hidden></div>
    <script>
        const sidebar = document.querySelector('.sidebar');
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        const sidebarOverlay = document.querySelector('.sidebar-overlay');

        function toggleSidebar(open) {
            sidebar.classList.toggle('is-open', open);
            sidebarOverlay.hidden = !open;
            sidebarToggle.setAttribute('aria-expanded', String(open));
        }

        sidebarToggle.addEventListener('click', () => {
            toggleSidebar(!sidebar.classList.contains('is-open'));
        });
        sidebarOverlay.addEventListener('click', () => toggleSidebar(false));
    </script>
</body>
</html>
