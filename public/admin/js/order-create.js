$(function () {
    'use strict';

    var config = window.orderFormConfig || {};
    var products = JSON.parse($('#products-data').text() || '[]');
    var rowIndex = 0;
    var rowChoices = {};

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
    });

    function money(value) {
        return Number(value || 0).toFixed(2);
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

    function addRow() {
        var index = rowIndex++;

        var row = $(
            '<tr data-row-index="' + index + '">' +
                '<td class="px-3 py-2">' +
                    '<select name="items[' + index + '][product_id]" class="product-select block w-full" required></select>' +
                '</td>' +
                '<td class="px-3 py-2 stock-cell text-sm text-slate-500">&mdash;</td>' +
                '<td class="px-3 py-2">' +
                    '<div class="flex items-center gap-1">' +
                        '<button type="button" class="qty-decrement inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50" aria-label="Decrease quantity">&minus;</button>' +
                        '<input type="number" name="items[' + index + '][quantity]" class="qty-input block w-14 rounded-md border-slate-300 text-center shadow-sm sm:text-sm" min="1" value="1" required>' +
                        '<button type="button" class="qty-increment inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50" aria-label="Increase quantity">&plus;</button>' +
                    '</div>' +
                '</td>' +
                '<td class="px-3 py-2 text-right price-cell">0.00</td>' +
                '<td class="px-3 py-2 text-right line-total-cell font-medium">0.00</td>' +
                '<td class="px-3 py-2 text-right">' +
                    '<button type="button" class="remove-row text-slate-400 hover:text-red-600" title="Remove">&times;</button>' +
                '</td>' +
            '</tr>'
        );

        $('#product-rows').append(row);

        var $select = row.find('.product-select');
        $select.append('<option value="">Select a product&hellip;</option>');

        products.forEach(function (product) {
            var stock = parseInt(product.stock_quantity, 10);
            var $option = $('<option></option>')
                .val(product.id)
                .text(productOptionLabel(product));

            if (stock <= 0) {
                $option.prop('disabled', true);
            }

            $select.append($option);
        });

        rowChoices[index] = new Choices($select[0], {
            searchEnabled: true,
            shouldSort: false,
            itemSelectText: '',
            placeholder: true,
            placeholderValue: 'Select a product…',
        });
    }

    function updateRow($row) {
        var productId = $row.find('.product-select').val();
        var product = findProduct(productId);
        var $stockCell = $row.find('.stock-cell');
        var $qtyInput = $row.find('.qty-input');

        if (!product) {
            $stockCell.text('—').removeClass('text-amber-600 text-red-600 font-medium');
            $row.find('.price-cell').text('0.00');
            $row.find('.line-total-cell').text('0.00');
            return;
        }

        var stock = parseInt(product.stock_quantity, 10);
        $qtyInput.attr('max', stock > 0 ? stock : 1);

        $stockCell
            .text(stock + ' in stock')
            .toggleClass('text-red-600 font-medium', stock <= 0)
            .toggleClass('text-amber-600 font-medium', stock > 0 && stock <= config.lowStockThreshold);

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

    function recomputeTotals() {
        var subtotal = 0;
        var tax = 0;

        $('#product-rows tr').each(function () {
            var $row = $(this);
            var product = findProduct($row.find('.product-select').val());

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

        $('#summary-subtotal').text(money(subtotal));
        $('#summary-tax').text(money(tax));
        $('#summary-grand-total').text(money(subtotal + tax));
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
        }
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

    $('#add-product-row').on('click', addRow);

    $('#product-rows')
        .on('change', '.product-select', function () {
            updateRow($(this).closest('tr'));
        })
        .on('input', '.qty-input', function () {
            updateRow($(this).closest('tr'));
        })
        .on('click', '.qty-increment', function () {
            var $input = $(this).siblings('.qty-input');
            var max = parseInt($input.attr('max'), 10) || Infinity;
            $input.val(Math.min(max, (parseInt($input.val(), 10) || 0) + 1));
            updateRow($(this).closest('tr'));
        })
        .on('click', '.qty-decrement', function () {
            var $input = $(this).siblings('.qty-input');
            $input.val(Math.max(1, (parseInt($input.val(), 10) || 1) - 1));
            updateRow($(this).closest('tr'));
        })
        .on('click', '.remove-row', function () {
            var $rows = $('#product-rows tr');

            if ($rows.length > 1) {
                var index = $(this).closest('tr').data('row-index');

                if (rowChoices[index]) {
                    rowChoices[index].destroy();
                    delete rowChoices[index];
                }

                $(this).closest('tr').remove();
                recomputeTotals();
            }
        });

    $('#order-form').on('submit', function (event) {
        event.preventDefault();
        clearErrors();

        var $submit = $('#submit-order').prop('disabled', true);

        $.ajax({
            url: config.storeUrl,
            method: 'POST',
            data: $(this).serialize(),
        }).done(function (response) {
            window.location.href = response.redirect;
        }).fail(function (response) {
            showErrors(response);
        }).always(function () {
            $submit.prop('disabled', false);
        });
    });

    addRow();
});
