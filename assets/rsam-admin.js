/**
 * Retail Shop Accounting & Management (RSAM)
 * Yeh file tamam (Admin UI) (interactions), (AJAX) (requests), 
 * (form handling), aur (table rendering) ko (manage) karti hai.
 */

// (IIFE) (Immediately Invoked Function Expression) tamam (scope) ko (wrap) karne ke liye
(() => {
	'use strict';

	/** Part 1 — Bunyadi Setup, Utilities, aur Main (Initializer) */

	// (Global Data) (PHP) se (wp_localize_script) ke zariye
	const rsamData = window.rsamData || {
		ajax_url: '',
		nonce: '',
		caps: {},
		strings: {
			loading: 'Loading...',
			errorOccurred: 'An error occurred.',
			confirmDelete: 'Are you sure?',
			noItemsFound: 'No items found.',
		},
	};

	// (Global State)
	const state = {
		currentScreen: null, // (e.g., 'dashboard', 'products')
		currentPage: 1, // (Pagination) ke liye
		currentSearch: '', // (Search) ke liye
		isLoading: false,
		// (UI Elements) (Cache)
		ui: {
			root: null,
			modal: null,
			confirmModal: null,
		},
	};

	// DOM Ready Hone Par (Initialize) Karein
	document.addEventListener('DOMContentLoaded', () => {
		// (Root element) (find) karein
		const rootEl = document.querySelector('.rsam-root[data-screen]');
		if (!rootEl) {
			// Agar (root) nahi mila to kuch na karein
			return;
		}

		state.ui.root = rootEl;
		state.currentScreen = rootEl.dataset.screen;

		// (Screen) ke hisab se (initializer) (call) karein
		initApp();
	});

	/**
	 * Main (App Initializer)
	 * (Screen) ke hisab se (routing) karta hai.
	 */
	function initApp() {
		if (!state.ui.root) return;

		// (Common UI) (templates) (Modals) ko (mount) karein
		initCommonUI();

		// (Screen-specific) (initializer) (call) karein
		switch (state.currentScreen) {
			case 'dashboard':
				initDashboard();
				break;
			case 'products':
				initProducts();
				break;
			case 'purchases':
				initPurchases();
				break;
			case 'sales':
				initSales();
				break;
			case 'expenses':
				initExpenses();
				break;
			case 'employees':
				initEmployees();
				break;
			case 'suppliers':
				initSuppliers();
				break;
			case 'customers':
				initCustomers();
				break;
			case 'reports':
				initReports();
				break;
			case 'settings':
				initSettings();
				break;
			default:
				showError(
					`Unknown screen: ${state.currentScreen}`,
					state.ui.root
				);
		}
	}

	/**
	 * (Common UI Elements) (Modals) ko (mount) karta hai.
	 */
	function initCommonUI() {
		// (Add/Edit Modal)
		const modalTmpl = document.getElementById('rsam-tmpl-modal-form');
		if (modalTmpl) {
			const modalEl = mountTemplate(modalTmpl);
			document.body.appendChild(modalEl);
			state.ui.modal = {
				wrapper: document.querySelector(
					'.rsam-modal-wrapper:not(.rsam-modal-confirm)'
				),
				backdrop: document.querySelector(
					'.rsam-modal-backdrop:not(.rsam-modal-confirm .rsam-modal-backdrop)'
				),
				title: document.querySelector('.rsam-modal-title'),
				body: document.querySelector('.rsam-modal-body'),
				saveBtn: document.querySelector('.rsam-modal-save'),
				cancelBtn: document.querySelector('.rsam-modal-cancel'),
				closeBtn: document.querySelector('.rsam-modal-close'),
			};
			// (Close) (listeners)
			state.ui.modal.backdrop.addEventListener('click', () =>
				closeModal()
			);
			state.ui.modal.cancelBtn.addEventListener('click', () =>
				closeModal()
			);
			state.ui.modal.closeBtn.addEventListener('click', () =>
				closeModal()
			);
		}

		// (Confirm Modal)
		const confirmTmpl = document.getElementById('rsam-tmpl-modal-confirm');
		if (confirmTmpl) {
			const confirmEl = mountTemplate(confirmTmpl);
			document.body.appendChild(confirmEl);
			state.ui.confirmModal = {
				wrapper: document.querySelector('.rsam-modal-confirm'),
				backdrop: document.querySelector(
					'.rsam-modal-confirm .rsam-modal-backdrop'
				),
				title: document.querySelector(
					'.rsam-modal-confirm .rsam-modal-title'
				),
				body: document.querySelector(
					'.rsam-modal-confirm .rsam-modal-body'
				),
				deleteBtn: document.querySelector(
					'.rsam-modal-confirm-delete'
				),
				cancelBtn: document.querySelector(
					'.rsam-modal-confirm .rsam-modal-cancel'
				),
				closeBtn: document.querySelector(
					'.rsam-modal-confirm .rsam-modal-close'
				),
			};
			// (Close) (listeners)
			state.ui.confirmModal.backdrop.addEventListener('click', () =>
				closeConfirmModal()
			);
			state.ui.confirmModal.cancelBtn.addEventListener('click', () =>
				closeConfirmModal()
			);
			state.ui.confirmModal.closeBtn.addEventListener('click', () =>
				closeConfirmModal()
			);
		}
	}

	// -----------------------------------------------------------------
	// (UTILITY FUNCTIONS)
	// -----------------------------------------------------------------

	/**
	 * (WordPress AJAX) ke liye (Wrapper)
	 * @param {string} action (WP AJAX action) ka naam
	 * @param {object} data Bhejne wala (data)
	 * @param {HTMLElement} [loaderEl] (Button) ya (element) jahan (loader) dikhana hai
	 * @returns {Promise<any>}
	 */
	async function wpAjax(action, data = {}, loaderEl = null) {
		if (state.isLoading && loaderEl) return Promise.reject('Loading...');

		// (Loader) (show) karein
		if (loaderEl) {
			setLoading(loaderEl, true);
		}
		state.isLoading = true;

		// (jQuery) ka (AJAX) istemal karein (WordPress (admin) mein (reliable) hai)
		return new Promise((resolve, reject) => {
			window
				.jQuery
				.post(rsamData.ajax_url, {
					action: action,
					nonce: rsamData.nonce,
					...data,
				})
				.done((response) => {
					if (response.success) {
						resolve(response.data);
					} else {
						// (PHP error) ko (reject) karein
						const errorMsg =
							response.data && response.data.message
								? response.data.message
								: rsamData.strings.errorOccurred;
						showToast(errorMsg, 'error');
						reject(errorMsg);
					}
				})
				.fail((jqXHR, textStatus, errorThrown) => {
					// (Network/HTTP error) ko (reject) karein
					console.error(
						'RSAM AJAX Error:',
						textStatus,
						errorThrown,
						jqXHR
					);
					const errorMsg =
						jqXHR.responseJSON && jqXHR.responseJSON.data
							? jqXHR.responseJSON.data.message
							: rsamData.strings.errorOccurred;
					showToast(errorMsg, 'error');
					reject(errorMsg);
				})
				.always(() => {
					// (Loader) (hide) karein
					if (loaderEl) {
						setLoading(loaderEl, false);
					}
					state.isLoading = false;
				});
		});
	}

	/**
	 * Ek (HTML <template>) ko (clone) aur (mount) karta hai.
	 * @param {HTMLTemplateElement} templateEl (Template element)
	 * @returns {DocumentFragment} (Cloned content)
	 */
	function mountTemplate(templateEl) {
		if (!templateEl || templateEl.tagName !== 'TEMPLATE') {
			console.error('Invalid template provided', templateEl);
			return document.createDocumentFragment();
		}
		return templateEl.content.cloneNode(true);
	}

	/**
	 * (Loader) (state) (set) karta hai (aam taur par (button) par).
	 * @param {HTMLElement} el (Element)
	 * @param {boolean} isLoading Kya (loading) (state) (set) karni hai?
	 */
	function setLoading(el, isLoading) {
		if (!el) return;
		el.disabled = isLoading;
		el.classList.toggle('rsam-loading', isLoading);
	}

	/**
	 * (Error message) (container) mein dikhata hai.
	 * @param {string} message (Error) ka paigham
	 * @param {HTMLElement} [container] (Container) (default: root)
	 */
	function showError(message, container = null) {
		const target = container || state.ui.root;
		if (target) {
			target.innerHTML = `<div class="rsam-alert rsam-alert-danger">${escapeHtml(
				message
			)}</div>`;
		}
		console.error('RSAM Error:', message);
	}

	/**
	 * (HTML) (strings) ko (escape) karta hai.
	 * @param {string} str
	 * @returns {string} (Escaped string)
	 */
	function escapeHtml(str) {
		if (str === null || str === undefined) return '';
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	/**
	 * Raqam (Price) ko format karta hai (Yeh (PHP helper) se (match) karta hai).
	 * (Note: (Currency symbol) (settings) se (configurable) hona chahiye)
	 * @param {number|string} price
	 * @returns {string} Formatted raqam
	 */
	function formatPrice(price) {
		const symbol = rsamData.strings.currencySymbol || 'Rs.'; // (Settings) se ayega
		const num = parseFloat(price);
		if (isNaN(num)) {
			return `${symbol} 0.00`;
		}
		return `${symbol} ${num.toLocaleString('en-IN', {
			minimumFractionDigits: 2,
			maximumFractionDigits: 2,
		})}`;
	}

	/**
	 * (Toast) (notification) dikhata hai (Admin notices) ka istemal karke.
	 * @param {string} message Paigham
	 * @param {'success'|'error'|'warning'|'info'} type (Notice) ki (type)
	 */
	function showToast(message, type = 'success') {
		const notice = document.createElement('div');
		notice.className = `notice notice-${type} is-dismissible rsam-toast`;
		notice.innerHTML = `<p>${escapeHtml(message)}</p>`;

		// (Dismiss) (button) (WordPress (style))
		const dismissBtn = document.createElement('button');
		dismissBtn.type = 'button';
		dismissBtn.className = 'notice-dismiss';
		dismissBtn.innerHTML =
			'<span class="screen-reader-text">Dismiss this notice.</span>';
		notice.appendChild(dismissBtn);

		dismissBtn.addEventListener('click', () => {
			notice.remove();
		});

		// (WordPress header) ke (top) par (show) karein
		const headerEnd =
			document.querySelector('.wp-header-end') ||
			document.querySelector('.wrap');
		if (headerEnd) {
			headerEnd.insertAdjacentElement('afterend', notice);
		} else {
			document.body.prepend(notice);
		}

		// 3 (seconds) baad (auto-dismiss)
		setTimeout(() => {
			notice.remove();
		}, 3000);
	}

	/**
	 * (Modal) ko kholta hai aur (content) (set) karta hai.
	 * @param {string} title (Modal) ka (title)
	 * @param {HTMLElement|string} formContent (Form) ya (HTML content)
	 * @param {function} saveCallback (Save) (button) (click) hone par (callback)
	 */
	function openModal(title, formContent, saveCallback) {
		if (!state.ui.modal) return;

		state.ui.modal.title.innerHTML = escapeHtml(title);
		state.ui.modal.body.innerHTML = ''; // Pehle (clear) karein

		if (typeof formContent === 'string') {
			state.ui.modal.body.innerHTML = formContent;
		} else {
			state.ui.modal.body.appendChild(formContent);
		}

		// (Save) (listener) ko (bind) karein
		// Pehle purana (listener) (remove) karein (clone karke)
		const newSaveBtn = state.ui.modal.saveBtn.cloneNode(true);
		state.ui.modal.saveBtn.parentNode.replaceChild(
			newSaveBtn,
			state.ui.modal.saveBtn
		);
		state.ui.modal.saveBtn = newSaveBtn;
		state.ui.modal.saveBtn.addEventListener('click', saveCallback);

		document.body.classList.add('rsam-modal-open');
		state.ui.modal.wrapper.classList.add('rsam-modal-visible');
	}

	/**
	 * (Modal) ko (close) karta hai.
	 */
	function closeModal() {
		if (!state.ui.modal) return;
		document.body.classList.remove('rsam-modal-open');
		state.ui.modal.wrapper.classList.remove('rsam-modal-visible');
		// (Modal body) (clear) karein
		state.ui.modal.body.innerHTML = '';
	}

	/**
	 * (Confirmation Modal) ko kholta hai.
	 * @param {string} title (Title)
	 * @param {string} message Paigham
	 * @param {function} deleteCallback (Delete) (button) (click) hone par (callback)
	 */
	function openConfirmModal(title, message, deleteCallback) {
		if (!state.ui.confirmModal) return;

		state.ui.confirmModal.title.innerHTML = escapeHtml(
			title || rsamData.strings.confirmDelete
		);
		state.ui.confirmModal.body.querySelector('p').innerHTML = escapeHtml(
			message || rsamData.strings.confirmDelete
		);

		// (Delete) (listener) (bind) karein
		const newDeleteBtn = state.ui.confirmModal.deleteBtn.cloneNode(true);
		state.ui.confirmModal.deleteBtn.parentNode.replaceChild(
			newDeleteBtn,
			state.ui.confirmModal.deleteBtn
		);
		state.ui.confirmModal.deleteBtn = newDeleteBtn;
		state.ui.confirmModal.deleteBtn.addEventListener(
			'click',
			deleteCallback
		);

		document.body.classList.add('rsam-modal-open');
		state.ui.confirmModal.wrapper.classList.add('rsam-modal-visible');
	}

	/**
	 * (Confirmation Modal) ko (close) karta hai.
	 */
	function closeConfirmModal() {
		if (!state.ui.confirmModal) return;
		document.body.classList.remove('rsam-modal-open');
		state.ui.confirmModal.wrapper.classList.remove('rsam-modal-visible');
	}

	/**
	 * (Pagination) (controls) banata hai.
	 * @param {HTMLElement} container (Pagination) (container)
	 * @param {object} paginationData (PHP) se (pagination data)
	 * @param {function} callback (Page change) (callback)
	 */
	function renderPagination(container, paginationData, callback) {
		if (!container || !paginationData || paginationData.total_pages <= 1) {
			if (container) container.innerHTML = '';
			return;
		}

		const { current_page, total_pages } = paginationData;
		let html = '<div class="rsam-pagination-links">';

		// (Previous) (button)
		html += `<button type="button" class="button" data-page="${
			current_page - 1
		}" ${current_page === 1 ? 'disabled' : ''}>
            &laquo; ${rsamData.strings.prev || 'Prev'}
        </button>`;

		// (Page numbers) (Logic)
		// (Complex (pagination) (UI) yahan banaya ja sakta hai, abhi (simple) rakhte hain)
		html += `<span class="rsam-pagination-current">
            ${escapeHtml(
				`Page ${current_page} of ${total_pages}`
			)}
        </span>`;

		// (Next) (button)
		html += `<button type="button" class="button" data-page="${
			current_page + 1
		}" ${current_page === total_pages ? 'disabled' : ''}>
            ${rsamData.strings.next || 'Next'} &raquo;
        </button>`;

		html += '</div>';
		container.innerHTML = html;

		// (Listeners) (add) karein
		container.querySelectorAll('button[data-page]').forEach((button) => {
			button.addEventListener('click', (e) => {
				const newPage = parseInt(e.target.dataset.page, 10);
				if (newPage && newPage !== current_page) {
					callback(newPage);
				}
			});
		});
	}

	// (Aglay (Parts) yahan (append) honge)
	// ... (initDashboard, initProducts, etc.)
/**
	 * Part 2 — Dashboard Screen
	 * (Dashboard) (template) ko (mount) karta hai aur (stats) (load) karta hai.
	 */
	function initDashboard() {
		const tmpl = document.getElementById('rsam-tmpl-dashboard');
		if (!tmpl) {
			showError('Dashboard template not found.');
			return;
		}

		// (Template) ko (mount) karein
		const content = mountTemplate(tmpl);
		state.ui.root.innerHTML = ''; // (Loading placeholder) ko (remove) karein
		state.ui.root.appendChild(content);

		// (Stats) (load) karein
		fetchDashboardStats();
	}

	/**
	 * (AJAX) ke zariye (Dashboard) (widgets) ke liye (data) (fetch) karta hai.
	 */
	async function fetchDashboardStats() {
		const statsWidget = state.ui.root.querySelector(
			'.rsam-widget[data-widget="stats"]'
		);
		const topProductsWidget = state.ui.root.querySelector(
			'.rsam-widget[data-widget="top-products"]'
		);
		const lowStockWidget = state.ui.root.querySelector(
			'.rsam-widget[data-widget="low-stock"]'
		);

		try {
			const data = await wpAjax('rsam_get_dashboard_stats');

			// 1. (Overview Stats) (Widget)
			if (statsWidget) {
				statsWidget.classList.remove('rsam-widget-loading');
				statsWidget.querySelector('.rsam-widget-body').innerHTML = `
                    <div class="rsam-stats-grid">
                        <div class="rsam-stat-item">
                            <strong>${rsamData.strings.todaySales || 'Today\'s Sales'}</strong>
                            <span>${escapeHtml(data.today_sales)}</span>
                        </div>
                        <div class="rsam-stat-item">
                            <strong>${rsamData.strings.monthlySales || 'This Month\'s Sales'}</strong>
                            <span>${escapeHtml(data.monthly_sales)}</span>
                        </div>
                        <div class="rsam-stat-item rsam-stat-profit">
                            <strong>${rsamData.strings.monthlyProfit || 'This Month\'s Profit'}</strong>
                            <span>${escapeHtml(data.monthly_profit)}</span>
                        </div>
                        <div class="rsam-stat-item rsam-stat-expense">
                            <strong>${rsamData.strings.monthlyExpenses || 'This Month\'s Expenses'}</strong>
                            <span>${escapeHtml(data.monthly_expenses)}</span>
                        </div>
                        <div class="rsam-stat-item">
                            <strong>${rsamData.strings.stockValue || 'Total Stock Value'}</strong>
                            <span>${escapeHtml(data.stock_value)}</span>
                        </div>
                        <div class="rsam-stat-item rsam-stat-alert">
                            <strong>${rsamData.strings.lowStockItems || 'Low Stock Items'}</strong>
                            <span>${escapeHtml(data.low_stock_count)}</span>
                        </div>
                    </div>
                `;
			}

			// 2. (Top Selling Products) (Widget)
			if (topProductsWidget) {
				topProductsWidget.classList.remove('rsam-widget-loading');
				const body = topProductsWidget.querySelector('.rsam-widget-body');
				if (data.top_products && data.top_products.length > 0) {
					let listHtml = '<ul class="rsam-widget-list">';
					data.top_products.forEach((product) => {
						listHtml += `<li>
                            <span>${escapeHtml(product.name)}</span>
                            <strong>${escapeHtml(
								product.total_quantity
							)} ${rsamData.strings.unitsSold || 'units'}</strong>
                        </li>`;
					});
					listHtml += '</ul>';
					body.innerHTML = listHtml;
				} else {
					body.innerHTML = `<p>${rsamData.strings.noTopProducts || 'No top selling products this month.'}</p>`;
				}
			}

			// 3. (Low Stock) (Widget)
			if (lowStockWidget) {
				lowStockWidget.classList.remove('rsam-widget-loading');
				const body = lowStockWidget.querySelector('.rsam-widget-body');
				if (
					data.low_stock_products &&
					data.low_stock_products.length > 0
				) {
					let listHtml = '<ul class="rsam-widget-list rsam-low-stock-list">';
					data.low_stock_products.forEach((product) => {
						listHtml += `<li>
                            <span>${escapeHtml(product.name)}</span>
                            <strong>${rsamData.strings.inStock || 'In Stock:'} ${escapeHtml(
							product.stock_quantity
						)}</span>
                        </li>`;
					});
					listHtml += '</ul>';
					body.innerHTML = listHtml;
				} else {
					body.innerHTML = `<p>${rsamData.strings.allStockGood || 'All stock levels are good.'}</p>`;
				}
			}
		} catch (error) {
			// (Error) (Main stats widget) mein dikhayein
			if (statsWidget) {
				statsWidget.classList.remove('rsam-widget-loading');
				showError(error, statsWidget.querySelector('.rsam-widget-body'));
			}
			console.error('Failed to load dashboard stats:', error);
		}
	}

	/** Part 2 — Yahan khatam hua */
/**
	 * Part 3 — Products (Inventory) Screen
	 * (Products) (template) ko (mount) karta hai, (list) (fetch) karta hai, aur (CRUD) (handle) karta hai.
	 */
	function initProducts() {
		const tmpl = document.getElementById('rsam-tmpl-products');
		if (!tmpl) {
			showError('Products template not found.');
			return;
		}

		// (Template) ko (mount) karein
		const content = mountTemplate(tmpl);
		state.ui.root.innerHTML = ''; // (Loading placeholder) ko (remove) karein
		state.ui.root.appendChild(content);

		// (UI Elements) ko (cache) karein
		const ui = {
			tableBody: state.ui.root.querySelector('#rsam-products-table-body'),
			pagination: state.ui.root.querySelector(
				'#rsam-products-pagination'
			),
			search: state.ui.root.querySelector('#rsam-product-search'),
			addNewBtn: state.ui.root.querySelector('#rsam-add-new-product'),
			formContainer: state.ui.root.querySelector(
				'#rsam-product-form-container'
			),
		};
		// (UI) ko (state) mein (store) karein (event listeners) ke liye
		state.ui.products = ui;

		// (Initial) (Products) (fetch) karein
		fetchProducts();

		// (Event Listeners)
		// (Search)
		ui.search.addEventListener('keyup', (e) => {
			// (debounce) (timer)
			clearTimeout(state.searchTimer);
			state.searchTimer = setTimeout(() => {
				state.currentSearch = e.target.value;
				state.currentPage = 1; // (Search) par (page 1) par (reset) karein
				fetchProducts();
			}, 500); // 500ms (debounce)
		});

		// (Add New)
		ui.addNewBtn.addEventListener('click', () => {
			openProductForm();
		});
	}

	/**
	 * (AJAX) ke zariye (Products) (fetch) aur (render) karta hai.
	 */
	async function fetchProducts() {
		const { tableBody, pagination } = state.ui.products;
		if (!tableBody) return;

		// (Loading) (state)
		tableBody.innerHTML = `<tr>
            <td colspan="7" class="rsam-list-loading">
                <span class="rsam-loader-spinner"></span> ${rsamData.strings.loading}
            </td>
        </tr>`;

		try {
			const data = await wpAjax('rsam_get_products', {
				page: state.currentPage,
				search: state.currentSearch,
			});

			// (Table) (render) karein
			renderProductsTable(data.products);
			// (Pagination) (render) karein
			renderPagination(
				pagination,
				data.pagination,
				(newPage) => {
					state.currentPage = newPage;
					fetchProducts();
				}
			);
		} catch (error) {
			showError(error, tableBody);
		}
	}

	/**
	 * (Products) (data) ko (table) mein (render) karta hai.
	 * @param {Array} products (Products) ka (array)
	 */
	function renderProductsTable(products) {
		const { tableBody } = state.ui.products;
		tableBody.innerHTML = ''; // (Clear) karein

		if (!products || products.length === 0) {
			tableBody.innerHTML = `<tr>
                <td colspan="7" class="rsam-list-empty">
                    ${rsamData.strings.noItemsFound}
                </td>
            </tr>`;
			return;
		}

		products.forEach((product) => {
			const tr = document.createElement('tr');
			tr.dataset.productId = product.id;
			// (product) (object) ko (element) par (store) karein (edit) ke liye
			tr.dataset.productData = JSON.stringify(product);

			tr.innerHTML = `
                <td>${escapeHtml(product.name)}</td>
                <td>${escapeHtml(product.category)}</td>
                <td>${escapeHtml(product.unit_type)}</td>
                <td>${escapeHtml(
					product.stock_quantity
				)}</td> <td>${escapeHtml(product.stock_value_formatted)}</td>
                <td>${escapeHtml(product.selling_price_formatted)}</td>
                <td class="rsam-list-actions">
                    <button type="button" class="button rsam-edit-btn" title="${rsamData.strings.edit}">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
                    <button type="button" class="button rsam-delete-btn" title="${rsamData.strings.delete}">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </td>
            `;

			// (Action Listeners)
			tr.querySelector('.rsam-edit-btn').addEventListener(
				'click',
				(e) => {
					const row = e.target.closest('tr');
					const data = JSON.parse(row.dataset.productData);
					openProductForm(data);
				}
			);

			tr.querySelector('.rsam-delete-btn').addEventListener(
				'click',
				(e) => {
					const row = e.target.closest('tr');
					const productId = row.dataset.productId;
					const productName = row.cells[0].textContent;
					confirmDeleteProduct(productId, productName);
				}
			);

			tableBody.appendChild(tr);
		});
	}

	/**
	 * (Add/Edit) (Product) (Form Modal) ko kholta hai.
	 * @param {object} [productData] (Edit) ke liye (Product) ka (data)
	 */
	function openProductForm(productData = null) {
		const { formContainer } = state.ui.products;
		const formHtml = formContainer.innerHTML; // (Form) (HTML) ko (template) se (copy) karein
		const isEditing = productData !== null;

		const title = isEditing
			? `${rsamData.strings.edit} Product`
			: `${rsamData.strings.addNew} Product`;

		// (Modal) kholne ke baad (form) ko (populate) karein
		openModal(title, formHtml, async (e) => {
			// (Save callback)
			const saveBtn = e.target;
			const form = state.ui.modal.body.querySelector('#rsam-product-form');
			if (form.checkValidity() === false) {
				form.reportValidity();
				return;
			}

			// (Form data) (serialize) karein
			const formData = new URLSearchParams(new FormData(form)).toString();

			try {
				const result = await wpAjax(
					'rsam_save_product',
					{ form_data: formData },
					saveBtn
				);
				showToast(result.message, 'success');
				closeModal();
				fetchProducts(); // (List) (refresh) karein
			} catch (error) {
				// (wpAjax) (toast) (show) kar dega
			}
		});

		// (Modal) khulne ke baad (form) ko (populate) karein
		if (isEditing) {
			const form = state.ui.modal.body.querySelector('#rsam-product-form');
			form.querySelector('[name="product_id"]').value = productData.id;
			form.querySelector('[name="name"]').value = productData.name;
			form.querySelector('[name="category"]').value = productData.category;
			form.querySelector('[name="unit_type"]').value =
				productData.unit_type;
			form.querySelector('[name="selling_price"]').value =
				productData.selling_price;
			form.querySelector('[name="low_stock_threshold"]').value =
				productData.low_stock_threshold;
			form.querySelector('[name="has_expiry"]').checked =
				!!Number(productData.has_expiry);
		}
	}

	/**
	 * (Product) ko (delete) karne ke liye (confirmation) (prompt) dikhata hai.
	 * @param {string|number} productId
	 * @param {string} productName
	 */
	function confirmDeleteProduct(productId, productName) {
		const title = `${rsamData.strings.delete} ${productName}?`;
		const message = `Are you sure you want to delete "${productName}"? This action cannot be undone.`;

		openConfirmModal(title, message, async (e) => {
			// (Delete callback)
			const deleteBtn = e.target;
			try {
				const result = await wpAjax(
					'rsam_delete_product',
					{ product_id: productId },
					deleteBtn
				);
				showToast(result.message, 'success');
				closeConfirmModal();
				fetchProducts(); // (List) (refresh) karein
			} catch (error) {
				// (wpAjax) (toast) (show) kar dega
				closeConfirmModal();
			}
		});
	}

	/** Part 3 — Yahan khatam hua */
  

  

	/** Part 1 — Yahan khatam hua */
})(); // (IIFE) (close)
