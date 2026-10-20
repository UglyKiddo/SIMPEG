        </main>

        <footer class="app-footer">
            <strong>SIMPEG - Sistem Informasi Manajemen Kepegawaian · Pengadilan Negeri Yogyakarta</strong>
            <span>© <?= date('Y') ?> Pengadilan Negeri Yogyakarta</span>
        </footer>
    </div>

    <div class="sidebar-overlay" hidden></div>
    <dialog class="confirmation-dialog" aria-labelledby="confirmation-dialog-title" aria-describedby="confirmation-dialog-description">
        <div class="confirmation-dialog-icon" aria-hidden="true">?</div>
        <h2 id="confirmation-dialog-title"></h2>
        <p id="confirmation-dialog-description"></p>
        <div class="confirmation-dialog-actions">
            <button class="confirmation-dialog-confirm" type="button"></button>
            <button class="confirmation-dialog-cancel" type="button">Batal</button>
        </div>
    </dialog>
    <script>
        const sidebar = document.querySelector('.sidebar');
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        const sidebarOverlay = document.querySelector('.sidebar-overlay');
        const confirmationDialog = document.querySelector('.confirmation-dialog');
        const confirmationTitle = document.querySelector('#confirmation-dialog-title');
        const confirmationDescription = document.querySelector('#confirmation-dialog-description');
        const confirmationConfirm = document.querySelector('.confirmation-dialog-confirm');
        const confirmationCancel = document.querySelector('.confirmation-dialog-cancel');
        let pendingForm = null;
        let pendingSubmitter = null;
        let approvedForm = null;

        function toggleSidebar(open) {
            sidebar.classList.toggle('is-open', open);
            sidebarOverlay.hidden = !open;
            sidebarToggle.setAttribute('aria-expanded', String(open));
        }

        sidebarToggle.addEventListener('click', () => {
            toggleSidebar(!sidebar.classList.contains('is-open'));
        });
        sidebarOverlay.addEventListener('click', () => toggleSidebar(false));

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (approvedForm === form) {
                approvedForm = null;
                return;
            }

            const submitter = event.submitter || null;
            const confirmation = submitter && submitter.dataset.confirmTitle
                ? submitter
                : form;
            if (!confirmation.dataset.confirmTitle) return;

            event.preventDefault();
            pendingForm = form;
            pendingSubmitter = submitter;
            confirmationTitle.textContent = confirmation.dataset.confirmTitle;
            confirmationDescription.textContent = confirmation.dataset.confirmMessage || '';
            confirmationConfirm.textContent = confirmation.dataset.confirmButton || 'Ya, Lanjutkan';
            confirmationDialog.showModal();
        });

        confirmationConfirm.addEventListener('click', () => {
            if (!pendingForm) return;
            const form = pendingForm;
            const submitter = pendingSubmitter;
            pendingForm = null;
            pendingSubmitter = null;
            approvedForm = form;
            confirmationDialog.close();
            if (submitter) {
                form.requestSubmit(submitter);
            } else {
                form.requestSubmit();
            }
        });
        confirmationCancel.addEventListener('click', () => confirmationDialog.close());
        confirmationDialog.addEventListener('click', (event) => {
            if (event.target === confirmationDialog) confirmationDialog.close();
        });
        confirmationDialog.addEventListener('close', () => {
            pendingForm = null;
            pendingSubmitter = null;
        });
    </script>
</body>
</html>
