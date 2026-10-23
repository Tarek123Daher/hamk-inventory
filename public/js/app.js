document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('#sidebar');
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const closeSidebar = () => {
        sidebar?.classList.remove('open');
        sidebarToggle?.setAttribute('aria-expanded', 'false');
        sidebarToggle?.setAttribute('aria-label', 'Open navigation');
    };
    const openSidebar = () => {
        sidebar?.classList.add('open');
        sidebarToggle?.setAttribute('aria-expanded', 'true');
        sidebarToggle?.setAttribute('aria-label', 'Close navigation');
    };
    sidebarToggle?.addEventListener('click', () => {
        if (sidebar?.classList.contains('open')) closeSidebar();
        else openSidebar();
    });
    document.querySelector('[data-sidebar-close]')?.addEventListener('click', closeSidebar);
    sidebar?.querySelectorAll('nav a').forEach((link) => link.addEventListener('click', closeSidebar));

    const searchForm = document.querySelector('.global-search');
    const searchToggle = document.querySelector('[data-search-toggle]');
    const searchInput = document.querySelector('#global-search-input');
    const closeSearch = () => {
        searchForm?.classList.remove('search-open');
        searchToggle?.setAttribute('aria-expanded', 'false');
        searchToggle?.setAttribute('aria-label', 'Open search');
    };
    searchToggle?.addEventListener('click', () => {
        if (!window.matchMedia('(max-width: 450px)').matches) {
            searchInput?.focus();
            return;
        }
        const isOpen = searchForm?.classList.toggle('search-open') ?? false;
        searchToggle.setAttribute('aria-expanded', String(isOpen));
        searchToggle.setAttribute('aria-label', isOpen ? 'Close search' : 'Open search');
        if (isOpen) searchInput?.focus();
        else searchToggle.focus();
    });
    document.addEventListener('click', (event) => {
        if (searchForm?.classList.contains('search-open') && !searchForm.contains(event.target)) closeSearch();
    });
    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSidebar();
            closeSearch();
        }
    });
    window.addEventListener('resize', () => {
        if (window.innerWidth > 760) closeSidebar();
        if (window.innerWidth > 450) closeSearch();
    });

    document.querySelectorAll('[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    document.querySelector('[data-print]')?.addEventListener('click', () => window.print());

    const imageInput = document.querySelector('[data-image-input]');
    const imagePreview = document.querySelector('[data-image-preview]');
    imageInput?.addEventListener('change', () => {
        const [file] = imageInput.files || [];
        if (file && imagePreview) {
            imagePreview.src = URL.createObjectURL(file);
            imagePreview.classList.remove('hidden');
        }
    });

    const specList = document.querySelector('[data-spec-list]');
    document.querySelector('[data-add-spec]')?.addEventListener('click', () => {
        if (!specList) return;
        const index = specList.querySelectorAll('.spec-row').length;
        const row = document.createElement('div');
        row.className = 'spec-row';
        row.innerHTML = `<input name="attributes[${index}][name]" placeholder="Specification name" maxlength="120"><input name="attributes[${index}][value]" placeholder="Value" maxlength="500"><button type="button" class="button ghost" data-remove-row>Remove</button>`;
        specList.append(row);
        row.querySelector('[data-remove-row]')?.addEventListener('click', () => row.remove());
    });
    document.querySelectorAll('[data-remove-row]').forEach((button) => {
        button.addEventListener('click', () => button.closest('.spec-row')?.remove());
    });

    const componentSelect = document.querySelector('[data-borrow-component]');
    const kitSelect = document.querySelector('[data-borrow-kit]');
    componentSelect?.addEventListener('change', () => {
        if (componentSelect.value && kitSelect) kitSelect.value = '';
    });
    kitSelect?.addEventListener('change', () => {
        if (kitSelect.value && componentSelect) componentSelect.value = '';
    });
});
