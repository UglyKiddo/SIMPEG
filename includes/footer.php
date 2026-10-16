        </main>

        <footer class="app-footer">
            <strong>SIMPEG - Sistem Informasi Manajemen Kepegawaian · Pengadilan Negeri Yogyakarta</strong>
            <span>© <?= date('Y') ?> Pengadilan Negeri Yogyakarta</span>
        </footer>
    </div>

    <div class="sidebar-overlay" hidden></div>
    <dialog class="logout-dialog" aria-labelledby="logout-dialog-title" aria-describedby="logout-dialog-description">
        <div class="logout-dialog-icon" aria-hidden="true">?</div>
        <h2 id="logout-dialog-title">Yakin ingin keluar?</h2>
        <p id="logout-dialog-description">Sesi Anda akan diakhiri.</p>
        <div class="logout-dialog-actions">
            <button class="logout-dialog-confirm" type="button">Ya, Keluar</button>
            <button class="logout-dialog-cancel" type="button">Batal</button>
        </div>
    </dialog>
    <script>
        const sidebar = document.querySelector('.sidebar');
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        const sidebarOverlay = document.querySelector('.sidebar-overlay');
        const logoutForm = document.querySelector('.logout');
        const logoutDialog = document.querySelector('.logout-dialog');
        const logoutConfirm = document.querySelector('.logout-dialog-confirm');
        const logoutCancel = document.querySelector('.logout-dialog-cancel');

        function toggleSidebar(open) {
            sidebar.classList.toggle('is-open', open);
            sidebarOverlay.hidden = !open;
            sidebarToggle.setAttribute('aria-expanded', String(open));
        }

        sidebarToggle.addEventListener('click', () => {
            toggleSidebar(!sidebar.classList.contains('is-open'));
        });
        sidebarOverlay.addEventListener('click', () => toggleSidebar(false));

        logoutForm.addEventListener('submit', (event) => {
            event.preventDefault();
            logoutDialog.showModal();
        });
        logoutConfirm.addEventListener('click', () => logoutForm.submit());
        logoutCancel.addEventListener('click', () => logoutDialog.close());
        logoutDialog.addEventListener('click', (event) => {
            if (event.target === logoutDialog) logoutDialog.close();
        });
    </script>
</body>
</html>
