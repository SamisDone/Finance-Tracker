/**
 * Finance Tracker - Main JavaScript
 * DRY: Reusable functions extracted from PHP files
 */

/**
 * Setup add new item (category, source, method) to dropdown
 */
function setupAddNew(fieldId, selectId, btnId, saveBtnId, cancelBtnId, formId) {
    const btn = document.getElementById(btnId);
    const form = document.getElementById(formId);
    const saveBtn = document.getElementById(saveBtnId);
    const cancelBtn = document.getElementById(cancelBtnId);
    const select = document.getElementById(selectId);
    const input = document.getElementById(fieldId);

    if (btn) {
        btn.onclick = () => { form.style.display = 'block'; btn.style.display = 'none'; };
    }
    if (cancelBtn) {
        cancelBtn.onclick = () => { form.style.display = 'none'; btn.style.display = ''; };
    }
    if (saveBtn) {
        saveBtn.onclick = () => {
            const val = input.value.trim();
            if (val) {
                let exists = false;
                for (let i = 0; i < select.options.length; i++) {
                    if (select.options[i].value === val) { exists = true; break; }
                }
                if (!exists) {
                    const opt = document.createElement('option');
                    opt.value = val; opt.textContent = val;
                    select.appendChild(opt);
                }
                select.value = val;
                form.style.display = 'none';
                btn.style.display = '';
            }
        };
    }
}

/**
 * Initialize all dropdown add-new functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // Income page
    setupAddNew('new_source_name', 'source', 'addSourceBtn', 'saveNewSource', 'cancelNewSource', 'newSourceForm');
    setupAddNew('new_category_name', 'category', 'addCategoryBtn', 'saveNewCategory', 'cancelNewCategory', 'newCategoryForm');
    
    // Expenses page
    setupAddNew('new_category_name', 'category', 'addCategoryBtn', 'saveNewCategory', 'cancelNewCategory', 'newCategoryForm');
    setupAddNew('new_method_name', 'payment_method', 'addMethodBtn', 'saveNewMethod', 'cancelNewMethod', 'newMethodForm');
    
    // Budget page
    const addBudgetCatBtn = document.getElementById('addBudgetCategoryBtn');
    const newBudgetCatForm = document.getElementById('newBudgetCategoryForm');
    const saveBudgetCat = document.getElementById('saveNewBudgetCategory');
    const cancelBudgetCat = document.getElementById('cancelNewBudgetCategory');
    const container = document.getElementById('budget-category-limits');
    
    if (addBudgetCatBtn) {
        addBudgetCatBtn.onclick = () => { newBudgetCatForm.style.display = 'block'; addBudgetCatBtn.style.display = 'none'; };
    }
    if (cancelBudgetCat) {
        cancelBudgetCat.onclick = () => { newBudgetCatForm.style.display = 'none'; addBudgetCatBtn.style.display = ''; };
    }
    if (saveBudgetCat) {
        saveBudgetCat.onclick = () => {
            const val = document.getElementById('new_budget_category_name').value.trim();
            if (val) {
                let exists = false;
                container.querySelectorAll('label').forEach(function(lbl) {
                    if (lbl.textContent === val) exists = true;
                });
                if (!exists) {
                    const div = document.createElement('div');
                    div.className = 'form-group budget-cat-limit-row';
                    div.innerHTML = `<label>${val}</label><input type="number" step="0.01" min="0" name="category_limits[new_${val.replace(/[^a-zA-Z0-9]/g,'_')}]" placeholder="Limit for ${val}">`;
                    container.appendChild(div);
                }
                newBudgetCatForm.style.display = 'none';
                addBudgetCatBtn.style.display = '';
            }
        };
    }
});
