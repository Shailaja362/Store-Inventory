# Prompt Log

Screenshots of the actual prompts used with Claude Code while building this app, in the order they were sent.

| # | Screenshot | What was asked |
|---|---|---|
| 01 | [`01-order-management-flow-a.png`](./01-order-management-flow-a.png), [`-b`](./01-order-management-flow-b.png) | Initial spec: build the Order Management flow — order list page, "New Order" button, Create Order page with a customer card and a product card, low-stock highlighting, stock validation, and an activity/order log on save. |
| 02 | [`02-order-form-ui-tailwind.png`](./02-order-form-ui-tailwind.png) | Style the create-order form with Tailwind: a separate low-stock card, +/- quantity buttons, and searchable customer/product dropdowns using Choices.js. |
| 03 | [`03-product-table-tailwind-fix.png`](./03-product-table-tailwind-fix.png) | Add a product table with an "Add Product" dropdown, and fix the Tailwind styling on the input fields and product card (width was too narrow). |
| 04 | [`04-order-page-redesign-bill-preview-a.png`](./04-order-page-redesign-bill-preview-a.png), [`-b`](./04-order-page-redesign-bill-preview-b.png) | Redesign the order page: drop the in-stock badge, move low-stock alerts to their own card, remove the separate "Save Order" button, and auto-save + log the order when "Generate Bill" is clicked, showing a bill preview (product, quantity, price, line total, grand total, amount paid, balance). |
| 05 | [`05-validation-pdf-action-column-a.png`](./05-validation-pdf-action-column-a.png), [`-b`](./05-validation-pdf-action-column-b.png), [`-c`](./05-validation-pdf-action-column-c.png) | Validation rules (require customer email/name, minimum amount paid), add a Download PDF button on the bill preview, rename the order list's "View" column to "Action" with view/download-PDF icons, and add a New Product modal with front- and back-end validation. |
| 06 | [`06-customer-duplicate-stock-toasts.png`](./06-customer-duplicate-stock-toasts.png) | Show a message when a new customer's email already exists, and add toast notifications for stock limits (max stock reached, can't decrease quantity below 1). |
| 07 | [`07-ui-ux-redesign-fullstack-style.png`](./07-ui-ux-redesign-fullstack-style.png) | Redesign the UI to look like a professional full-stack production app rather than a junior-level project: improve headings, cards, buttons, forms, tables, modals, navigation, typography, spacing, colors, and alerts/toasts, with a distinct but consistent visual style per section and responsive layouts — all existing functionality and backend logic left unchanged. |
