(function () {
    'use strict';

    /**
     * 打开弹窗
     * @param {string} id 弹窗遮罩层 id
     */
    window.openModal = function (id) {
        var mask = document.getElementById(id);
        if (!mask) return;
        mask.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    /**
     * 关闭弹窗
     * @param {string} id 弹窗遮罩层 id
     */
    window.closeModal = function (id) {
        var mask = document.getElementById(id);
        if (!mask) return;
        mask.classList.remove('open');
        if (!document.querySelector('.modal-mask.open')) {
            document.body.style.overflow = '';
        }
    };

    /**
     * 点击遮罩空白处关闭弹窗
     */
    document.addEventListener('click', function (e) {
        if (e.target.classList && e.target.classList.contains('modal-mask')) {
            window.closeModal(e.target.id);
        }
    });

    /**
     * ESC 关闭最上层弹窗
     */
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var opened = document.querySelectorAll('.modal-mask.open');
        if (!opened.length) return;
        window.closeModal(opened[opened.length - 1].id);
    });

    /**
     * 带 data-confirm 的表单提交前二次确认
     */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.tagName !== 'FORM') return;
        var message = form.getAttribute('data-confirm');
        if (message && !window.confirm(message)) {
            e.preventDefault();
        }
    }, true);

    /**
     * 日历格点击：填充并打开上班记录弹窗
     */
    document.addEventListener('click', function (e) {
        var cell = e.target.closest && e.target.closest('.cal-cell[data-date]');
        if (!cell) return;
        if (cell.classList.contains('blank')) return;

        var modal = document.getElementById('recordModal');
        if (!modal) return;

        var dateInput = modal.querySelector('input[name="record_date"]');
        var daysInput = modal.querySelector('input[name="work_days"]');
        var otInput = modal.querySelector('input[name="overtime_hours"]');
        var noteInput = modal.querySelector('input[name="note"]');

        if (dateInput) dateInput.value = cell.dataset.date || '';
        if (daysInput) daysInput.value = cell.dataset.days !== '' && cell.dataset.days !== undefined ? cell.dataset.days : '1';
        if (otInput) otInput.value = cell.dataset.ot !== '' && cell.dataset.ot !== undefined ? cell.dataset.ot : '0';
        if (noteInput) noteInput.value = cell.dataset.note || '';

        window.openModal('recordModal');
    });
})();
