$(function () {
    'use strict';

    var config = window.orderFormConfig || {};
    var products = JSON.parse($('#products-data').text() || '[]');
    var customers = JSON.parse($('#customers-data').text() || '[]');
    var rowIndex = 0;
    var orderPlaced = false;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
    });

    function money(value) {
        return Number(value || 0).toFixed(2);
    }

    function showToast(message, variant) {
        var colors = {
            warning: 'border-amber-200 bg-amber-50 text-amber-800',
            error: 'border-rose-200 bg-rose-50 text-rose-800',
            info: 'border-indigo-200 bg-white text-indigo-700',
        };

        var $toast = $('<div></div>')
            .addClass('rounded-xl border px-4 py-3 text-sm font-medium shadow-lg shadow-slate-900/5 transition-all duration-300 opacity-0 -translate-y-2')
            .addClass(colors[variant] || colors.warning)
            .text(message);

        $('#toast-container').append($toast);

        requestAnimationFrame(function () {
            $toast.removeClass('opacity-0 -translate-y-2');
        });

        setTimeout(function () {
            $toast.addClass('opacity-0');
            setTimeout(function () {
                $toast.remove();
            }, 300);
        }, 3500);
    }

    function findCustomerByEmail(email) {
        var normalized = (email || '').trim().toLowerCase();

        if (!normalized) {
            return null;
        }

        for (var i = 0; i < customers.length; i++) {
            if (customers[i].email.toLowerCase() === normalized) {
                return customers[i];
            }
        }

        return null;
    }

    function findProduct(id) {
        for (var i = 0; i < products.length; i++) {
            if (String(products[i].id) === String(id)) {
                return products[i];
            }
        }
        return null;
    }

    function productOptionLabel(product) {
        var stock = parseInt(product.stock_quantity, 10);
        var label = product.name + ' (' + product.code + ') — ' + money(product.price);

        if (stock <= 0) {
            label += ' — Out of stock';
        } else if (stock <= config.lowStockThreshold) {
            label += ' — Low stock: ' + stock + ' left';
        }

        return label;
    }

    function toggleEmptyState() {
        var hasRows = $('#product-rows tr[data-product-id]').length > 0;
        $('#no-products-row').toggle(!hasRows);
    }

    function findRowByProductId(productId) {
        return $('#product-rows tr[data-product-id="' + productId + '"]');
    }

    function updateRowDisplay($row) {
        var product = findProduct($row.data('product-id'));

        if (!product) {
            return;
        }

        var $qtyInput = $row.find('.qty-input');
        var stock = parseInt(product.stock_quantity, 10);

        $qtyInput.attr('max', stock > 0 ? stock : 1);

        var quantity = parseInt($qtyInput.val(), 10) || 0;
        var price = parseFloat(product.price);
        var taxPct = parseFloat(product.tax_percentage);
        var lineSubtotal = quantity * price;
        var lineTax = lineSubtotal * (taxPct / 100);

        $row.find('.price-cell').text(money(price));
        $row.find('.line-total-cell').text(money(lineSubtotal + lineTax));
        $qtyInput.toggleClass('border-red-500', quantity > stock);

        recomputeTotals();
    }

    function addProductRow(productId) {
        var product = findProduct(productId);

        if (!product) {
            return;
        }

        var $existingRow = findRowByProductId(productId);

        if ($existingRow.length) {
            var $qtyInput = $existingRow.find('.qty-input');
            var max = parseInt($qtyInput.attr('max'), 10) || Infinity;
            $qtyInput.val(Math.min(max, (parseInt($qtyInput.val(), 10) || 0) + 1));
            updateRowDisplay($existingRow);
            return;
        }

        var index = rowIndex++;

        var row = $(
            '<tr data-row-index="' + index + '" data-product-id="' + product.id + '">' +
                '<td class="px-3 py-2.5">' +
                    '<input type="hidden" name="items[' + index + '][product_id]" value="' + product.id + '">' +
                    '<span class="font-medium text-slate-800">' + product.name + '</span> ' +
                    '<span class="text-slate-400">(' + product.code + ')</span>' +
                '</td>' +
                '<td class="px-3 py-2.5">' +
                    '<div class="flex items-center gap-1">' +
                        '<button type="button" class="qty-decrement inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 text-slate-600 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600" aria-label="Decrease quantity">&minus;</button>' +
                        '<input type="number" name="items[' + index + '][quantity]" class="qty-input block w-14 rounded-lg border border-slate-300 px-2 py-1.5 text-center text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-3 focus:ring-indigo-500/15" min="1" value="1" required>' +
                        '<button type="button" class="qty-increment inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 text-slate-600 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600" aria-label="Increase quantity">&plus;</button>' +
                    '</div>' +
                '</td>' +
                '<td class="px-3 py-2.5 text-right price-cell text-slate-600">0.00</td>' +
                '<td class="px-3 py-2.5 text-right line-total-cell font-semibold text-slate-900">0.00</td>' +
                '<td class="px-3 py-2.5 text-right">' +
                    '<button type="button" class="remove-row flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600" title="Remove">&times;</button>' +
                '</td>' +
            '</tr>'
        );

        $('#product-rows').append(row);
        toggleEmptyState();
        updateRowDisplay(row);
    }

    function recomputeTotals() {
        var subtotal = 0;
        var tax = 0;

        $('#product-rows tr[data-product-id]').each(function () {
            var $row = $(this);
            var product = findProduct($row.data('product-id'));

            if (!product) {
                return;
            }

            var quantity = parseInt($row.find('.qty-input').val(), 10) || 0;
            var price = parseFloat(product.price);
            var taxPct = parseFloat(product.tax_percentage);
            var lineSubtotal = quantity * price;

            subtotal += lineSubtotal;
            tax += lineSubtotal * (taxPct / 100);
        });

        var grandTotal = subtotal + tax;

        $('#summary-subtotal').text(money(subtotal));
        $('#summary-tax').text(money(tax));
        $('#summary-grand-total').text(money(grandTotal));

        updateBalance(grandTotal);
    }

    function updateBalance(grandTotal) {
        var amountPaidRaw = $('#amount_paid').val();

        if (amountPaidRaw === '') {
            $('#balance-label').text('Balance to Return');
            $('#summary-balance').text('—');
            return;
        }

        var amountPaid = parseFloat(amountPaidRaw) || 0;
        var balance = amountPaid - grandTotal;

        $('#balance-label').text(balance < 0 ? 'Balance Due' : 'Balance to Return');
        $('#summary-balance').text(money(Math.abs(balance)));
    }

    function currentGrandTotal() {
        return parseFloat($('#summary-grand-total').text()) || 0;
    }

    function clearErrors() {
        $('#form-alert').addClass('hidden').text('');
        $('[data-error-for]').addClass('hidden').text('');
    }

    function showErrors(response) {
        var message = (response && response.responseJSON && response.responseJSON.message) || 'Something went wrong. Please review the order.';
        $('#form-alert').removeClass('hidden').text(message);

        var errors = response && response.responseJSON && response.responseJSON.errors;

        if (!errors) {
            return;
        }

        Object.keys(errors).forEach(function (field) {
            var text = errors[field].join(' ');
            var $target = $('[data-error-for="' + field + '"]');

            if ($target.length) {
                $target.removeClass('hidden').text(text);
            }
        });
    }

    function toggleNewCustomerFields(isExistingCustomerSelected) {
        $('#customer_name, #customer_email')
            .prop('disabled', isExistingCustomerSelected)
            .prop('required', !isExistingCustomerSelected);

        if (isExistingCustomerSelected) {
            $('#customer_name, #customer_email').val('');
            $('#customer-exists-note').addClass('hidden').text('');
        }
    }

    function renderBillPreview(order) {
        $('#bill-order-id').text(order.order_number);
        $('#bill-customer-name').text(order.customer.name);
        $('#bill-customer-email').text(order.customer.email);

        var $items = $('#bill-items').empty();

        order.items.forEach(function (item) {
            $items.append(
                '<tr>' +
                    '<td class="px-3 py-2.5">' + item.product_name + ' <span class="text-slate-400">(' + item.product_code + ')</span></td>' +
                    '<td class="px-3 py-2.5 text-center">' + item.quantity + '</td>' +
                    '<td class="px-3 py-2.5 text-right">' + money(item.unit_price) + '</td>' +
                    '<td class="px-3 py-2.5 text-right font-semibold text-slate-900">' + money(item.line_total) + '</td>' +
                '</tr>'
            );
        });

        $('#bill-grand-total').text(money(order.grand_total));

        if (order.amount_paid !== null) {
            $('#bill-amount-paid-row').show();
            $('#bill-balance-row').show();
            $('#bill-amount-paid').text(money(order.amount_paid));

            var balance = parseFloat(order.balance);
            $('#bill-balance-label').text(balance < 0 ? 'Balance Due' : 'Balance Returned');
            $('#bill-balance').text(money(Math.abs(balance)));
        } else {
            $('#bill-amount-paid-row').hide();
            $('#bill-balance-row').hide();
        }

        $('#bill-download-pdf').attr('href', config.orderPdfUrlTemplate.replace('__ORDER_ID__', order.id));

        $('#bill-preview')
            .removeClass('hidden')
            .get(0)
            .scrollIntoView({behavior: 'smooth', block: 'start'});
    }

    function lockForm() {
        orderPlaced = true;

        $('#product-picker, #customer_select, #customer_name, #customer_email, #amount_paid, #generate-bill')
            .prop('disabled', true);

        $('#product-rows .qty-input, #product-rows .qty-increment, #product-rows .qty-decrement, #product-rows .remove-row')
            .prop('disabled', true)
            .addClass('opacity-50 pointer-events-none');
    }

    var customerChoices = new Choices('#customer_select', {
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
    });

    $('#customer_select').on('change', function () {
        toggleNewCustomerFields(Boolean($(this).val()));
    });

    toggleNewCustomerFields(false);

    $('#customer_email').on('blur', function () {
        var existing = findCustomerByEmail($(this).val());
        var $note = $('#customer-exists-note');

        if (existing) {
            $note.removeClass('hidden').text('A customer with this email already exists (' + existing.name + '). This order will be linked to their existing account.');
        } else {
            $note.addClass('hidden').text('');
        }
    });

    $('#customer_email').on('input', function () {
        $('#customer-exists-note').addClass('hidden').text('');
    });

    var $productPicker = $('#product-picker');

    products.forEach(function (product) {
        var stock = parseInt(product.stock_quantity, 10);
        var $option = $('<option></option>')
            .val(product.id)
            .text(productOptionLabel(product));

        if (stock <= 0) {
            $option.prop('disabled', true);
        }

        $productPicker.append($option);
    });

    var productPickerChoices = new Choices($productPicker[0], {
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
        placeholderValue: 'Select a product…',
    });

    $productPicker.on('change', function () {
        var productId = $(this).val();

        if (!productId || orderPlaced) {
            return;
        }

        $('[data-error-for="items"]').addClass('hidden').text('');
        addProductRow(productId);
        productPickerChoices.setChoiceByValue('');
    });

    $('#amount_paid').on('input', function () {
        updateBalance(currentGrandTotal());
    });

    $('#product-rows')
        .on('input', '.qty-input', function () {
            updateRowDisplay($(this).closest('tr'));
        })
        .on('click', '.qty-increment', function () {
            var $row = $(this).closest('tr');
            var $input = $(this).siblings('.qty-input');
            var max = parseInt($input.attr('max'), 10) || Infinity;
            var current = parseInt($input.val(), 10) || 0;
            var next = Math.min(max, current + 1);

            $input.val(next);
            updateRowDisplay($row);

            if (next >= max) {
                var product = findProduct($row.data('product-id'));
                var productName = product ? product.name : 'this product';
                showToast('Maximum available stock reached for ' + productName + ' (' + max + ' units).', 'warning');
            }
        })
        .on('click', '.qty-decrement', function () {
            var $input = $(this).siblings('.qty-input');
            var current = parseInt($input.val(), 10) || 1;

            if (current <= 1) {
                showToast('Quantity cannot be less than 1.', 'warning');
                return;
            }

            $input.val(current - 1);
            updateRowDisplay($(this).closest('tr'));
        })
        .on('click', '.remove-row', function () {
            $(this).closest('tr').remove();
            toggleEmptyState();
            recomputeTotals();
        });

    $('#order-form').on('submit', function (event) {
        event.preventDefault();

        if (orderPlaced) {
            return;
        }

        clearErrors();

        var hasError = false;

        if ($('#product-rows tr[data-product-id]').length === 0) {
            $('[data-error-for="items"]').removeClass('hidden').text('Add at least one product to the order.');
            hasError = true;
        }

        var amountPaid = parseFloat($('#amount_paid').val());

        if (!amountPaid || amountPaid < 1) {
            $('[data-error-for="amount_paid"]').removeClass('hidden').text('Enter an amount of at least ₹1.');
            hasError = true;
        }

        if (!$('#customer_select').val()) {
            if (!$('#customer_name').val().trim()) {
                $('[data-error-for="customer_name"]').removeClass('hidden').text('The customer name is required.');
                hasError = true;
            }

            if (!$('#customer_email').val().trim()) {
                $('[data-error-for="customer_email"]').removeClass('hidden').text('The customer email is required.');
                hasError = true;
            }
        }

        if (hasError) {
            return;
        }

        var $submit = $('#generate-bill').prop('disabled', true);

        $.ajax({
            url: config.storeUrl,
            method: 'POST',
            data: $(this).serialize(),
        }).done(function (response) {
            renderBillPreview(response.order);
            lockForm();
        }).fail(function (response) {
            showErrors(response);
            $submit.prop('disabled', false);
        });
    });

    $('#new-order-btn').on('click', function () {
        window.location.href = config.createUrl;
    });

    // --- New product modal -------------------------------------------------

    function openProductModal() {
        $('#new-product-form')[0].reset();
        $('#product-form-alert').addClass('hidden').text('');
        $('#new-product-form [data-error-for]').addClass('hidden').text('');
        $('#new-product-modal').removeClass('hidden');
    }

    function closeProductModal() {
        $('#new-product-modal').addClass('hidden');
        $('#new-product-form')[0].reset();
        $('#product-form-alert').addClass('hidden').text('');
        $('#new-product-form [data-error-for]').addClass('hidden').text('');
    }

    function validateProductForm() {
        var errors = {};

        if (!$('#product_name').val().trim()) {
            errors.name = 'The name is required.';
        }

        if (!$('#product_code').val().trim()) {
            errors.code = 'The unique code is required.';
        }

        var price = parseFloat($('#product_price').val());
        if (!$('#product_price').val() || isNaN(price) || price <= 0) {
            errors.price = 'Enter a price greater than 0.';
        }

        var taxRaw = $('#product_tax').val();
        var tax = parseFloat(taxRaw);
        if (taxRaw === '' || isNaN(tax) || tax < 0 || tax > 100) {
            errors.tax_percentage = 'Enter a tax percentage between 0 and 100.';
        }

        var stockRaw = $('#product_stock').val();
        var stock = parseInt(stockRaw, 10);
        if (stockRaw === '' || isNaN(stock) || stock < 0) {
            errors.stock_quantity = 'Enter a stock quantity of 0 or more.';
        }

        return errors;
    }

    function showProductFormErrors(errors, message) {
        $('#product-form-alert').removeClass('hidden').text(message || 'Please fix the errors below.');

        Object.keys(errors).forEach(function (field) {
            var text = Array.isArray(errors[field]) ? errors[field].join(' ') : errors[field];
            $('#new-product-form [data-error-for="' + field + '"]').removeClass('hidden').text(text);
        });
    }

    $('#open-product-modal').on('click', openProductModal);
    $('#close-product-modal, #cancel-product-modal, #new-product-backdrop').on('click', closeProductModal);

    $('#new-product-form').on('submit', function (event) {
        event.preventDefault();

        $('#product-form-alert').addClass('hidden').text('');
        $('#new-product-form [data-error-for]').addClass('hidden').text('');

        var errors = validateProductForm();

        if (Object.keys(errors).length > 0) {
            showProductFormErrors(errors);
            return;
        }

        var $save = $('#save-product-btn').prop('disabled', true);

        $.ajax({
            url: config.productStoreUrl,
            method: 'POST',
            data: $(this).serialize(),
        }).done(function (response) {
            var product = response.data;
            products.push(product);

            var stock = parseInt(product.stock_quantity, 10);
            productPickerChoices.setChoices([{
                value: String(product.id),
                label: productOptionLabel(product),
                disabled: stock <= 0,
            }], 'value', 'label', false);

            closeProductModal();
        }).fail(function (response) {
            var errors = (response.responseJSON && response.responseJSON.errors) || {};
            showProductFormErrors(errors, (response.responseJSON && response.responseJSON.message));
        }).always(function () {
            $save.prop('disabled', false);
        });
    });
});
